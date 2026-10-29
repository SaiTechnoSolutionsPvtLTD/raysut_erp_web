<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'audit_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'module_name',
        'action',
        'action_type',
        'record_table',
        'entity_type',
        'record_id',
        'entity_id',
        'details_json',
        'ip_address',
    ];

    protected $casts = [
        'details_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function setActionTypeAttribute($value): void
    {
        $this->attributes['action'] = $value;
    }

    public function setEntityTypeAttribute($value): void
    {
        $this->attributes['record_table'] = $value;
    }

    public function setEntityIdAttribute($value): void
    {
        $this->attributes['record_id'] = $value;
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            if (empty($log->module_name)) {
                $log->module_name = 'System, Security & Setup';
            }
            if (empty($log->action)) {
                $log->action = 'ACTION';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function getFormattedSummaryAttribute(): string
    {
        $userName = $this->user?->full_name ?? 'System Seeder';
        $tableName = \Illuminate\Support\Str::headline(\Illuminate\Support\Str::singular($this->record_table));
        $actionVerb = match ($this->action) {
            'CREATE' => 'created new',
            'UPDATE' => 'updated',
            'DELETE' => 'deleted',
            default => strtolower($this->action),
        };

        $recordStr = $this->record_id ? " #{$this->record_id}" : '';
        $timeStr = $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '';

        return "{$userName} {$actionVerb} {$tableName}{$recordStr} at {$timeStr}";
    }
}
