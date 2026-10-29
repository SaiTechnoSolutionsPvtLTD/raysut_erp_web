<?php

namespace App\Services\Permit;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class TesseractExtractor implements PermitScreenshotExtractor
{
    protected ?\Closure $processRunner = null;
    public ?string $lastTempPath = null;

    public function __construct(
        protected ?PermitTextParser $parser = null,
        protected ?PermitScreenshotExtractor $fallbackDriver = null
    ) {
        $this->parser = $parser ?: new PermitTextParser();
    }

    public function setProcessRunner(?\Closure $runner): self
    {
        $this->processRunner = $runner;
        return $this;
    }

    protected function createProcess(array $cmd, int $timeout, ?string $tessdataDir = null): Process
    {
        if ($this->processRunner) {
            return ($this->processRunner)($cmd, $timeout, $tessdataDir);
        }

        $process = new Process($cmd);
        $process->setTimeout($timeout);

        if (! empty($tessdataDir)) {
            $process->setEnv(['TESSDATA_PREFIX' => $tessdataDir]);
        }

        return $process;
    }

    /**
     * Preprocess image using GD if available: upscale to >= 1800px, grayscale, mild contrast.
     */
    public static function preprocessImage(string $srcPath, string $destPath): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $imageInfo = @getimagesize($srcPath);
        if (! $imageInfo) {
            return false;
        }

        $mime = $imageInfo['mime'] ?? '';
        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($srcPath),
            'image/png' => @imagecreatefrompng($srcPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : null,
            default => null,
        };

        if (! $srcImg) {
            return false;
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);

        // Upscale to a minimum width of 1800 px
        $targetW = max(1800, $origW);
        $targetH = (int) round(($origH / $origW) * $targetW);

        $resized = imagecreatetruecolor($targetW, $targetH);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $targetW, $targetH, $white);

        imagecopyresampled($resized, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
        imagedestroy($srcImg);

        // Convert to grayscale
        imagefilter($resized, IMG_FILTER_GRAYSCALE);

        // Mild contrast stretch
        imagefilter($resized, IMG_FILTER_CONTRAST, -15);

        imagepng($resized, $destPath);
        imagedestroy($resized);

        return true;
    }

    /**
     * Extract permit details from screenshot using Tesseract OCR with optional fallback.
     */
    public function extract(UploadedFile|string $image): PermitExtractionResult
    {
        $srcPath = is_string($image) ? $image : $image->getRealPath();
        if (! file_exists($srcPath)) {
            return PermitExtractionResult::failed('Permit screenshot file not found.');
        }

        $binary = config('rmsc.permit_extraction.tesseract.binary', env('TESSERACT_PATH', 'tesseract'));
        $langs = config('rmsc.permit_extraction.tesseract.langs', env('TESSERACT_LANGS', 'eng+ara'));
        $tessdataDir = config('rmsc.permit_extraction.tesseract.tessdata_dir', env('TESSDATA_DIR'));
        $timeout = (int) config('rmsc.permit_extraction.tesseract.timeout', 20);
        $psm = (int) config('rmsc.permit_extraction.tesseract.psm', 6);

        // Create a temporary file for OCR
        $tempPath = tempnam(sys_get_temp_dir(), 'rmsc_ocr_') . '.png';
        $this->lastTempPath = $tempPath;

        try {
            // Preprocess if GD or Imagick available; fallback to copy
            $preprocessed = static::preprocessImage($srcPath, $tempPath);
            if (! $preprocessed) {
                copy($srcPath, $tempPath);
            }

            // Build Tesseract command: <TESSERACT_PATH> [--tessdata-dir <dir>] <image> stdout -l <TESSERACT_LANGS> --psm 6 tsv
            $cmd = [$binary];
            if (! empty($tessdataDir)) {
                $cmd[] = '--tessdata-dir';
                $cmd[] = $tessdataDir;
            }
            $cmd[] = $tempPath;
            $cmd[] = 'stdout';
            $cmd[] = '-l';
            $cmd[] = $langs;
            $cmd[] = '--psm';
            $cmd[] = (string) $psm;
            $cmd[] = 'tsv';

            $process = $this->createProcess($cmd, $timeout, $tessdataDir);
            $process->run();

            if (! $process->isSuccessful()) {
                $exitCode = $process->getExitCode();
                $errorOutput = $process->getErrorOutput();

                Log::warning('Tesseract process execution failed', [
                    'exit_code' => $exitCode,
                    'reason' => 'Process unsuccessful: ' . trim($errorOutput),
                ]);

                // Check if language pack is missing
                if (str_contains($errorOutput, 'Error opening data file') || str_contains($errorOutput, 'traineddata')) {
                    Log::warning('Tesseract language pack missing', ['langs' => $langs]);
                }

                return $this->handleFallbackOrError($image, 'OCR engine is not installed or not configured. Enter the details manually.');
            }

            $tsvOutput = $process->getOutput();
            if (empty(trim($tsvOutput))) {
                return $this->handleFallbackOrError($image, 'OCR engine produced empty output.');
            }

            // Parse TSV output via PermitTextParser
            try {
                $result = $this->parser->parseTsv($tsvOutput);
                $result->driver = 'tesseract';
            } catch (Exception $pe) {
                Log::warning('Error parsing OCR output', [
                    'error' => $pe->getMessage(),
                    'file' => $pe->getFile(),
                    'line' => $pe->getLine(),
                ]);
                $result = PermitExtractionResult::empty();
                $result->driver = 'tesseract';
                $result->warnings[] = 'Some details could not be read reliably from this image. Please confirm the fields manually.';
            }

            // Check if fallback is configured and required
            $fallbackDriverName = config('rmsc.permit_extraction.fallback_driver', env('PERMIT_OCR_FALLBACK'));
            $fallbackMinConfidence = (float) config('rmsc.permit_extraction.fallback_min_confidence', 0.6);

            $missingRequired = empty($result->fields['civil_id'])
                || empty($result->fields['holder_name'])
                || empty($result->fields['remaining_amount']);

            if (! empty($fallbackDriverName) && ($result->overallConfidence < $fallbackMinConfidence || $missingRequired)) {
                Log::info('Tesseract confidence below threshold or missing required field. Invoking fallback driver.', [
                    'overall_confidence' => $result->overallConfidence,
                    'missing_required' => $missingRequired,
                    'fallback_driver' => $fallbackDriverName,
                ]);

                return $this->runFallback($image, $fallbackDriverName);
            }

            return $result;

        } catch (ProcessTimedOutException $pte) {
            Log::warning('Tesseract process timed out', ['timeout' => $timeout]);
            return $this->handleFallbackOrError($image, 'OCR processing timed out. Enter the details manually.');
        } catch (Exception $e) {
            Log::warning('Tesseract exception occurred', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->handleFallbackOrError($image, 'OCR engine is not installed or not configured. Enter the details manually.');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Trigger fallback driver if available; otherwise return standard friendly error.
     */
    protected function handleFallbackOrError(UploadedFile|string $image, string $friendlyError): PermitExtractionResult
    {
        $fallbackDriverName = config('rmsc.permit_extraction.fallback_driver', env('PERMIT_OCR_FALLBACK'));
        if (! empty($fallbackDriverName)) {
            return $this->runFallback($image, $fallbackDriverName);
        }

        return PermitExtractionResult::failed($friendlyError);
    }

    /**
     * Execute external fallback driver when explicitly configured.
     */
    protected function runFallback(UploadedFile|string $image, string $driverName): PermitExtractionResult
    {
        $driver = $this->fallbackDriver ?: $this->resolveFallbackDriver($driverName);

        if (! $driver) {
            return PermitExtractionResult::failed('Configured fallback OCR driver is unavailable.');
        }

        $res = $driver->extract($image);
        $res->driver = $driverName;

        return $res;
    }

    /**
     * Resolve fallback driver instance by name.
     */
    protected function resolveFallbackDriver(string $driverName): ?PermitScreenshotExtractor
    {
        return match (strtolower($driverName)) {
            'anthropic' => new \App\Services\Permit\Drivers\AnthropicPermitExtractor(),
            'gemini' => new \App\Services\Permit\Drivers\GeminiPermitExtractor(),
            'fake' => new \App\Services\Permit\Drivers\FakePermitExtractor(),
            default => null,
        };
    }
}
