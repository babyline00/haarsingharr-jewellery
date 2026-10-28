<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\StoreSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneAuditLogs extends Command
{
    protected $signature = 'haarsingharr:prune-audit';

    protected $description = 'Delete audit log records older than the configured retention window (default 365 days).';

    public function handle(): int
    {
        $days = (int) (StoreSetting::where('key', 'audit_retention_days')->first()?->value ?: 365);
        $cutoff = Carbon::now()->subDays(max(30, $days));

        // Retention pruning is a hard delete — old audit rows must leave the
        // database, not linger in the trash. Also purge records already soft-
        // deleted and past the window.
        $deleted = AuditLog::where('created_at', '<', $cutoff)->forceDelete();
        $trashed = AuditLog::onlyTrashed()->where('deleted_at', '<', $cutoff)->forceDelete();
        $total = $deleted + $trashed;

        $this->info("Pruned {$total} audit record(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
