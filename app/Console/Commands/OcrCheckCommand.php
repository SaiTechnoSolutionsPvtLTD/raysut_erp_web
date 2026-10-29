<?php

namespace App\Console\Commands;

use App\Services\Permit\TesseractExtractor;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class OcrCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permits:ocr-check {--fixture= : Path to specific fixture image}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify local Tesseract OCR installation, version, installed languages, and test extraction on sample fixture';

    /**
     * Execute the console command.
     */
    public function handle(TesseractExtractor $extractor): int
    {
        $this->info('======================================================');
        $this->info('     RMSC Permit Local OCR Health & Diagnostics       ');
        $this->info('======================================================');

        $binary = config('rmsc.permit_extraction.tesseract.binary', env('TESSERACT_PATH', 'tesseract'));
        $langs = config('rmsc.permit_extraction.tesseract.langs', env('TESSERACT_LANGS', 'eng+ara'));
        $timeout = (int) config('rmsc.permit_extraction.tesseract.timeout', 20);

        $this->line("<comment>Configured Binary Path:</comment> {$binary}");
        $this->line("<comment>Configured Languages:</comment>   {$langs}");
        $this->newLine();

        // 1. Check Binary Version
        $this->info('1. Checking Tesseract binary...');
        $versionProcess = new Process([$binary, '--version']);
        $versionProcess->setTimeout(5);

        try {
            $versionProcess->run();
            if ($versionProcess->isSuccessful()) {
                $versionOutput = trim(explode("\n", $versionProcess->getOutput())[0] ?? 'Version unknown');
                $this->line("   <info>✔ Success:</info> {$versionOutput}");
            } else {
                $this->line("   <error>✘ Failed:</error> Binary returned non-zero exit code ({$versionProcess->getExitCode()}).");
                $this->line("   Error Output: " . trim($versionProcess->getErrorOutput()));
                return Command::FAILURE;
            }
        } catch (Exception $e) {
            $this->line("   <error>✘ Error:</error> Cannot execute binary: {$e->getMessage()}");
            $this->line("   <comment>Note:</comment> Please install Tesseract OCR and set TESSERACT_PATH in .env.");
            return Command::FAILURE;
        }

        // 2. Check Installed Language Packs
        $this->newLine();
        $this->info('2. Checking Installed Language Packs...');
        $tessdataDir = config('rmsc.permit_extraction.tesseract.tessdata_dir', env('TESSDATA_DIR'));
        $langsCmd = [$binary];
        if (! empty($tessdataDir)) {
            $langsCmd[] = '--tessdata-dir';
            $langsCmd[] = $tessdataDir;
        }
        $langsCmd[] = '--list-langs';
        $langsProcess = new Process($langsCmd);
        if (! empty($tessdataDir)) {
            $langsProcess->setEnv(['TESSDATA_PREFIX' => $tessdataDir]);
        }
        $langsProcess->setTimeout(5);

        try {
            $langsProcess->run();
            if ($langsProcess->isSuccessful()) {
                $outputLines = array_filter(array_map('trim', explode("\n", $langsProcess->getOutput())));
                // Skip header like "List of available languages (X):"
                $installedLangs = array_values(array_filter($outputLines, fn($l) => ! str_contains($l, ':')));
                $this->line("   <info>✔ Installed Languages:</info> " . implode(', ', $installedLangs));

                $configuredList = explode('+', $langs);
                $missing = array_diff($configuredList, $installedLangs);
                if (! empty($missing)) {
                    $this->warn("   <comment>⚠ Warning:</comment> Missing configured language pack(s): " . implode(', ', $missing));
                } else {
                    $this->line("   <info>✔ All configured language packs are installed.</info>");
                }
            } else {
                $this->warn("   Could not retrieve installed languages list.");
            }
        } catch (Exception $e) {
            $this->warn("   Error checking language list: " . $e->getMessage());
        }

        // 3. Run OCR on Fixture Sample
        $this->newLine();
        $this->info('3. Testing OCR Extraction on Fixture Image...');

        $fixturePath = $this->option('fixture')
            ?: base_path('tests/Fixtures/permit-samples/01_valid.png');

        if (! file_exists($fixturePath)) {
            $this->warn("   Fixture image not found at: {$fixturePath}");
            $this->line("   Creating a temporary test fixture for extraction verification...");
            $fixturePath = tempnam(sys_get_temp_dir(), 'test_ocr_') . '.png';
            $this->createFallbackImage($fixturePath);
            $cleanupTemp = true;
        } else {
            $cleanupTemp = false;
        }

        $startTime = microtime(true);
        $result = $extractor->extract($fixturePath);
        $elapsed = round(microtime(true) - $startTime, 2);

        if ($cleanupTemp && file_exists($fixturePath)) {
            @unlink($fixturePath);
        }

        if ($result->success) {
            $this->line("   <info>✔ OCR Extraction Passed ({$elapsed}s):</info>");
            $this->table(
                ['Field', 'Extracted Value', 'Confidence'],
                [
                    ['Civil ID', $result->fields['civil_id'] ?? 'null', $result->confidences['civil_id'] ?? 0],
                    ['Holder Name', $result->fields['holder_name'] ?? 'null', $result->confidences['holder_name'] ?? 0],
                    ['Permit No', $result->fields['permit_no'] ?? 'null', $result->confidences['permit_no'] ?? 0],
                    ['Annual Limit', $result->fields['annual_limit'] ?? 'null', $result->confidences['annual_limit'] ?? 0],
                    ['Used Amount', $result->fields['used_amount'] ?? 'null', $result->confidences['used_amount'] ?? 0],
                    ['Remaining Amount', $result->fields['remaining_amount'] ?? 'null', $result->confidences['remaining_amount'] ?? 0],
                    ['Valid From', $result->fields['valid_from'] ?? 'null', $result->confidences['valid_from'] ?? 0],
                    ['Valid To', $result->fields['valid_to'] ?? 'null', $result->confidences['valid_to'] ?? 0],
                    ['Overall Confidence', round($result->overallConfidence * 100, 1) . '%', $result->overallConfidence],
                ]
            );

            if (! empty($result->warnings)) {
                $this->warn("   Extraction Warnings: " . implode(' | ', $result->warnings));
            }

            return Command::SUCCESS;
        } else {
            $this->line("   <error>✘ OCR Extraction Failed ({$elapsed}s):</error> {$result->errorMessage}");
            return Command::FAILURE;
        }
    }

    /**
     * Create minimal binary PNG header image if no fixture exists.
     */
    protected function createFallbackImage(string $path): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
        file_put_contents($path, $png);
    }
}
