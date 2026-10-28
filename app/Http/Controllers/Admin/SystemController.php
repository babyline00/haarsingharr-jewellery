<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;

class SystemController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * System control panel: health, backups, migrations, failed queue jobs,
     * maintenance mode and the schedule status.
     */
    public function index(): View
    {
        return view('admin.system.index', [
            'health'      => $this->health(),
            'backups'     => $this->backups(),
            'zipAvailable' => class_exists('ZipArchive'),
            'migrations'  => $this->migrationStatus(),
            'failedJobs'  => $this->failedJobs(),
            'schedule'    => $this->scheduleList(),
            'down'        => app()->isDownForMaintenance(),
        ]);
    }

    // -----------------------------------------------------------------------
    // Backups
    // -----------------------------------------------------------------------
    public function createBackup(Request $request)
    {
        if (! class_exists('ZipArchive')) {
            return back()->with('error',
                'Backup creation needs the PHP zip extension, which is missing on this server. '
                .'Install/enable ext-zip (cPanel: MultiPHP INI editor → zip), then retry.');
        }

        try {
            Artisan::call('backup:run', ['--disable-notifications' => true]);

            $this->audit->record('system.backup.create', StoreSetting::class);

            return back()->with('success', 'Backup created: '.trim(Artisan::output()));
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: '.Str::limit($e->getMessage(), 160));
        }
    }

    public function downloadBackup(string $file)
    {
        $backup = $this->findBackup($file);

        if (! $backup || ! $backup->exists()) {
            abort(404);
        }

        $this->audit->record('system.backup.download', StoreSetting::class, null, ['file' => $file]);

        $stream = $backup->stream();

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.basename($backup->path()).'"',
        ]);
    }

    public function destroyBackup(Request $request, string $file)
    {
        $backup = $this->findBackup($file);

        if (! $backup || ! $backup->exists()) {
            abort(404);
        }

        $backup->delete();
        $this->audit->record('system.backup.delete', StoreSetting::class, null, ['file' => $file]);

        return back()->with('success', 'Backup deleted.');
    }

    private function findBackup(string $file): ?Backup
    {
        $safe = basename($file); // no traversal

        return $this->destination()?->backups()->first(
            fn ($backup) => basename($backup->path()) === $safe,
        );
    }

    private function destination(): ?BackupDestination
    {
        $disks = (array) config('backup.backup.destination.disks', ['local']);
        $name = (string) config('backup.backup.name', 'haarsingharr-backup');

        try {
            return BackupDestination::create($disks[0], $name);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function backups(): array
    {
        try {
            return $this->destination()?->backups()
                ->map(fn ($backup) => [
                    'name'   => basename($backup->path()),
                    'size'   => number_format($backup->sizeInBytes() / 1024 / 1024, 2).' MB',
                    'date'   => $backup->date()->format('M j, Y H:i'),
                    'exists' => $backup->exists(),
                ])
                ->filter(fn ($b) => $b['exists'])
                ->values()
                ->all() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    // -----------------------------------------------------------------------
    // Maintenance mode
    // -----------------------------------------------------------------------
    public function toggleMaintenance(Request $request)
    {
        $request->validate(['confirm' => 'required|in:MAINTENANCE']);

        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            $this->audit->record('system.maintenance.up', StoreSetting::class);

            return back()->with('success', 'Site is live again.');
        }

        $secret = Str::random(24);

        Artisan::call('down', ['--secret' => $secret, '--render' => 'errors.503']);
        $this->audit->record('system.maintenance.down', StoreSetting::class);

        return back()->with(
            'success',
            "Maintenance mode enabled. Bypass URL when signed out: ".url('/')."/{$secret}",
        );
    }

    // -----------------------------------------------------------------------
    // Queue failed jobs
    // -----------------------------------------------------------------------
    public function retryFailedJob(Request $request, string $id)
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            $this->audit->record('system.queue.retry', StoreSetting::class, null, ['job' => $id]);

            return back()->with('success', 'Job pushed back onto its queue.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Retry failed: '.Str::limit($e->getMessage(), 160));
        }
    }

    public function deleteFailedJob(Request $request, string $id)
    {
        try {
            Artisan::call('queue:forget', ['id' => $id]);
            $this->audit->record('system.queue.forget', StoreSetting::class, null, ['job' => $id]);

            return back()->with('success', 'Failed job deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Delete failed: '.Str::limit($e->getMessage(), 160));
        }
    }

    public function flushFailedJobs(Request $request)
    {
        Artisan::call('queue:flush');

        $this->audit->record('system.queue.flush', StoreSetting::class);

        return back()->with('success', 'Failed-jobs list flushed.');
    }

    /**
     * @return array<int, object>
     */
    private function failedJobs(): array
    {
        try {
            return DB::table('failed_jobs')->orderByDesc('id')->limit(25)->get()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    // -----------------------------------------------------------------------
    // Health helpers
    // -----------------------------------------------------------------------

    /**
     * @return array<int, array{label: string, value: string, ok: bool|null, note?: string}>
     */
    private function health(): array
    {
        $checks = [];

        $checks[] = $this->check('PHP version', PHP_VERSION, version_compare(PHP_VERSION, '8.3.', '>='), '8.3+ recommended');
        $checks[] = $this->check('Laravel', app()->version(), true);

        try {
            DB::select('select 1');
            $checks[] = $this->check('Database', DB::connection()->getDatabaseName(), true);
        } catch (\Throwable $e) {
            $checks[] = $this->check('Database', 'unreachable', false, $e->getMessage());
        }

        try {
            $probe = 'health-probe-'.Str::random(6);
            Cache::put($probe, 1, 5);
            $ok = Cache::get($probe) === 1;
            Cache::forget($probe);
            $checks[] = $this->check('Cache', config('cache.default'), $ok);
        } catch (\Throwable) {
            $checks[] = $this->check('Cache', config('cache.default'), false);
        }

        $checks[] = $this->check('Storage symlink', file_exists(public_path('storage')) ? 'linked' : 'missing', file_exists(public_path('storage')));

        try {
            $free = @disk_free_space(storage_path());
            $total = (float) @disk_total_space(storage_path());
            $pct = $total > 0 ? round($free / $total * 100, 1) : 0.0;
            $checks[] = $this->check(
                'Disk free',
                $free ? number_format($free / 1024 / 1024 / 1024, 1).' GB ('.$pct.'%)' : 'unknown',
                $pct === 0.0 || $pct > 10.0,
                '10% free minimum',
            );
        } catch (\Throwable) {
            $checks[] = $this->check('Disk free', 'unknown', null);
        }

        foreach (['curl', 'mbstring', 'openssl', 'pdo', 'gd'] as $ext) {
            $has = extension_loaded($ext);
            $checks[] = $this->check("Extension: {$ext}", $has ? 'loaded' : 'missing', $has);
        }

        $checks[] = $this->check(
            'Extension: zip',
            extension_loaded('zip') ? 'loaded' : 'missing',
            extension_loaded('zip'),
            'needed for archive backups',
        );

        $checks[] = $this->check('Environment', app()->environment(), app()->environment() === 'production' ? config('app.debug') === false : true);
        $checks[] = $this->check('Timezone', config('app.timezone'), true);
        $checks[] = $this->check('Mail mailer', config('mail.default'), config('mail.default') !== '' ?: null);
        $checks[] = $this->check('Queue connection', config('queue.default'), true);

        return $checks;
    }

    /**
     * @return array{label: string, value: string, ok: bool|null, note?: string}
     */
    private function check(string $label, mixed $value, ?bool $ok = null, ?string $note = null): array
    {
        return [
            'label' => $label,
            'value' => (string) ($value ?? 'N/A'),
            'ok'    => $ok,
            'note'  => $note,
        ];
    }

    private function migrationStatus(): string
    {
        try {
            Artisan::call('migrate:status', ['--no-interaction' => true]);

            return trim(Artisan::output());
        } catch (\Throwable $e) {
            return 'Migration status unavailable: '.$e->getMessage();
        }
    }

    private function scheduleList(): string
    {
        try {
            Artisan::call('schedule:list', ['--no-interaction' => true]);

            return trim(Artisan::output());
        } catch (\Throwable $e) {
            return 'Schedule listing unavailable: '.$e->getMessage();
        }
    }
}
