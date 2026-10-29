<?php

namespace App\Services\Permit;

use Illuminate\Http\UploadedFile;

interface PermitScreenshotExtractor
{
    /**
     * Extract Oman government permit verification details from an uploaded screenshot.
     */
    public function extract(UploadedFile $image): PermitExtractionResult;
}
