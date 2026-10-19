<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::updated(function (Model $model) {
            if ($model->shouldLogActivity()) {
                app(AuditService::class)->record(
                    'update',
                    $model,
                    $model->getOriginal(),
                    $model->getAttributes(),
                );
            }
        });

        static::created(function (Model $model) {
            if ($model->shouldLogActivity()) {
                app(AuditService::class)->record(
                    'create',
                    $model,
                    null,
                    $model->getAttributes(),
                );
            }
        });

        static::deleted(function (Model $model) {
            if ($model->shouldLogActivity()) {
                app(AuditService::class)->record(
                    $model->isForceDeleting() ? 'force_delete' : 'delete',
                    $model,
                    null,
                    $model->getAttributes(),
                );
            }
        });
    }

    /**
     * Hook for models to opt out of audit logging per-event.
     */
    protected function shouldLogActivity(): bool
    {
        if (app()->runningInConsole()) {
            // Seeders & migrations should not pollute the audit log.
            return false;
        }
        return true;
    }
}
