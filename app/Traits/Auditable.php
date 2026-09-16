<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAuditEvent($model, 'CREATE', $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            static::logAuditEvent($model, 'UPDATE', $changes);
        });

        static::deleted(function ($model) {
            static::logAuditEvent($model, 'DELETE', ['id' => $model->getKey()]);
        });
    }

    protected static function logAuditEvent($model, string $action, array $details): void
    {
        try {
            $user = Auth::user();
            $moduleName = property_exists($model, 'moduleName') ? $model->moduleName : 'System, Security & Setup';

            AuditLog::create([
                'user_id' => $user?->user_id,
                'module_name' => $moduleName,
                'action' => $action,
                'record_table' => $model->getTable(),
                'record_id' => $model->getKey(),
                'details_json' => json_encode($details),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Silently ignore audit errors to prevent breaking main transaction
        }
    }
}
