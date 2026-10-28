<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Paginated, filterable audit trail.
     */
    public function index(Request $request): View
    {
<<<<<<< HEAD
        [$logs, $filters] = $this->filteredLogs($request);

        $logs = $logs->with('actor')->latest()->paginate(30)->withQueryString();
=======
        $filters = $request->only(['q', 'module', 'action', 'outcome', 'from', 'to']);

        $logs = AuditLog::with('actor')
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('action', 'like', "%{$term}%")
                        ->orWhere('module', 'like', "%{$term}%")
                        ->orWhere('actor_email', 'like', "%{$term}%")
                        ->orWhere('target_type', 'like', "%{$term}%")
                        ->orWhere('note', 'like', "%{$term}%");
                });
            })
            ->when($filters['module'] ?? null, fn ($q, $module) => $q->where('module', $module))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['outcome'] ?? null, fn ($q, $outcome) => $q->where('outcome', $outcome))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()))
            ->latest()
            ->paginate(30)
            ->withQueryString();
>>>>>>> origin/main

        $modules = AuditLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', compact('logs', 'filters', 'modules', 'actions'));
    }

    /**
     * Single audit record with before/after diff.
     */
    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('actor');

<<<<<<< HEAD
        // Per-record activity tab: other events against the same target,
        // and other actions by the same actor, recent first.
        $relatedTarget = AuditLog::with('actor')
            ->where('target_type', $auditLog->target_type)
            ->where('target_id', $auditLog->target_id)
            ->where('id', '!=', $auditLog->id)
            ->latest()->limit(10)->get();

        $relatedActor = $auditLog->actor_id
            ? AuditLog::with('actor')
                ->where('actor_id', $auditLog->actor_id)
                ->where('id', '!=', $auditLog->id)
                ->latest()->limit(10)->get()
            : collect();

        return view('admin.audit.show', [
            'log'           => $auditLog,
            'relatedTarget' => $relatedTarget,
            'relatedActor'  => $relatedActor,
        ]);
    }

    /**
     * CSV export of the filtered audit trail (capped at 5000 rows).
     */
    public function exportAudit(Request $request): StreamedResponse
    {
        [$logs, $filters] = $this->filteredLogs($request);
        $count = min(5000, (clone $logs)->count());

        return response()->streamDownload(function () use ($logs, $count) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Actor', 'Action', 'Module', 'Target', 'Outcome', 'IP', 'Note']);

            $logs->latest()->limit($count)->get()
                ->each(fn ($log) => fputcsv($out, [
                    $log->created_at?->toDateTimeString(),
                    $log->actor_email,
                    $log->action,
                    $log->module,
                    class_basename((string) $log->target_type).($log->target_id ? '#'.$log->target_id : ''),
                    $log->outcome,
                    $log->ip_address,
                    \Illuminate\Support\Str::limit((string) $log->note, 200),
                ]));

            fclose($out);
        }, 'audit-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * CSV export of the filtered security event stream (capped at 5000 rows).
     */
    public function exportSecurityEvents(Request $request): StreamedResponse
    {
        [$events, $filters] = $this->filteredEvents($request);
        $count = min(5000, (clone $events)->count());

        return response()->streamDownload(function () use ($events, $count) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Email', 'Event', 'Outcome', 'IP', 'Description']);

            $events->latest()->limit($count)->get()
                ->each(fn ($event) => fputcsv($out, [
                    $event->created_at?->toDateTimeString(),
                    $event->email,
                    $event->event_type,
                    $event->outcome,
                    $event->ip_address,
                    \Illuminate\Support\Str::limit((string) $event->description, 200),
                ]));

            fclose($out);
        }, 'security-events-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Revoke a staff session (removes it from the active list and invalidates
     * the stored session when the driver keeps sessions in the database).
     */
    public function revokeSession(Request $request, ActiveSession $session)
    {
        $this->audit->record('security.session.revoke', $session->user()->first(), null, [
            'active_session_id' => $session->id,
            'was_current'       => $request->session()->getId() === $session->session_id,
        ]);

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('id', $session->session_id)->delete();
        }

        $session->delete();

        return back()->with('success', 'Session revoked — that device will need to sign in again.');
    }

    /**
     * Shared filter builder for the audit index + CSV export.
     */
    private function filteredLogs(Request $request): array
    {
        $filters = $request->only(['q', 'module', 'action', 'outcome', 'from', 'to']);

        $logs = AuditLog::query()
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('action', 'like', "%{$term}%")
                        ->orWhere('module', 'like', "%{$term}%")
                        ->orWhere('actor_email', 'like', "%{$term}%")
                        ->orWhere('target_type', 'like', "%{$term}%")
                        ->orWhere('note', 'like', "%{$term}%");
                });
            })
            ->when($filters['module'] ?? null, fn ($q, $module) => $q->where('module', $module))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['outcome'] ?? null, fn ($q, $outcome) => $q->where('outcome', $outcome))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()));

        return [$logs, $filters];
    }

    /**
     * Shared filter builder for the security-events index + CSV export.
     */
    private function filteredEvents(Request $request): array
    {
        $filters = $request->only(['q', 'event_type', 'outcome', 'from', 'to']);

        $events = SecurityEvent::query()
=======
        return view('admin.audit.show', ['log' => $auditLog]);
    }

    /**
     * Authentication / security event stream.
     */
    public function securityEvents(Request $request): View
    {
        $filters = $request->only(['q', 'event_type', 'outcome', 'from', 'to']);

        $events = SecurityEvent::with('user')
>>>>>>> origin/main
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('email', 'like', "%{$term}%")
                        ->orWhere('event_type', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('ip_address', 'like', "%{$term}%");
                });
            })
            ->when($filters['event_type'] ?? null, fn ($q, $type) => $q->where('event_type', $type))
            ->when($filters['outcome'] ?? null, fn ($q, $outcome) => $q->where('outcome', $outcome))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($from)->startOfDay()))
<<<<<<< HEAD
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()));

        return [$events, $filters];
    }

    /**
     * Authentication / security event stream.
     */
    public function securityEvents(Request $request): View
    {
        [$stream, $filters] = $this->filteredEvents($request);

        $events = $stream->with('user')->latest()->paginate(30)->withQueryString();
=======
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()))
            ->latest()
            ->paginate(30)
            ->withQueryString();
>>>>>>> origin/main

        $eventTypes = SecurityEvent::query()->distinct()->orderBy('event_type')->pluck('event_type');

        return view('admin.audit.security-events', compact('events', 'filters', 'eventTypes'));
    }

    /**
     * Currently signed-in sessions.
     */
    public function activeSessions(Request $request): View
    {
        $sessions = ActiveSession::with('user')
            ->when($request->input('q'), function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('ip_address', 'like', "%{$term}%")
                        ->orWhere('device', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($u) use ($term) {
                            $u->where('email', 'like', "%{$term}%")
                                ->orWhere('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                        });
                });
            })
            ->orderByDesc('last_activity_at')
            ->paginate(30)
            ->withQueryString();

        $staleMinutes = 120;

        return view('admin.audit.active-sessions', compact('sessions', 'staleMinutes'));
    }
}
