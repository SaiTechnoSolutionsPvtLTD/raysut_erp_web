<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleDocumentSequence extends Model
{
    protected $table = 'wholesale_document_sequences';

    protected $fillable = [
        'branch_id',
        'doc_type',
        'sequence_year',
        'last_sequence',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
