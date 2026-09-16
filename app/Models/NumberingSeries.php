<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class NumberingSeries extends Model
{
    use Auditable;

    protected $table = 'numbering_series';
    protected $primaryKey = 'series_id';
    public $timestamps = false;

    protected $fillable = [
        'document_type',
        'prefix',
        'next_number',
        'fiscal_year',
    ];

    /**
     * Generate next formatted document number (e.g., PO-2026-00001).
     */
    public static function getNextFormatted(string $documentType): string
    {
        $series = static::where('document_type', $documentType)->first();
        if (! $series) {
            return $documentType . '-00001';
        }

        $num = str_pad((string)$series->next_number, 5, '0', STR_PAD_LEFT);
        $prefix = $series->prefix ? rtrim($series->prefix, '-') . '-' : '';
        $year = $series->fiscal_year ? rtrim($series->fiscal_year, '-') . '-' : '';

        // Increment next number
        $series->increment('next_number');

        return "{$prefix}{$year}{$num}";
    }
}
