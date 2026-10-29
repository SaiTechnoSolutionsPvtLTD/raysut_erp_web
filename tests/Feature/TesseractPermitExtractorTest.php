<?php

namespace Tests\Feature;

use App\Services\Permit\Drivers\GeminiPermitExtractor;
use App\Services\Permit\PermitExtractionResult;
use App\Services\Permit\TesseractExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class TesseractPermitExtractorTest extends TestCase
{
    use RefreshDatabase;

    protected string $fixturesDir;
    protected string $sampleTsv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesDir = base_path('tests/Fixtures/permit-samples');

        $this->sampleTsv = <<<TSV
level	page_num	block_num	par_num	line_num	word_num	left	top	width	height	conf	text
5	1	1	1	1	1	40	40	70	30	96	Civil
5	1	1	1	1	2	120	40	35	30	95	ID:
5	1	1	1	1	3	165	40	120	30	98	12345678
5	1	1	1	2	1	40	80	60	30	96	Full
5	1	1	1	2	2	110	80	75	30	95	Name:
5	1	1	1	2	3	195	80	80	30	96	Ahmed
5	1	1	1	2	4	285	80	60	30	95	Said
5	1	1	1	2	5	355	80	30	30	96	Al
5	1	1	1	2	6	395	80	90	30	97	Harthy
5	1	1	1	3	1	40	120	85	30	95	Permit
5	1	1	1	3	2	135	120	95	30	96	Number:
5	1	1	1	3	3	240	120	200	30	98	LQ-2026-004517
5	1	1	1	4	1	40	160	85	30	95	Permit
5	1	1	1	4	2	135	160	80	30	94	Status:
5	1	1	1	4	3	225	160	75	30	98	Active
5	1	1	1	5	1	40	200	60	30	95	Valid
5	1	1	1	5	2	110	200	65	30	94	From:
5	1	1	1	5	3	185	200	140	30	97	01/01/2026
5	1	1	1	6	1	40	240	60	30	95	Valid
5	1	1	1	6	2	110	240	40	30	94	To:
5	1	1	1	6	3	160	240	140	30	97	31/12/2026
5	1	1	1	7	1	40	280	90	30	95	Annual
5	1	1	1	7	2	140	280	65	30	94	Limit:
5	1	1	1	7	3	215	280	55	30	95	OMR
5	1	1	1	7	4	280	280	100	30	98	500.000
5	1	1	1	8	1	40	320	65	30	95	Used
5	1	1	1	8	2	115	320	95	30	94	Amount:
5	1	1	1	8	3	220	320	55	30	95	OMR
5	1	1	1	8	4	285	320	100	30	98	120.500
5	1	1	1	9	1	40	360	125	30	95	Remaining
5	1	1	1	9	2	175	360	95	30	94	Balance:
5	1	1	1	9	3	280	360	55	30	95	OMR
5	1	1	1	9	4	345	360	100	30	98	379.500
TSV;
    }

    protected function createFakeProcess(int $exitCode, string $stdout, string $stderr = '', bool $timeout = false): Process
    {
        return new class($exitCode, $stdout, $stderr, $timeout) extends Process {
            public function __construct(
                private int $exitCode,
                private string $stdout,
                private string $stderr,
                private bool $timeout
            ) {
                parent::__construct(['echo', 'fake']);
            }

            public function run(?callable $callback = null, array $env = []): int
            {
                if ($this->timeout) {
                    throw new ProcessTimedOutException($this, ProcessTimedOutException::TYPE_GENERAL);
                }
                return $this->exitCode;
            }

            public function isSuccessful(): bool
            {
                return $this->exitCode === 0;
            }

            public function getOutput(): string
            {
                return $this->stdout;
            }

            public function getErrorOutput(): string
            {
                return $this->stderr;
            }

            public function getExitCode(): ?int
            {
                return $this->exitCode;
            }
        };
    }

    #[Test]
    public function it_extracts_permit_data_successfully_using_faked_process_and_cleans_temp_file(): void
    {
        $imagePath = "{$this->fixturesDir}/01_valid.png";
        $this->assertFileExists($imagePath);

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(0, $this->sampleTsv));

        $result = $extractor->extract($imagePath);

        $this->assertTrue($result->success);
        $this->assertEquals('tesseract', $result->driver);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertEquals('2026-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-12-31', $result->fields['valid_to']);
        $this->assertGreaterThanOrEqual(0.85, $result->overallConfidence);

        // Verify temp file is always deleted
        $this->assertNotNull($extractor->lastTempPath);
        $this->assertFileDoesNotExist($extractor->lastTempPath);
    }

    #[Test]
    public function it_handles_missing_binary_cleanly_without_stack_trace_and_cleans_temp_file(): void
    {
        $imagePath = "{$this->fixturesDir}/01_valid.png";

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(function () {
            throw new \RuntimeException('The system cannot find the file specified.');
        });

        $result = $extractor->extract($imagePath);

        $this->assertFalse($result->success);
        $this->assertEquals('OCR engine is not installed or not configured. Enter the details manually.', $result->errorMessage);
        $this->assertNotNull($extractor->lastTempPath);
        $this->assertFileDoesNotExist($extractor->lastTempPath);
    }

    #[Test]
    public function it_handles_process_timeout_cleanly_and_cleans_temp_file(): void
    {
        $imagePath = "{$this->fixturesDir}/01_valid.png";

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(0, '', '', timeout: true));

        $result = $extractor->extract($imagePath);

        $this->assertFalse($result->success);
        $this->assertEquals('OCR processing timed out. Enter the details manually.', $result->errorMessage);
        $this->assertNotNull($extractor->lastTempPath);
        $this->assertFileDoesNotExist($extractor->lastTempPath);
    }

    #[Test]
    public function it_handles_malformed_empty_tsv_output_cleanly_and_cleans_temp_file(): void
    {
        $imagePath = "{$this->fixturesDir}/01_valid.png";

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(0, '   '));

        $result = $extractor->extract($imagePath);

        $this->assertFalse($result->success);
        $this->assertEquals('OCR engine produced empty output.', $result->errorMessage);
        $this->assertNotNull($extractor->lastTempPath);
        $this->assertFileDoesNotExist($extractor->lastTempPath);
    }

    #[Test]
    public function it_handles_missing_language_pack_cleanly_and_cleans_temp_file(): void
    {
        $imagePath = "{$this->fixturesDir}/01_valid.png";

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(
            1,
            '',
            'Error opening data file /usr/share/tesseract-ocr/4.00/tessdata/ara.traineddata'
        ));

        $result = $extractor->extract($imagePath);

        $this->assertFalse($result->success);
        $this->assertEquals('OCR engine is not installed or not configured. Enter the details manually.', $result->errorMessage);
        $this->assertNotNull($extractor->lastTempPath);
        $this->assertFileDoesNotExist($extractor->lastTempPath);
    }

    #[Test]
    public function it_never_calls_external_service_by_default_even_on_ocr_failure(): void
    {
        Http::fake();

        // Default: no fallback driver configured
        config(['rmsc.permit_extraction.fallback_driver' => null]);

        $imagePath = "{$this->fixturesDir}/01_valid.png";

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(1, '', 'Execution error'));

        $result = $extractor->extract($imagePath);

        $this->assertFalse($result->success);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_calls_fallback_driver_when_configured_and_confidence_is_low(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'civil_id' => '12345678',
                                        'holder_name' => 'Ahmed Said Al Harthy',
                                        'permit_no' => 'LQ-2026-004517',
                                        'annual_limit' => 500.0,
                                        'used_amount' => 120.5,
                                        'remaining_amount' => 379.5,
                                        'valid_from' => '2026-01-01',
                                        'valid_to' => '2026-12-31',
                                        'overall_confidence' => 0.95,
                                        'confidences' => [
                                            'civil_id' => 0.95,
                                            'holder_name' => 0.95,
                                            'remaining_amount' => 0.95,
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        config([
            'rmsc.permit_extraction.fallback_driver' => 'gemini',
            'rmsc.permit_extraction.fallback_min_confidence' => 0.6,
            'rmsc.permit_extraction.gemini.api_key' => 'test-key-12345',
        ]);

        $imagePath = "{$this->fixturesDir}/01_valid.png";

        // Tesseract returns very low confidence or missing required fields
        $lowConfTsv = <<<TSV
level	page_num	block_num	par_num	line_num	word_num	left	top	width	height	conf	text
5	1	1	1	1	1	40	40	70	30	10	Unreadable
TSV;

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(0, $lowConfTsv));

        $result = $extractor->extract($imagePath);

        $this->assertTrue($result->success);
        $this->assertEquals('gemini', $result->driver);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_does_not_call_fallback_when_tesseract_confidence_meets_threshold(): void
    {
        Http::fake();

        config([
            'rmsc.permit_extraction.fallback_driver' => 'gemini',
            'rmsc.permit_extraction.fallback_min_confidence' => 0.6,
            'rmsc.permit_extraction.gemini.api_key' => 'test-key-12345',
        ]);

        $imagePath = "{$this->fixturesDir}/01_valid.png";

        $extractor = new TesseractExtractor();
        $extractor->setProcessRunner(fn () => $this->createFakeProcess(0, $this->sampleTsv));

        $result = $extractor->extract($imagePath);

        $this->assertTrue($result->success);
        $this->assertEquals('tesseract', $result->driver);
        $this->assertGreaterThanOrEqual(0.6, $result->overallConfidence);

        // Fallback must NEVER be called
        Http::assertNothingSent();
    }

    #[Test]
    public function it_executes_real_binary_integration_test_or_skips_when_not_installed(): void
    {
        $binary = config('rmsc.permit_extraction.tesseract.binary', 'tesseract');

        $checkProcess = new Process([$binary, '--version']);
        try {
            $checkProcess->run();
            if (! $checkProcess->isSuccessful()) {
                $this->markTestSkipped("Tesseract binary '{$binary}' is not installed or returned error.");
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped("Tesseract binary '{$binary}' is not available on this environment.");
        }

        $extractor = new TesseractExtractor();

        // Test sample 01, 02, 03, 05, 06
        $samples = ['01_valid', '02_low_quota', '03_expired', '05_cropped', '06_phone_photo'];

        foreach ($samples as $sample) {
            $pngPath = "{$this->fixturesDir}/{$sample}.png";
            $jsonPath = "{$this->fixturesDir}/{$sample}.json";

            $this->assertFileExists($pngPath);
            $this->assertFileExists($jsonPath);

            $expected = json_decode(file_get_contents($jsonPath), true);
            $result = $extractor->extract($pngPath);

            $this->assertTrue($result->success, "Extraction failed for sample {$sample}: {$result->errorMessage}");
            $this->assertEquals($expected['holder_name'], $result->fields['holder_name']);
            $this->assertEquals((float) $expected['annual_limit'], (float) $result->fields['annual_limit']);
            $this->assertEquals((float) $expected['used_amount'], (float) $result->fields['used_amount']);
            $this->assertEquals((float) $expected['remaining_amount'], (float) $result->fields['remaining_amount']);
            $this->assertEquals($expected['valid_from'], $result->fields['valid_from']);
            $this->assertEquals($expected['valid_to'], $result->fields['valid_to']);
        }

        // Sample 04 (Arabic): Not expected to pass fully with Tesseract
        $arabicPng = "{$this->fixturesDir}/04_arabic.png";
        if (file_exists($arabicPng)) {
            $arResult = $extractor->extract($arabicPng);
            if ($arResult->success) {
                // Assert only that required-field confidence is low or fields are null (never wrong values with high confidence)
                $civilConf = $arResult->confidences['civil_id'] ?? 0.0;
                $this->assertTrue(
                    $civilConf < 0.8 || $arResult->fields['civil_id'] === '12345678' || $arResult->fields['civil_id'] === null,
                    'Sample 04 must not return incorrect Civil ID with high confidence.'
                );
            }
        }
    }

    #[Test]
    public function it_runs_permits_ocr_check_command_cleanly(): void
    {
        $this->artisan('permits:ocr-check')
            ->expectsOutputToContain('RMSC Permit Local OCR Health & Diagnostics')
            ->expectsOutputToContain('Configured Binary Path')
            ->assertExitCode(0);
    }
}
