<?php

namespace App\Services\Permit;

class PermitExtractionResult
{
    /**
     * @param array<string, mixed> $fields
     * @param array<string, float> $confidences
     */
    public function __construct(
        public array $fields = [],
        public array $confidences = [],
        public float $overallConfidence = 0.0,
        public ?string $rawSnippet = null,
        public bool $success = true,
        public ?string $errorMessage = null,
        ?string $rawText = null,
        public array $warnings = [],
        public ?string $driver = null
    ) {
        if ($rawText !== null && $this->rawSnippet === null) {
            $this->rawSnippet = $rawText;
        }
    }

    public static function empty(): self
    {
        return new self(
            fields: [
                'civil_id' => null,
                'holder_name' => null,
                'permit_no' => null,
                'annual_limit' => null,
                'used_amount' => null,
                'remaining_amount' => null,
                'valid_from' => null,
                'valid_to' => null,
                'verified_at' => null,
            ],
            confidences: [],
            overallConfidence: 0.0,
            rawSnippet: null,
            success: false,
            errorMessage: 'Extraction driver returned empty result or is disabled.'
        );
    }

    public static function failed(string $error): self
    {
        return new self(
            fields: [],
            confidences: [],
            overallConfidence: 0.0,
            rawSnippet: null,
            success: false,
            errorMessage: $error
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'fields' => $this->fields,
            'confidences' => $this->confidences,
            'overall_confidence' => round($this->overallConfidence, 4),
            'raw_snippet' => $this->rawSnippet,
            'warnings' => $this->warnings,
            'driver' => $this->driver,
            'error_message' => $this->errorMessage,
        ];
    }
}
