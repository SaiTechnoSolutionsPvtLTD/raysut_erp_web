<?php

namespace App\Http\Controllers\Sales\Retail;

use App\Http\Controllers\Controller;
use App\Models\PermitVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermitVerificationController extends Controller
{
    /**
     * Stream screenshot preview from private disk with policy authorization check.
     * Never exposes a public URL.
     */
    public function screenshot(Request $request, PermitVerification $verification)
    {
        $this->authorize('viewScreenshot', $verification);

        if (empty($verification->screenshot_path)) {
            abort(404, 'No screenshot attached to this permit verification.');
        }

        $fullPath = storage_path('app/private/' . $verification->screenshot_path);
        if (! File::exists($fullPath)) {
            abort(404, 'Screenshot file not found on private storage.');
        }

        $mime = File::mimeType($fullPath) ?: 'image/jpeg';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Export permit verifications to CSV (masked Civil ID unless authorized).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', PermitVerification::class);

        $user = auth()->user();
        $canViewFullCivilId = $user->can('viewFullCivilId', PermitVerification::class);

        $query = PermitVerification::with(['retailSale', 'branch', 'counter', 'enteredBy'])
            ->orderBy('verified_at', 'desc');

        if (! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            $accessibleBranchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter();
            $query->whereIn('branch_id', $accessibleBranchIds);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="oman-permit-verifications-' . date('Ymd-His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query, $canViewFullCivilId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Verification ID',
                'Receipt No',
                'Verified At',
                'Branch',
                'Counter',
                'Cashier',
                'Civil ID',
                'Holder Name',
                'Permit No',
                'Annual Limit (OMR)',
                'Remaining Amount (OMR)',
                'Controlled Total (OMR)',
                'Method',
                'Confidence',
                'Status',
            ]);

            $query->chunk(200, function ($verifications) use ($handle, $canViewFullCivilId) {
                foreach ($verifications as $v) {
                    fputcsv($handle, [
                        $v->id,
                        $v->retailSale?->receipt_no ?? '-',
                        $v->verified_at ? $v->verified_at->format('Y-m-d H:i') : '-',
                        $v->branch?->branch_name ?? '-',
                        $v->counter?->counter_code ?? '-',
                        $v->enteredBy?->full_name ?? '-',
                        $canViewFullCivilId ? $v->civil_id : $v->masked_civil_id,
                        $v->holder_name,
                        $v->permit_no ?? '-',
                        $v->annual_limit !== null ? number_format($v->annual_limit, 3) : '-',
                        $v->remaining_amount !== null ? number_format($v->remaining_amount, 3) : '-',
                        number_format($v->controlled_total, 3),
                        $v->entry_method,
                        $v->extraction_confidence !== null ? round($v->extraction_confidence * 100) . '%' : '-',
                        $v->status,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
