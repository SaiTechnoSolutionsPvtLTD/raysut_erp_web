<?php

namespace App\Services\Permit\Drivers;

use App\Services\Permit\PermitExtractionResult;
use App\Services\Permit\PermitScreenshotExtractor;
use Illuminate\Http\UploadedFile;

class FakePermitExtractor implements PermitScreenshotExtractor
{
    protected ?PermitExtractionResult $customResult = null;
    protected ?string $simulatedError = null;
    public int $callCount = 0;

    public function setResult(PermitExtractionResult $result): self
    {
        $this->customResult = $result;
        return $this;
    }

    public function setError(string $errorMessage): self
    {
        $this->simulatedError = $errorMessage;
        return $this;
    }

    public function extract(UploadedFile $image): PermitExtractionResult
    {
        $this->callCount++;

        if ($this->simulatedError) {
            return PermitExtractionResult::failed($this->simulatedError);
        }

        if ($this->customResult) {
            return $this->customResult;
        }

        return new PermitExtractionResult(
            fields: [
                'civil_id' => '10459823',
                'holder_name' => 'Ahmed Al-Harthy',
                'permit_no' => 'ROP-10459823-26',
                'annual_limit' => 500.000,
                'used_amount' => 50.000,
                'remaining_amount' => 450.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => now()->format('Y-m-d H:i:s'),
            ],
            confidences: [
                'civil_id' => 0.98,
                'holder_name' => 0.95,
                'permit_no' => 0.92,
                'annual_limit' => 0.99,
                'used_amount' => 0.95,
                'remaining_amount' => 0.97,
                'valid_from' => 0.90,
                'valid_to' => 0.90,
                'verified_at' => 0.96,
            ],
            overallConfidence: 0.95,
            rawSnippet: 'Sultanate of Oman Royal Oman Police Liquor Permit Verification Remaining Quota: 450.000 OMR',
            success: true
        );
    }
}
