<?php

namespace App\Services\Permit;

use App\Models\PermitVerification;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PermitVerificationService
{
    /**
     * Validate permit verification payload according to RMSC config rules.
     *
     * @throws ValidationException
     */
    public function validatePayload(array $data, float $controlledTotal = 0.000): array
    {
        $errors = [];

        // 1. Civil ID
        $civilId = trim((string) ($data['civil_id'] ?? ''));
        $regex = config('rmsc.permit_civil_id_regex', '/^\d{6,12}$/');
        if (empty($civilId)) {
            $errors['civil_id'][] = 'The Civil ID field is required for permit-controlled items.';
        } elseif (! preg_match($regex, $civilId)) {
            $errors['civil_id'][] = 'Civil ID must be 6 to 12 digits per Oman compliance rules.';
        }

        // 2. Holder Name
        $holderName = trim((string) ($data['holder_name'] ?? ''));
        if (empty($holderName)) {
            $errors['holder_name'][] = 'The permit holder name is required.';
        }

        // 3. Verified At
        $verifiedAtRaw = $data['verified_at'] ?? null;
        $verifiedAt = null;
        if (empty($verifiedAtRaw)) {
            $errors['verified_at'][] = 'The verification timestamp is required.';
        } else {
            try {
                $verifiedAt = Carbon::parse($verifiedAtRaw);
                $now = now();
                $maxHours = (int) config('rmsc.permit_verification_max_age_hours', 24);

                if ($verifiedAt->gt($now->copy()->addMinutes(5))) {
                    $errors['verified_at'][] = 'Verification date cannot be in the future.';
                } elseif ($verifiedAt->lt($now->copy()->subHours($maxHours))) {
                    $errors['verified_at'][] = "Verification is stale (older than {$maxHours} hours). A fresh government verification is required.";
                }
            } catch (Exception) {
                $errors['verified_at'][] = 'The verification date format is invalid.';
            }
        }

        // 4. Remaining Amount
        $remaining = isset($data['remaining_amount']) && $data['remaining_amount'] !== '' ? (float) $data['remaining_amount'] : null;
        if ($remaining === null) {
            $errors['remaining_amount'][] = 'The remaining permit quota amount is required.';
        } elseif ($remaining < 0) {
            $errors['remaining_amount'][] = 'Remaining quota amount cannot be negative.';
        }

        // 5. Optional Amounts
        $annualLimit = isset($data['annual_limit']) && $data['annual_limit'] !== '' ? (float) $data['annual_limit'] : null;
        if ($annualLimit !== null && $annualLimit < 0) {
            $errors['annual_limit'][] = 'Annual limit cannot be negative.';
        }

        $usedAmount = isset($data['used_amount']) && $data['used_amount'] !== '' ? (float) $data['used_amount'] : null;
        if ($usedAmount !== null && $usedAmount < 0) {
            $errors['used_amount'][] = 'Used amount cannot be negative.';
        }

        // 6. Validity Date Range
        $validFrom = ! empty($data['valid_from']) ? Carbon::parse($data['valid_from'])->startOfDay() : null;
        $validTo = ! empty($data['valid_to']) ? Carbon::parse($data['valid_to'])->endOfDay() : null;

        if ($validFrom && $validTo && $validTo->lt($validFrom)) {
            $errors['valid_to'][] = 'Permit valid-to date must be on or after the valid-from date.';
        }

        $today = now()->startOfDay();
        if ($validFrom && $today->lt($validFrom)) {
            $errors['valid_from'][] = 'The permit is not yet effective.';
        }

        if ($validTo && $today->gt($validTo)) {
            $errors['valid_to'][] = 'The permit has expired.';
        }

        // 7. Attestation
        $attested = filter_var($data['attested'] ?? $data['attestation'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (! $attested) {
            $errors['attested'][] = 'You must attest that you have verified these details on the Oman government portal.';
        }

        // 8. Remaining Quota vs Controlled Cart Total
        $blockWhenExceeds = (bool) config('rmsc.permit_block_when_exceeds', true);
        if ($blockWhenExceeds && $remaining !== null && $controlledTotal > 0.001) {
            if ($remaining < ($controlledTotal - 0.001)) {
                $errors['remaining_amount'][] = sprintf(
                    'Order total for permit-controlled items (OMR %.3f) exceeds remaining quota (OMR %.3f). Checkout blocked.',
                    $controlledTotal,
                    $remaining
                );
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'civil_id' => $civilId,
            'holder_name' => $holderName,
            'permit_no' => ! empty($data['permit_no']) ? trim((string) $data['permit_no']) : null,
            'annual_limit' => $annualLimit,
            'used_amount' => $usedAmount,
            'remaining_amount' => $remaining,
            'valid_from' => $validFrom?->format('Y-m-d'),
            'valid_to' => $validTo?->format('Y-m-d'),
            'verified_at' => $verifiedAt?->format('Y-m-d H:i:s'),
            'entry_method' => strtoupper($data['entry_method'] ?? 'MANUAL') === 'SCREENSHOT' ? 'SCREENSHOT' : 'MANUAL',
            'screenshot_token' => $data['screenshot_token'] ?? null,
            'screenshot_path' => $data['screenshot_path'] ?? null,
            'extraction_confidence' => isset($data['extraction_confidence']) ? (float) $data['extraction_confidence'] : null,
            'extraction_raw' => $data['extraction_raw'] ?? null,
            'fields_edited_after_extraction' => $data['fields_edited_after_extraction'] ?? null,
            'attested' => true,
        ];
    }

    /**
     * Store an uploaded screenshot in private storage, strip EXIF metadata, and return temporary token.
     */
    public function storeScreenshot(UploadedFile $file, int $branchId): array
    {
        $realMime = $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (! in_array($realMime, $allowed)) {
            throw ValidationException::withMessages([
                'screenshot' => ['The uploaded file must be a valid image (JPEG, PNG, or WebP).'],
            ]);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'screenshot' => ['The screenshot size must not exceed 5 MB.'],
            ]);
        }

        $ext = match ($realMime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $year = date('Y');
        $month = date('m');
        $uuid = (string) Str::uuid();
        $fileName = "{$uuid}.{$ext}";

        // Private directory path
        $relativeDir = "permit-screenshots/{$branchId}/{$year}/{$month}";
        $targetDir = storage_path("app/private/{$relativeDir}");

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0750, true);
        }

        $targetFullPath = "{$targetDir}/{$fileName}";
        $targetRelativePath = "{$relativeDir}/{$fileName}";

        // Strip EXIF metadata using GD if available
        $this->stripExifAndSave($file->getRealPath(), $targetFullPath, $realMime);

        // Generate temporary token
        $token = 'pvt_' . Str::random(32);
        Cache::put("permit_token_{$token}", [
            'relative_path' => $targetRelativePath,
            'full_path' => $targetFullPath,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'created_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        return [
            'token' => $token,
            'path' => $targetRelativePath,
        ];
    }

    /**
     * Strip EXIF metadata by re-encoding image through GD or direct copy.
     */
    protected function stripExifAndSave(string $sourcePath, string $targetPath, string $mime): void
    {
        if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
            try {
                if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                    $img = @imagecreatefromjpeg($sourcePath);
                    if ($img !== false) {
                        imagejpeg($img, $targetPath, 92);
                        imagedestroy($img);
                        return;
                    }
                } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng') && function_exists('imagepng')) {
                    $img = @imagecreatefrompng($sourcePath);
                    if ($img !== false) {
                        imagepng($img, $targetPath);
                        imagedestroy($img);
                        return;
                    }
                } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
                    $img = @imagecreatefromwebp($sourcePath);
                    if ($img !== false) {
                        imagewebp($img, $targetPath);
                        imagedestroy($img);
                        return;
                    }
                }
            } catch (Exception) {
                // Fallback to copy if GD encounters memory or format issues
            }
        }

        copy($sourcePath, $targetPath);
    }

    /**
     * Find a reusable permit verification for the same Civil ID within the same shift and day
     * that still has remaining amount to cover the controlled total.
     */
    public function findReusableVerification(string $civilId, int $shiftId, float $controlledTotal): ?PermitVerification
    {
        $hash = PermitVerification::generateHash($civilId);

        $latest = PermitVerification::where('cashier_shift_id', $shiftId)
            ->where('civil_id_hash', $hash)
            ->whereDate('created_at', now()->toDateString())
            ->where('status', 'USED')
            ->latest('id')
            ->first();

        if (! $latest) {
            return null;
        }

        // Must still have enough remaining to cover current cart
        if ($latest->remaining_amount !== null && (float) $latest->remaining_amount >= $controlledTotal) {
            return $latest;
        }

        return null;
    }

    /**
     * Purge orphaned screenshot uploads older than 24h that were never attached to a sale.
     */
    public function purgeOrphanScreenshots(): int
    {
        $baseDir = storage_path('app/private/permit-screenshots');
        if (! File::exists($baseDir)) {
            return 0;
        }

        $count = 0;
        $files = File::allFiles($baseDir);
        $cutoff = now()->subHours(24)->getTimestamp();

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                $relativePath = 'permit-screenshots/' . str_replace('\\', '/', $file->getRelativePathname());
                $isAttached = PermitVerification::where('screenshot_path', $relativePath)->exists();

                if (! $isAttached) {
                    File::delete($file->getRealPath());
                    $count++;
                }
            }
        }

        return $count;
    }
}
