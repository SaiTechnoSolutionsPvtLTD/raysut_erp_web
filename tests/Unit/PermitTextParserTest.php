<?php

namespace Tests\Unit;

use App\Services\Permit\PermitTextParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermitTextParserTest extends TestCase
{
    protected PermitTextParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PermitTextParser();
    }

    #[Test]
    public function it_parses_sample_01_valid_permit_from_recorded_tsv(): void
    {
        $tsv = <<<TSV
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

        $result = $this->parser->parseTsv($tsv);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertEquals('2026-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-12-31', $result->fields['valid_to']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertGreaterThanOrEqual(0.85, $result->overallConfidence);
        $this->assertEmpty($result->warnings);
    }

    #[Test]
    public function it_parses_sample_02_low_quota_and_valid(): void
    {
        // Sample 02 text fixture including heading "Permit Holder Details"
        $text = <<<TEXT
Permit Holder Details
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Permit Number: LQ-2026-004517
Permit Status: Active
Valid From: 01/01/2026
Valid To: 31/12/2026
Annual Limit: OMR 500.000
Used Amount: OMR 490.000
Remaining Balance: OMR 10.000
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(490.000, $result->fields['used_amount']);
        $this->assertEquals(10.000, $result->fields['remaining_amount']);
        $this->assertEquals('2026-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-12-31', $result->fields['valid_to']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertGreaterThanOrEqual(0.85, $result->overallConfidence);
        $this->assertEmpty($result->warnings);
    }

    #[Test]
    public function it_parses_sample_03_expired_status_and_generates_warnings(): void
    {
        $text = <<<TEXT
Permit Holder Details
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Permit Number: LQ-2026-004517
Permit Status: Expired
Valid From: 01/01/2025
Valid To: 30/06/2026
Annual Limit: OMR 500.000
Used Amount: OMR 120.500
Remaining Balance: OMR 379.500
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertEquals('EXPIRED', $result->fields['status']);
        $this->assertEquals('2025-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-06-30', $result->fields['valid_to']);
        $this->assertNotEmpty($result->warnings);
        $this->assertTrue(collect($result->warnings)->contains(fn($w) => str_contains($w, 'EXPIRED')));
    }

    #[Test]
    public function it_parses_sample_05_cropped_recorded_text(): void
    {
        $text = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Permit Number: LQ-2026-004517
Status: Active
Valid From: 01/01/2026
Valid To: 31/12/2026
Annual Limit: OMR 500.000
Used Amount: OMR 120.500
Remaining Balance: OMR 379.500
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertEquals('2026-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-12-31', $result->fields['valid_to']);
        $this->assertGreaterThanOrEqual(0.85, $result->overallConfidence);
    }

    #[Test]
    public function it_parses_sample_06_phone_photo_recorded_text_with_formatting_noise(): void
    {
        $text = <<<TEXT
=== SULTANATE OF OMAN ===
Royal Oman Police - Directorate General of Inquiries

Civil ID:   12345678
Full Name :   Ahmed Said Al Harthy
Permit No : LQ-2026-004517
Permit Status: Active
Valid From: 01/01/2026
Valid To: 31/12/2026
Annual Limit : OMR 500.000
Used Amount : OMR 120.500
Remaining Balance : OMR 379.500
Last Updated: 2026-01-15
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertEquals('2026-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-12-31', $result->fields['valid_to']);
        $this->assertGreaterThanOrEqual(0.85, $result->overallConfidence);
    }

    #[Test]
    public function heading_only_line_returns_null_holder_name_with_warning_and_capped_confidence(): void
    {
        // Line "Permit Holder Details" with no Full Name row
        $text = <<<TEXT
Permit Holder Details
Civil ID: 12345678
Permit Number: LQ-2026-004517
Annual Limit: OMR 500.000
Used Amount: OMR 120.500
Remaining Balance: OMR 379.500
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertNull($result->fields['holder_name']);
        $this->assertEquals(0.0, $result->confidences['holder_name']);
        $this->assertNotEmpty($result->warnings);
        $this->assertTrue(collect($result->warnings)->contains(fn($w) => str_contains($w, 'Holder name could not be read reliably')));
        $this->assertLessThanOrEqual(0.50, $result->overallConfidence);
    }

    #[Test]
    public function stoplist_words_single_word_names_and_names_with_digits_fail_validation(): void
    {
        // Stoplist word English
        $resDetails = $this->parser->parseText("Full Name: Details\nCivil ID: 12345678");
        $this->assertNull($resDetails->fields['holder_name']);
        $this->assertEquals(0.0, $resDetails->confidences['holder_name']);

        $resInfo = $this->parser->parseText("Full Name: Information\nCivil ID: 12345678");
        $this->assertNull($resInfo->fields['holder_name']);

        // Stoplist word Arabic
        $resAr = $this->parser->parseText("الاسم الكامل: تفاصيل\nالرقم المدني: 12345678");
        $this->assertNull($resAr->fields['holder_name']);

        // Single word name fails min_name_tokens (default 2)
        $resSingle = $this->parser->parseText("Full Name: Ahmed\nCivil ID: 12345678");
        $this->assertNull($resSingle->fields['holder_name']);

        // Name with digits fails
        $resDigits = $this->parser->parseText("Full Name: Ahmed Said 123\nCivil ID: 12345678");
        $this->assertNull($resDigits->fields['holder_name']);
    }

    #[Test]
    public function label_priority_full_name_wins_over_looser_label_regardless_of_order(): void
    {
        // Looser label comes earlier in document, but Full Name is higher priority
        $text = <<<TEXT
Permit Holder: Generic Person
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Annual Limit: OMR 500.000
Remaining Balance: OMR 500.000
TEXT;

        $result = $this->parser->parseText($text);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
    }

    #[Test]
    public function it_parses_sample_04_arabic_labels_and_normalizes_arabic_indic_digits(): void
    {
        $text = <<<TEXT
الرقم المدني: ١٢٣٤٥٦٧٨
الاسم الكامل: Ahmed Said Al Harthy
رقم التصريح: LQ-2026-004517
حالة التصريح: فعال
صالح من: 01/01/2026
صالح حتى: 31/12/2026
الحد السنوي: ٥٠٠.٠٠٠ ر.ع
المبلغ المستخدم: ١٢٠.٥٠٠ ر.ع
الرصيد المتبقي: ٣٧٩.٥٠٠ ر.ع
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('Ahmed Said Al Harthy', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
    }

    #[Test]
    public function it_parses_arabic_sample_with_arabic_holder_name_and_labels(): void
    {
        $text = <<<TEXT
الرقم المدني: ١٢٣٤٥٦٧٨
الاسم الكامل: أحمد بن سعيد الحارثي
رقم التصريح: LQ-2026-004517
حالة التصريح: فعال
صالح من: 01/01/2026
صالح حتى: 31/12/2026
الحد السنوي: ٥٠٠.٠٠٠ ر.ع
المبلغ المستخدم: ٤٩٠.٠٠٠ ر.ع
الرصيد المتبقي: ١٠.٠٠٠ ر.ع
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('أحمد بن سعيد الحارثي', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(490.000, $result->fields['used_amount']);
        $this->assertEquals(10.000, $result->fields['remaining_amount']);
        $this->assertEquals('2026-01-01', $result->fields['valid_from']);
        $this->assertEquals('2026-12-31', $result->fields['valid_to']);
        $this->assertGreaterThanOrEqual(0.85, $result->overallConfidence);
    }

    #[Test]
    public function semantic_validation_rejects_invalid_dates_and_amounts(): void
    {
        // 1. Remaining > Annual limit
        $invalidQuotaText = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Annual Limit: OMR 500.000
Remaining Balance: OMR 600.000
TEXT;
        $resQuota = $this->parser->parseText($invalidQuotaText);
        $this->assertNull($resQuota->fields['remaining_amount']);
        $this->assertEquals(0.0, $resQuota->confidences['remaining_amount']);
        $this->assertTrue(collect($resQuota->warnings)->contains(fn($w) => str_contains($w, 'Remaining quota could not be read reliably')));

        // 2. valid_to < valid_from
        $invalidDatesText = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Valid From: 31/12/2026
Valid To: 01/01/2026
Remaining Balance: OMR 500.000
TEXT;
        $resDates = $this->parser->parseText($invalidDatesText);
        $this->assertNull($resDates->fields['valid_to']);
        $this->assertEquals(0.0, $resDates->confidences['valid_to']);
        $this->assertTrue(collect($resDates->warnings)->contains(fn($w) => str_contains($w, 'Permit validity dates could not be read reliably')));
    }

    #[Test]
    public function it_normalizes_arabic_decimal_and_thousands_separators(): void
    {
        // Using Arabic decimal separator U+066B (٫) and thousand separator U+066C (٬)
        $text = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Annual Limit: OMR ۱٬۵۰۰٫۰۰۰
Used Amount: OMR ۱۲۰٫۵۰۰
Remaining Balance: OMR ۱٬۳۷۹٫۵۰۰
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertEquals(1500.000, $result->fields['annual_limit']);
        $this->assertEquals(120.500, $result->fields['used_amount']);
        $this->assertEquals(1379.500, $result->fields['remaining_amount']);
    }

    #[Test]
    public function it_converts_dates_with_day_first_by_default(): void
    {
        // 01/02/2026 with DAY FIRST must become 2026-02-01 (1st February), not 2026-01-02 (2nd January)
        config(['rmsc.permit_extraction.date_day_first' => true]);

        $text = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Valid From: 01/02/2026
Valid To: 15/08/2026
Remaining Balance: OMR 100.000
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertEquals('2026-02-01', $result->fields['valid_from']);
        $this->assertEquals('2026-08-15', $result->fields['valid_to']);
    }

    #[Test]
    public function missing_fields_return_null_with_zero_confidence_and_caps_overall(): void
    {
        $text = <<<TEXT
Permit Number: LQ-2026-004517
Annual Limit: OMR 500.000
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertNull($result->fields['civil_id']);
        $this->assertNull($result->fields['holder_name']);
        $this->assertNull($result->fields['remaining_amount']);

        $this->assertEquals(0.0, $result->confidences['civil_id']);
        $this->assertEquals(0.0, $result->confidences['holder_name']);

        // Since required fields are missing, overallConfidence must not exceed 0.5
        $this->assertLessThanOrEqual(0.50, $result->overallConfidence);
    }

    #[Test]
    public function it_derives_remaining_amount_when_limit_and_used_are_present(): void
    {
        $text = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Annual Limit: OMR 500.000
Used Amount: OMR 120.500
TEXT;

        $result = $this->parser->parseText($text);

        // Derived: 500 - 120.500 = 379.500
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertGreaterThan(0.0, $result->confidences['remaining_amount']);
    }

    #[Test]
    public function quota_math_mismatch_lowers_confidence_and_adds_warning(): void
    {
        // 500 - 100 != 200 (Expected 400)
        $text = <<<TEXT
Civil ID: 12345678
Full Name: Ahmed Said Al Harthy
Annual Limit: OMR 500.000
Used Amount: OMR 100.000
Remaining Balance: OMR 200.000
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(100.000, $result->fields['used_amount']);
        $this->assertEquals(200.000, $result->fields['remaining_amount']);

        // Confidence lowered to 0.5
        $this->assertEquals(0.5000, $result->confidences['annual_limit']);
        $this->assertEquals(0.5000, $result->confidences['used_amount']);
        $this->assertEquals(0.5000, $result->confidences['remaining_amount']);

        // Warning added
        $this->assertNotEmpty($result->warnings);
        $this->assertTrue(collect($result->warnings)->contains(fn($w) => str_contains($w, 'Quota calculation mismatch')));
    }

    #[Test]
    public function fallback_matches_apply_085_multiplier(): void
    {
        // No labels for Civil ID or amounts; just standalone texts
        $text = <<<TEXT
Government Portal Verification
Holder: Ahmed Said Al Harthy
Document civil reference 98765432
OMR 500.000
OMR 150.000
OMR 350.000
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertEquals('98765432', $result->fields['civil_id']);
        $this->assertEquals(500.000, $result->fields['annual_limit']);
        $this->assertEquals(150.000, $result->fields['used_amount']);
        $this->assertEquals(350.000, $result->fields['remaining_amount']);

        // Assert fallback confidence is discounted
        $this->assertLessThanOrEqual(0.85, $result->confidences['civil_id']);
    }

    #[Test]
    public function it_maps_bilingual_status_variants(): void
    {
        $activeEn = $this->parser->parseText("Civil ID: 12345678\nStatus: Valid");
        $this->assertEquals('ACTIVE', $activeEn->fields['status']);

        $activeAr = $this->parser->parseText("Civil ID: 12345678\nالحالة: ساري");
        $this->assertEquals('ACTIVE', $activeAr->fields['status']);

        $suspendedAr = $this->parser->parseText("Civil ID: 12345678\nحالة التصريح: موقوف");
        $this->assertEquals('SUSPENDED', $suspendedAr->fields['status']);
        $this->assertNotEmpty($suspendedAr->warnings);
    }

    #[Test]
    public function it_parses_visual_arabic_indic_transliteration_glyphs(): void
    {
        // When Tesseract with eng+ara misclassifies Arabic-Indic numerals as visually similar Latin glyphs:
        // \YPEOWVA => 12345678, rva.o-- OMR => 379.500
        $text = <<<TEXT
الرقم المدني \YPEOWVA
الاسم الكامل أحمد سعيد الحارثي
رقم التصريح LQ-2026-004517
all> التصريح فعّال
الرصيد المتبقي rva.o-- OMR
TEXT;

        $result = $this->parser->parseText($text);

        $this->assertEquals('12345678', $result->fields['civil_id']);
        $this->assertEquals('أحمد سعيد الحارثي', $result->fields['holder_name']);
        $this->assertEquals('LQ-2026-004517', $result->fields['permit_no']);
        $this->assertEquals('ACTIVE', $result->fields['status']);
        $this->assertEquals(379.500, $result->fields['remaining_amount']);
        $this->assertEmpty($result->warnings);
    }
}
