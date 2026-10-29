<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\WholesaleDocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    /**
     * Generate sequential document number under pessimistic locking:
     * {PREFIX}-{BRANCH}-{YYYY}-{00001}
     *
     * @param int $branchId
     * @param string $docType (WQ, SO, DN, INV)
     * @param string|null $branchCode
     * @return string
     */
    public function generateNumber(int $branchId, string $docType, ?string $branchCode = null): string
    {
        if (! $branchCode) {
            $branch = Branch::find($branchId);
            $branchCode = $branch ? $branch->branch_code : 'HQ';
        }

        $code = str_replace('BR-', '', strtoupper($branchCode));
        $year = now()->format('Y');

        return DB::transaction(function () use ($branchId, $docType, $year, $code) {
            // Ensure sequence row exists
            WholesaleDocumentSequence::firstOrCreate(
                [
                    'branch_id' => $branchId,
                    'doc_type' => $docType,
                    'sequence_year' => $year,
                ],
                [
                    'last_sequence' => 0,
                ]
            );

            // Lock row for update
            $seqRow = WholesaleDocumentSequence::where('branch_id', $branchId)
                ->where('doc_type', $docType)
                ->where('sequence_year', $year)
                ->lockForUpdate()
                ->first();

            $nextSeq = ($seqRow ? (int) $seqRow->last_sequence : 0) + 1;

            $seqRow->last_sequence = $nextSeq;
            $seqRow->save();

            $seqFormatted = str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);

            return "{$docType}-{$code}-{$year}-{$seqFormatted}";
        });
    }
}
