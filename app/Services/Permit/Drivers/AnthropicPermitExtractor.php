<?php

namespace App\Services\Permit\Drivers;

use App\Services\Permit\PermitExtractionResult;
use App\Services\Permit\PermitScreenshotExtractor;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicPermitExtractor implements PermitScreenshotExtractor
{
    /**
     * Convert Eastern Arabic-Indic numerals (٠-٩) and Persian numerals (۰-۹) to Western digits (0-9).
     */
    public static function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $normalized = str_replace($eastern, $western, $value);
        return str_replace($persian, $western, $normalized);
    }

    /**
     * Normalize date string into Y-m-d format.
     */
    public static function normalizeDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $cleaned = static::normalizeDigits(trim($value));
        try {
            return Carbon::parse($cleaned)->format('Y-m-d');
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Normalize amount into 3-decimal float.
     */
    public static function normalizeAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $str = static::normalizeDigits((string) $value);
        // Remove currency symbols, commas or extra whitespace
        $cleaned = preg_replace('/[^0-9.]/', '', $str);
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return null;
        }

        return round((float) $cleaned, 3);
    }

    public function extract(UploadedFile $image): PermitExtractionResult
    {
        $apiKey = config('rmsc.permit_extraction.anthropic.api_key');
        if (empty($apiKey)) {
            return PermitExtractionResult::failed('Anthropic API key is not configured.');
        }

        $model = config('rmsc.permit_extraction.anthropic.model', 'claude-3-5-sonnet-20241022');
        $maxTokens = (int) config('rmsc.permit_extraction.anthropic.max_tokens', 1024);
        $timeout = (int) config('rmsc.permit_extraction.anthropic.timeout', 30);

        $mimeType = $image->getMimeType() ?: 'image/jpeg';
        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
            $mimeType = 'image/jpeg';
        }

        $base64Data = base64_encode(file_get_contents($image->getRealPath()));

        $prompt = <<<PROMPT
You are a government document inspection AI. The image is a screenshot of an official Sultanate of Oman Liquor Permit verification webpage (either in Arabic or English).
Carefully extract the permit details from the screenshot.

Return ONLY a valid JSON object (with NO extra markdown, NO explanation, NO code blocks) with the following exact keys:
{
  "civil_id": string or null (Civil ID or Resident Card number),
  "holder_name": string or null (Full legal name of the permit holder),
  "permit_no": string or null (Official permit reference number),
  "annual_limit": number or null (Annual authorized quota in OMR),
  "used_amount": number or null (Quota consumed so far in OMR),
  "remaining_amount": number or null (Remaining quota available in OMR),
  "valid_from": string (YYYY-MM-DD) or null,
  "valid_to": string (YYYY-MM-DD) or null,
  "verified_at": string (YYYY-MM-DD HH:MM:SS) or null (Date/time visible on the verification page or current time),
  "confidences": {
    "civil_id": float (0.0 to 1.0),
    "holder_name": float,
    "permit_no": float,
    "annual_limit": float,
    "used_amount": float,
    "remaining_amount": float,
    "valid_from": float,
    "valid_to": float,
    "verified_at": float
  },
  "overall_confidence": float (0.0 to 1.0),
  "raw_text_snippet": string or null (Short snippet of text detected)
}
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout($timeout)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $base64Data,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Anthropic extraction API returned error', [
                    'status' => $response->status(),
                ]);
                return PermitExtractionResult::failed('OCR extraction service failed with HTTP ' . $response->status());
            }

            $body = $response->json();
            $content = $body['content'][0]['text'] ?? '';

            // Clean any backticks if returned
            $cleanedJson = trim($content);
            if (str_starts_with($cleanedJson, '```')) {
                $cleanedJson = preg_replace('/^```(?:json)?\s*/i', '', $cleanedJson);
                $cleanedJson = preg_replace('/\s*```$/', '', $cleanedJson);
            }

            $data = json_decode($cleanedJson, true);
            if (! is_array($data)) {
                return PermitExtractionResult::failed('Malformed output from extraction provider.');
            }

            $rawFields = is_array($data['fields'] ?? null) ? array_merge($data, $data['fields']) : $data;

            // Normalization
            $fields = [
                'civil_id' => static::normalizeDigits($rawFields['civil_id'] ?? null),
                'holder_name' => ! empty($rawFields['holder_name']) ? trim((string) $rawFields['holder_name']) : null,
                'permit_no' => static::normalizeDigits($rawFields['permit_no'] ?? null),
                'annual_limit' => static::normalizeAmount($rawFields['annual_limit'] ?? null),
                'used_amount' => static::normalizeAmount($rawFields['used_amount'] ?? null),
                'remaining_amount' => static::normalizeAmount($rawFields['remaining_amount'] ?? null),
                'valid_from' => static::normalizeDate($rawFields['valid_from'] ?? null),
                'valid_to' => static::normalizeDate($rawFields['valid_to'] ?? null),
                'verified_at' => ! empty($rawFields['verified_at']) ? Carbon::parse(static::normalizeDigits($rawFields['verified_at']))->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            ];

            $confidences = is_array($data['confidences'] ?? null) ? $data['confidences'] : (is_array($rawFields['confidences'] ?? null) ? $rawFields['confidences'] : []);
            $overallConfidence = (float) ($data['overall_confidence'] ?? $rawFields['overall_confidence'] ?? 0.0);
            $rawSnippet = ! empty($data['raw_text_snippet']) ? (string) $data['raw_text_snippet'] : (! empty($data['raw_snippet']) ? (string) $data['raw_snippet'] : null);

            return new PermitExtractionResult(
                fields: $fields,
                confidences: $confidences,
                overallConfidence: $overallConfidence,
                rawSnippet: $rawSnippet,
                success: true
            );
        } catch (Exception $e) {
            Log::warning('Permit screenshot extraction exception', ['error' => $e->getMessage()]);
            return PermitExtractionResult::failed('Failed to extract permit data: ' . $e->getMessage());
        }
    }
}
