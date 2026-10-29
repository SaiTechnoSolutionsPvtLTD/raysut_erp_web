<?php

namespace App\Services\Permit\Drivers;

use App\Services\Permit\PermitExtractionResult;
use App\Services\Permit\PermitScreenshotExtractor;
use Illuminate\Http\UploadedFile;

class NullPermitExtractor implements PermitScreenshotExtractor
{
    public function extract(UploadedFile $image): PermitExtractionResult
    {
        return PermitExtractionResult::empty();
    }
}
