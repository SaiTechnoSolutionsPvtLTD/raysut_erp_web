<?php

namespace App\Services\Permit;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class PermitTextParser
{
    /**
     * Parse Tesseract TSV output into a PermitExtractionResult.
     */
    public function parseTsv(string $tsv): PermitExtractionResult
    {
        $rawLines = explode("\n", trim($tsv));
        if (empty($rawLines)) {
            return PermitExtractionResult::empty();
        }

        // Parse TSV rows into structured word lines
        $header = str_getcsv(array_shift($rawLines), "\t");
        $headerMap = array_flip($header);

        $lineGroups = [];
        $fullTextSnippets = [];

        foreach ($rawLines as $rowStr) {
            $rowStr = trim($rowStr);
            if ($rowStr === '') {
                continue;
            }

            $cols = str_getcsv($rowStr, "\t");
            $level = (int) ($cols[$headerMap['level'] ?? 0] ?? 0);
            $conf = (float) ($cols[$headerMap['conf'] ?? 10] ?? -1);
            $text = trim((string) ($cols[$headerMap['text'] ?? 11] ?? ''));

            // Tesseract level 5 represents word items
            if ($level === 5 && $text !== '') {
                $blockNum = (int) ($cols[$headerMap['block_num'] ?? 2] ?? 0);
                $parNum = (int) ($cols[$headerMap['par_num'] ?? 3] ?? 0);
                $lineNum = (int) ($cols[$headerMap['line_num'] ?? 4] ?? 0);
                $lineKey = "{$blockNum}_{$parNum}_{$lineNum}";

                if (! isset($lineGroups[$lineKey])) {
                    $lineGroups[$lineKey] = [
                        'words' => [],
                    ];
                }

                $lineGroups[$lineKey]['words'][] = [
                    'text' => $text,
                    'conf' => max(0, $conf),
                ];
                $fullTextSnippets[] = $text;
            }
        }

        $structuredLines = [];
        foreach ($lineGroups as $group) {
            $words = $group['words'];
            $lineText = implode(' ', array_column($words, 'text'));
            $structuredLines[] = [
                'text' => $lineText,
                'words' => $words,
            ];
        }

        $rawSnippet = implode(' ', array_slice($fullTextSnippets, 0, 50));

        return $this->parseLines($structuredLines, $rawSnippet);
    }

    /**
     * Parse plain text string into a PermitExtractionResult.
     */
    public function parseText(string $text): PermitExtractionResult
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $structured = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $words = preg_split('/\s+/u', $line);
            $wordList = array_map(fn($w) => ['text' => $w, 'conf' => 90.0], $words);

            $structured[] = [
                'text' => $line,
                'words' => $wordList,
            ];
        }

        return $this->parseLines($structured, mb_substr(trim($text), 0, 300));
    }

    /**
     * Polymorphic parser accepting TSV, plain text, or pre-structured line array.
     */
    public function parse(string|array $input): PermitExtractionResult
    {
        if (is_array($input)) {
            return $this->parseLines($input);
        }

        if (str_contains($input, "\t") && (str_contains($input, 'level') || str_contains($input, 'page_num'))) {
            return $this->parseTsv($input);
        }

        return $this->parseText($input);
    }

    /**
     * Core extraction logic operating on lines with word confidence details.
     *
     * @param array<int, array{text: string, words: array<int, array{text: string, conf: float}>}> $lines
     */
    public function parseLines(array $lines, ?string $rawSnippet = null): PermitExtractionResult
    {
        $normalizedLines = [];
        $rawSnippets = [];

        foreach ($lines as $idx => $lineItem) {
            $rawText = is_array($lineItem) ? ($lineItem['text'] ?? '') : (string) $lineItem;
            $words = is_array($lineItem) ? ($lineItem['words'] ?? []) : [];

            if (empty($words) && $rawText !== '') {
                $words = array_map(fn($w) => ['text' => $w, 'conf' => 90.0], preg_split('/\s+/u', $rawText));
            }

            $cleanText = static::normalizeString($rawText);
            $normalizedWords = [];
            foreach ($words as $w) {
                $normalizedWords[] = [
                    'text' => static::normalizeString($w['text']),
                    'conf' => (float) ($w['conf'] ?? 80.0),
                ];
            }

            $normalizedLines[] = [
                'index' => $idx,
                'text' => $cleanText,
                'words' => $normalizedWords,
            ];

            if ($cleanText !== '') {
                $rawSnippets[] = $cleanText;
            }
        }

        if (empty($rawSnippet)) {
            $rawSnippet = implode(' | ', array_slice($rawSnippets, 0, 10));
        }

        $labels = config('rmsc.permit_extraction.labels', [
            'en' => [
                'civil_id' => ['Civil ID', 'Civil No', 'ID Number', 'Resident ID', 'Civil Number'],
                'holder_name' => ['Full Name', 'Holder Name', 'Name', 'Permit Holder'],
                'permit_no' => ['Permit Number', 'Permit No', 'Permit #', 'Reference No'],
                'status' => ['Permit Status', 'Status'],
                'valid_from' => ['Valid From', 'Issue Date', 'Start Date'],
                'valid_to' => ['Valid To', 'Expiry Date', 'End Date', 'Expires'],
                'annual_limit' => ['Annual Limit', 'Yearly Quota', 'Total Quota', 'Authorized Limit'],
                'used_amount' => ['Used Amount', 'Consumed Amount', 'Total Used', 'Quota Used'],
                'remaining_amount' => ['Remaining Balance', 'Remaining Quota', 'Remaining Amount', 'Remaining', 'Balance'],
                'last_updated' => ['Last Updated', 'Verification Date', 'Verified At', 'Updated On'],
            ],
            'ar' => [
                'civil_id' => ['الرقم المدني', 'رقم البطاقة', 'المدني'],
                'holder_name' => ['الاسم الكامل', 'اسم صاحب التصريح', 'اسم حامل التصريح', 'الاسم'],
                'permit_no' => ['رقم التصريح', 'رقم الترخيص'],
                'status' => ['حالة التصريح', 'الحالة'],
                'valid_from' => ['صالح من', 'تاريخ الإصدار', 'من تاريخ'],
                'valid_to' => ['صالح حتى', 'تاريخ الانتهاء', 'إلى تاريخ'],
                'annual_limit' => ['الحد السنوي', 'الحصة السنوية', 'إجمالي الحصة'],
                'used_amount' => ['المبلغ المستخدم', 'الحصة المستخدمة', 'المستخدم'],
                'remaining_amount' => ['الرصيد المتبقي', 'الحصة المتبقية', 'المتبقي'],
                'last_updated' => ['آخر تحديث', 'تاريخ التحقق'],
            ],
        ]);

        $genericWords = config('rmsc.permit_extraction.generic_words', [
            'details', 'detail', 'information', 'info', 'status', 'summary',
            'permit', 'holder', 'page', 'portal', 'home', 'services', 'print',
            'تفاصيل', 'معلومات', 'حالة', 'ملخص', 'طباعة', 'الرئيسية', 'الخدمات',
        ]);
        $stoplist = array_values(array_unique(array_map(fn($w) => mb_strtolower(trim(static::normalizeString($w))), $genericWords)));

        // Flatten all labels per field across languages for cross-field label collision checks
        $allFieldLabels = [];
        foreach (['en', 'ar'] as $lang) {
            foreach ($labels[$lang] ?? [] as $fieldKey => $candidateLabels) {
                if (! isset($allFieldLabels[$fieldKey])) {
                    $allFieldLabels[$fieldKey] = [];
                }
                foreach ($candidateLabels as $cl) {
                    $allFieldLabels[$fieldKey][] = $cl;
                }
            }
        }

        $extractedFields = [
            'civil_id' => null,
            'holder_name' => null,
            'permit_no' => null,
            'status' => null,
            'annual_limit' => null,
            'used_amount' => null,
            'remaining_amount' => null,
            'valid_from' => null,
            'valid_to' => null,
            'verified_at' => null,
        ];

        $fieldConfidences = [
            'civil_id' => 0.0,
            'holder_name' => 0.0,
            'permit_no' => 0.0,
            'annual_limit' => 0.0,
            'used_amount' => 0.0,
            'remaining_amount' => 0.0,
            'valid_from' => 0.0,
            'valid_to' => 0.0,
            'verified_at' => 0.0,
        ];

        $warnings = [];

        // 1. Label-driven extraction per field in priority order
        $fieldOrder = [
            'holder_name',
            'civil_id',
            'permit_no',
            'status',
            'valid_from',
            'valid_to',
            'annual_limit',
            'used_amount',
            'remaining_amount',
            'last_updated',
        ];

        foreach ($fieldOrder as $fieldKey) {
            $candidateLabels = [];
            foreach (['en', 'ar'] as $lang) {
                foreach ($labels[$lang][$fieldKey] ?? [] as $cl) {
                    if (! in_array($cl, $candidateLabels, true)) {
                        $candidateLabels[] = $cl;
                    }
                }
            }

            foreach ($candidateLabels as $candidateLabel) {
                $normLabel = static::normalizeString($candidateLabel);
                $labelLen = mb_strlen($normLabel);

                foreach ($normalizedLines as $lineItem) {
                    $lineText = $lineItem['text'];
                    if ($lineText === '') {
                        continue;
                    }

                    $trimmedLine = ltrim($lineText, " \t\n\r\0\x0B:-=|>·•*#");

                    // Label only matches when it starts the line
                    if (mb_stripos($trimmedLine, $normLabel) !== 0) {
                        continue;
                    }

                    // Ensure label boundary (delimiter or end-of-string) so it doesn't match a longer word
                    $charAfter = mb_substr($trimmedLine, $labelLen, 1);
                    if ($charAfter !== '' && ! preg_match('/[\s:\-=\|>·•*#\/]/u', $charAfter)) {
                        continue;
                    }

                    $remainder = mb_substr($trimmedLine, $labelLen);
                    $cleanRemainder = trim($remainder, " \t\n\r\0\x0B:-=|>·•*#");

                    // Skip heading lines: lines with no value after the label, or whose remainder is only generic words
                    if (static::isHeadingLine($cleanRemainder, $stoplist)) {
                        continue;
                    }

                    // A value must not be a label of another field and must not be in the stoplist
                    if (in_array(mb_strtolower($cleanRemainder), $stoplist, true)) {
                        continue;
                    }

                    if (static::isLabelOfAnotherField($cleanRemainder, $fieldKey, $allFieldLabels)) {
                        continue;
                    }

                    // Semantic validation per field
                    $parsed = $this->parseFieldByType($fieldKey, $cleanRemainder, $stoplist);
                    if ($parsed === null) {
                        // Try next candidate when value fails validation instead of returning it
                        continue;
                    }

                    $extractedFields[$fieldKey] = $parsed;
                    $matchedWords = static::findWordsForSubstr($lineItem['words'], $cleanRemainder);
                    $fieldConfidences[$fieldKey] = static::calcWordConfidence($matchedWords);
                    break 2; // Found valid candidate for this field, move to next field
                }
            }
        }

        // 2. Fallbacks when labels were not located
        // 2a. Civil ID fallback: standalone 6-12 digit number near the word 'Civil' or its Arabic equivalent
        if (empty($extractedFields['civil_id'])) {
            foreach ($normalizedLines as $lineItem) {
                $text = $lineItem['text'];
                $isNearCivil = (bool) preg_match('/(?:civil|مدني|بطاقة|resident)/i', $text);
                if (preg_match('/(?<![A-Za-z0-9\-\/])(\d{6,12})(?![A-Za-z0-9\-\/])/', $text, $matches)) {
                    if ($isNearCivil || trim($text) === $matches[1]) {
                        $candidateId = static::parseCivilId($matches[1]);
                        if ($candidateId !== null) {
                            $extractedFields['civil_id'] = $candidateId;
                            $matchedWords = static::findWordsForSubstr($lineItem['words'], $candidateId);
                            $fieldConfidences['civil_id'] = round(static::calcWordConfidence($matchedWords) * 0.85, 4);
                            break;
                        }
                    }
                }
            }
        }

        // 2b. Amounts fallback via 'OMR <number>' or '<number> OMR' or 'ر.ع <number>' in document order
        $missingAmounts = empty($extractedFields['annual_limit'])
            || empty($extractedFields['used_amount'])
            || empty($extractedFields['remaining_amount']);

        if ($missingAmounts) {
            $foundAmounts = [];
            foreach ($normalizedLines as $lineItem) {
                if (preg_match_all('/(?:(?:OMR|ر\.?ع\.?)\s*([0-9]+(?:\.[0-9]{1,3})?)|([0-9]+(?:\.[0-9]{1,3})?)\s*(?:OMR|ر\.?ع\.?))/iu', $lineItem['text'], $amtMatches, PREG_SET_ORDER)) {
                    foreach ($amtMatches as $m) {
                        $rawAmt = ($m[1] ?? '') !== '' ? $m[1] : ($m[2] ?? '');
                        if ($rawAmt === '') {
                            continue;
                        }
                        $amtVal = static::parseAmount($rawAmt);
                        if ($amtVal !== null) {
                            $matchedWords = static::findWordsForSubstr($lineItem['words'], (string) $amtVal);
                            $foundAmounts[] = [
                                'value' => $amtVal,
                                'conf' => round(static::calcWordConfidence($matchedWords) * 0.85, 4),
                            ];
                        }
                    }
                }
            }

            if (empty($extractedFields['annual_limit']) && isset($foundAmounts[0]) && $foundAmounts[0]['value'] > 0) {
                $extractedFields['annual_limit'] = $foundAmounts[0]['value'];
                $fieldConfidences['annual_limit'] = $foundAmounts[0]['conf'];
            }
            if (empty($extractedFields['used_amount']) && isset($foundAmounts[1])) {
                $extractedFields['used_amount'] = $foundAmounts[1]['value'];
                $fieldConfidences['used_amount'] = $foundAmounts[1]['conf'];
            }
            if (empty($extractedFields['remaining_amount']) && isset($foundAmounts[2])) {
                $extractedFields['remaining_amount'] = $foundAmounts[2]['value'];
                $fieldConfidences['remaining_amount'] = $foundAmounts[2]['conf'];
            }
        }

        // 2c. Status fallback: scan lines for known status keywords if not matched by label
        if (empty($extractedFields['status'])) {
            foreach ($normalizedLines as $lineItem) {
                $statusCandidate = static::parseStatus($lineItem['text']);
                if ($statusCandidate !== null) {
                    $extractedFields['status'] = $statusCandidate;
                    $matchedWords = static::findWordsForSubstr($lineItem['words'], $lineItem['text']);
                    $fieldConfidences['status'] = round(static::calcWordConfidence($matchedWords) * 0.85, 4);
                    break;
                }
            }
        }

        // 3. Mathematical Derivation and Consistency Checks
        // 3a. Derive remaining_amount = annual_limit - used_amount when remaining is missing
        if ($extractedFields['remaining_amount'] === null
            && $extractedFields['annual_limit'] !== null
            && $extractedFields['annual_limit'] > 0
            && $extractedFields['used_amount'] !== null
        ) {
            $derivedRemaining = round($extractedFields['annual_limit'] - $extractedFields['used_amount'], 3);
            if ($derivedRemaining >= 0 && $derivedRemaining <= $extractedFields['annual_limit']) {
                $extractedFields['remaining_amount'] = $derivedRemaining;
                $fieldConfidences['remaining_amount'] = round(min($fieldConfidences['annual_limit'], $fieldConfidences['used_amount']) * 0.9, 4);
            }
        }

        // 3b. Amount semantic check: remaining <= annual_limit when both exist and annual_limit > 0
        if ($extractedFields['annual_limit'] !== null
            && $extractedFields['remaining_amount'] !== null
            && $extractedFields['annual_limit'] > 0
        ) {
            if ($extractedFields['remaining_amount'] > ($extractedFields['annual_limit'] + 0.0001)) {
                $extractedFields['remaining_amount'] = null;
                $fieldConfidences['remaining_amount'] = 0.0;
                $warnings[] = "Remaining quota could not be read reliably.";
            }
        }

        // 3c. Math consistency check: remaining != annual_limit - used_amount
        if ($extractedFields['annual_limit'] !== null
            && $extractedFields['used_amount'] !== null
            && $extractedFields['remaining_amount'] !== null
        ) {
            $expectedRemaining = round($extractedFields['annual_limit'] - $extractedFields['used_amount'], 3);
            if (abs($expectedRemaining - $extractedFields['remaining_amount']) > 0.001) {
                $fieldConfidences['annual_limit'] = 0.5000;
                $fieldConfidences['used_amount'] = 0.5000;
                $fieldConfidences['remaining_amount'] = 0.5000;
                $warnings[] = sprintf(
                    "Quota calculation mismatch: Annual Limit (%.3f) - Used (%.3f) != Remaining (%.3f). Please review amounts.",
                    $extractedFields['annual_limit'],
                    $extractedFields['used_amount'],
                    $extractedFields['remaining_amount']
                );
            }
        }

        // 4. Dates semantic check: valid_to >= valid_from
        if (! empty($extractedFields['valid_from']) && ! empty($extractedFields['valid_to'])) {
            try {
                $from = Carbon::parse($extractedFields['valid_from']);
                $to = Carbon::parse($extractedFields['valid_to']);
                if ($to->lt($from)) {
                    $extractedFields['valid_to'] = null;
                    $fieldConfidences['valid_to'] = 0.0;
                    $warnings[] = "Permit validity dates could not be read reliably.";
                }
            } catch (Exception) {
                $extractedFields['valid_to'] = null;
                $fieldConfidences['valid_to'] = 0.0;
                $warnings[] = "Permit validity dates could not be read reliably.";
            }
        }

        // 5. Status mapping & Expiry Warnings
        if ($extractedFields['status'] !== null) {
            $statusUpper = strtoupper($extractedFields['status']);
            if (in_array($statusUpper, ['EXPIRED', 'SUSPENDED'])) {
                $warnings[] = "Extracted permit status is {$statusUpper}.";
            }
        }

        if (! empty($extractedFields['valid_to'])) {
            try {
                $expiryDate = Carbon::parse($extractedFields['valid_to']);
                if ($expiryDate->isPast() && ! $expiryDate->isToday()) {
                    $warnings[] = "Extracted permit valid_to date ({$extractedFields['valid_to']}) has expired.";
                }
            } catch (Exception) {
                // Ignore parse errors on warning
            }
        }

        // 6. Default verified_at to current timestamp if absent
        if (empty($extractedFields['verified_at'])) {
            $extractedFields['verified_at'] = now()->format('Y-m-d H:i:s');
            $fieldConfidences['verified_at'] = 0.95;
        }

        // 7. Check required fields AFTER validation
        // A field that fails validation returns null with confidence 0 and adds a warning ("Holder name could not be read reliably"), never a guessed value.
        if ($extractedFields['holder_name'] === null) {
            $fieldConfidences['holder_name'] = 0.0;
            if (! collect($warnings)->contains(fn($w) => str_contains($w, 'Holder name'))) {
                $warnings[] = "Holder name could not be read reliably.";
            }
        }

        if ($extractedFields['civil_id'] === null) {
            $fieldConfidences['civil_id'] = 0.0;
            if (! collect($warnings)->contains(fn($w) => str_contains($w, 'Civil ID'))) {
                $warnings[] = "Civil ID could not be read reliably.";
            }
        }

        if ($extractedFields['remaining_amount'] === null) {
            $fieldConfidences['remaining_amount'] = 0.0;
            if (! collect($warnings)->contains(fn($w) => str_contains($w, 'Remaining quota'))) {
                $warnings[] = "Remaining quota could not be read reliably.";
            }
        }

        // 8. Overall Confidence Calculation
        // Overall = mean of REQUIRED fields (civil_id, holder_name, remaining_amount) AFTER validation
        $requiredKeys = ['civil_id', 'holder_name', 'remaining_amount'];
        $requiredConfs = [];
        $hasMissingRequired = false;

        foreach ($requiredKeys as $rk) {
            if ($extractedFields[$rk] === null || $fieldConfidences[$rk] <= 0.0) {
                $hasMissingRequired = true;
                $fieldConfidences[$rk] = 0.0;
            }
            $requiredConfs[] = $fieldConfidences[$rk];
        }

        $meanRequired = count($requiredConfs) > 0 ? (array_sum($requiredConfs) / count($requiredConfs)) : 0.0;
        $overallConfidence = $hasMissingRequired ? min($meanRequired, 0.5000) : $meanRequired;

        return new PermitExtractionResult(
            fields: $extractedFields,
            confidences: $fieldConfidences,
            overallConfidence: round($overallConfidence, 4),
            rawSnippet: $rawSnippet,
            success: true,
            warnings: $warnings,
            driver: 'tesseract'
        );
    }

    /**
     * Determine if a line remainder indicates a heading line with no plausible value.
     */
    public static function isHeadingLine(string $cleanRemainder, array $stoplist): bool
    {
        if ($cleanRemainder === '') {
            return true;
        }

        $norm = mb_strtolower($cleanRemainder);
        if (in_array($norm, $stoplist, true)) {
            return true;
        }

        $tokens = preg_split('/[\s:\-=\|>·•*#&,\/]+/u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            return true;
        }

        // Check if all tokens are generic stoplist words or conjunctions
        $nonGeneric = array_filter(
            $tokens,
            fn($t) => ! in_array($t, $stoplist, true) && ! in_array($t, ['and', 'و', '&', 'of', 'for', 'the'], true)
        );

        return empty($nonGeneric);
    }

    /**
     * Check if a candidate value collides with a label of another field.
     *
     * @param array<string, array<int, string>> $allFieldLabels
     */
    public static function isLabelOfAnotherField(string $val, string $currentFieldKey, array $allFieldLabels): bool
    {
        $normVal = mb_strtolower(trim(static::normalizeString($val)));
        if ($normVal === '') {
            return false;
        }

        foreach ($allFieldLabels as $otherFieldKey => $labels) {
            if ($otherFieldKey === $currentFieldKey) {
                continue;
            }
            foreach ($labels as $label) {
                $normLabel = mb_strtolower(trim(static::normalizeString($label)));
                if ($normVal === $normLabel) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Type-specific parsers for each field with semantic validation.
     */
    protected function parseFieldByType(string $type, string $raw, array $stoplist = []): mixed
    {
        $clean = trim($raw);
        if ($clean === '') {
            return null;
        }

        return match ($type) {
            'civil_id' => static::parseCivilId($clean),
            'holder_name' => static::parseHolderName($clean, $stoplist),
            'permit_no' => static::parsePermitNo($clean),
            'status' => static::parseStatus($clean),
            'annual_limit' => ($amt = static::parseAmount($clean)) !== null && $amt > 0 ? $amt : null,
            'used_amount', 'remaining_amount' => static::parseAmount($clean),
            'valid_from', 'valid_to' => static::parseDate($clean),
            'last_updated', 'verified_at' => static::parseDateTime($clean),
            default => $clean,
        };
    }

    /**
     * Map Latin glyphs commonly produced by Tesseract OCR when reading Arabic-Indic numerals.
     */
    public static function transliterateVisualArabicDigits(string $str): string
    {
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $str = str_replace($eastern, $western, $str);
        $str = str_replace($persian, $western, $str);

        $visualMap = [
            '\\' => '1', '|' => '1', '/' => '1', 'I' => '1', 'l' => '1',
            'Y' => '2', 'y' => '2',
            'P' => '3', 'p' => '3', 'F' => '3', 'f' => '3', 'r' => '3',
            'E' => '4', 'e' => '4',
            'O' => '5', 'o' => '5',
            'W' => '6', 'w' => '6',
            'V' => '7', 'v' => '7', 'u' => '7',
            'A' => '8',
            'a' => '9', 'q' => '9',
        ];

        return strtr($str, $visualMap);
    }

    /**
     * Parse and validate Civil ID: digits only, matching configured regex length.
     */
    public static function parseCivilId(string $text): ?string
    {
        $cleaned = trim($text);

        // Normalize Eastern Arabic and Persian digits
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $converted = str_replace($eastern, $western, $cleaned);
        $converted = str_replace($persian, $western, $converted);

        $regex = config('rmsc.permit_civil_id_regex', '/^\d{6,12}$/');
        // If digits only, check length
        if (preg_match('/^\d+$/', $converted)) {
            if (preg_match($regex, $converted)) {
                return $converted;
            }
            return null;
        }

        // Visual transliteration fallback for Tesseract OCR font confusion
        $transliterated = static::transliterateVisualArabicDigits($cleaned);
        $digitsOnly = preg_replace('/\D/', '', $transliterated);
        if (preg_match($regex, $digitsOnly)) {
            return $digitsOnly;
        }

        return null;
    }

    /**
     * Parse and validate Holder Name: no digits, min words, letters only, not in stoplist.
     */
    public static function parseHolderName(string $text, array $stoplist = []): ?string
    {
        $cleaned = trim($text);

        // No digits allowed
        if (preg_match('/\d/u', $cleaned)) {
            return null;
        }

        // Letters only (Latin or Arabic) plus spaces, apostrophes, hyphens, and dots
        if (! preg_match('/^[\p{L}\s\'\x{2019}\-\.]+$/u', $cleaned)) {
            return null;
        }

        // Collapse whitespace
        $collapsed = trim(preg_replace('/\s+/u', ' ', $cleaned));

        // Token count check
        $tokens = preg_split('/\s+/u', $collapsed, -1, PREG_SPLIT_NO_EMPTY);
        $minTokens = (int) config('rmsc.permit_extraction.min_name_tokens', 2);
        if (count($tokens) < $minTokens) {
            return null;
        }

        // Stoplist check
        if (empty($stoplist)) {
            $genericWords = config('rmsc.permit_extraction.generic_words', [
                'details', 'detail', 'information', 'info', 'status', 'summary',
                'permit', 'holder', 'page', 'portal', 'home', 'services', 'print',
                'تفاصيل', 'معلومات', 'حالة', 'ملخص', 'طباعة', 'الرئيسية', 'الخدمات',
            ]);
            $stoplist = array_values(array_unique(array_map(fn($w) => mb_strtolower(trim(static::normalizeString($w))), $genericWords)));
        }

        $lowerClean = mb_strtolower($collapsed);
        if (in_array($lowerClean, $stoplist, true)) {
            return null;
        }

        // Check if all tokens are in stoplist
        $allStop = true;
        foreach ($tokens as $t) {
            if (! in_array(mb_strtolower($t), $stoplist, true)) {
                $allStop = false;
                break;
            }
        }
        if ($allStop) {
            return null;
        }

        return $collapsed;
    }

    public static function parsePermitNo(string $text): ?string
    {
        if (preg_match('/[A-Z0-9]+(?:-[A-Z0-9]+)+/i', $text, $matches)) {
            return strtoupper($matches[0]);
        }

        $cleaned = trim(preg_replace('/[^a-zA-Z0-9\-]/', '', $text));
        return strlen($cleaned) >= 4 ? strtoupper($cleaned) : null;
    }

    public static function parseStatus(string $text): ?string
    {
        $lower = mb_strtolower($text);

        if (str_contains($lower, 'active') || str_contains($lower, 'valid') || str_contains($lower, 'فعال') || str_contains($lower, 'ساري')) {
            return 'ACTIVE';
        }

        if (str_contains($lower, 'expired') || str_contains($lower, 'منتهي')) {
            return 'EXPIRED';
        }

        if (str_contains($lower, 'suspended') || str_contains($lower, 'موقوف') || str_contains($lower, 'معلق')) {
            return 'SUSPENDED';
        }

        return null;
    }

    /**
     * Parse and validate amount: parseable number with at most 3 decimals.
     */
    public static function parseAmount(string $text): ?float
    {
        // Normalize Eastern Arabic and Persian digits
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $normalized = str_replace($eastern, $western, $text);
        $normalized = str_replace($persian, $western, $normalized);

        // Normalize Arabic decimal and thousands separators
        $normalized = str_replace(['٫', '٬', '،', ','], ['.', '', '', ''], $normalized);

        // Strip currency words
        $stripped = preg_replace('/(?:OMR|ر\.?ع\.?|RO)/iu', '', $normalized);
        $stripped = trim($stripped);

        // Must be a parseable number with at most 3 decimal places
        if (preg_match('/^\d+(?:\.\d{1,3})?$/', $stripped)) {
            return round((float) $stripped, 3);
        }

        // Visual transliteration fallback for Arabic amounts
        $trans = static::transliterateVisualArabicDigits($stripped);
        $cleanAmt = preg_replace('/[\-_]+/', '0', $trans);
        if (preg_match('/([0-9]+(?:\.[0-9]{1,3})?)/u', $cleanAmt, $m)) {
            $val = (float) $m[1];
            if ($val > 0 && $val <= 100000) {
                return round($val, 3);
            }
        }

        return null;
    }

    /**
     * Parse and validate calendar date.
     */
    public static function parseDate(string $text): ?string
    {
        $dayFirst = (bool) config('rmsc.permit_extraction.date_day_first', true);

        // Normalize Eastern Arabic and Persian digits
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $normalized = str_replace($eastern, $western, $text);
        $normalized = str_replace($persian, $western, $normalized);

        // Normalize delimiters to slash
        $normalized = trim(str_replace(['-', '.', '٫'], ['/', '/', '/'], $normalized));

        // Format DD/MM/YYYY or MM/DD/YYYY
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/', $normalized, $matches)) {
            $part1 = (int) $matches[1];
            $part2 = (int) $matches[2];
            $year = (int) $matches[3];

            $day = $dayFirst ? $part1 : $part2;
            $month = $dayFirst ? $part2 : $part1;

            if ($dayFirst && $part1 > 12 && $part2 <= 12) {
                $day = $part1;
                $month = $part2;
            } elseif (! $dayFirst && $part2 > 12 && $part1 <= 12) {
                $day = $part2;
                $month = $part1;
            }

            if (! checkdate($month, $day, $year)) {
                return null;
            }

            try {
                return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            } catch (Exception) {
                return null;
            }
        }

        // Format YYYY/MM/DD
        if (preg_match('/\b(\d{4})\/(\d{1,2})\/(\d{1,2})\b/', $normalized, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (! checkdate($month, $day, $year)) {
                return null;
            }

            try {
                return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            } catch (Exception) {
                return null;
            }
        }

        try {
            $parsed = Carbon::parse($text);
            return $parsed->format('Y-m-d');
        } catch (Exception) {
            return null;
        }
    }

    public static function parseDateTime(string $text): ?string
    {
        try {
            return Carbon::parse($text)->format('Y-m-d H:i:s');
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Normalize Arabic/Persian digits, separators, invisible marks, and whitespace.
     */
    public static function normalizeString(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // Convert Eastern Arabic and Persian digits to Western 0-9
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $str = str_replace($eastern, $western, $value);
        $str = str_replace($persian, $western, $str);

        // Normalize Arabic decimal and thousands separators
        $str = str_replace(['٫', '٬', '،'], ['.', ',', ','], $str);

        // Strip Arabic diacritics (tashkeel), tatweel, RTL/LTR marks and invisible control characters
        $str = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $str);

        // Collapse whitespace
        $str = preg_replace('/\s+/u', ' ', $str);

        return trim($str);
    }

    /**
     * Compute average confidence of word items that form the extracted value.
     */
    protected static function calcWordConfidence(array $words): float
    {
        if (empty($words)) {
            return 0.8500;
        }

        $confs = array_map(fn($w) => (float) ($w['conf'] ?? 80.0), $words);
        $mean = array_sum($confs) / count($confs);

        return round(min(1.0, max(0.0, $mean / 100.0)), 4);
    }

    /**
     * Find word objects corresponding to a substring in the line.
     */
    protected static function findWordsForSubstr(array $words, string $substr): array
    {
        $tokens = preg_split('/\s+/u', trim($substr));
        $matched = [];

        foreach ($words as $w) {
            foreach ($tokens as $t) {
                if ($t !== '' && mb_stripos($w['text'], $t) !== false) {
                    $matched[] = $w;
                    break;
                }
            }
        }

        return $matched ?: $words;
    }
}
