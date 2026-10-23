<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Paginated, filterable audit trail.
     */
    public function index(Request $request): View
    {
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

        return view('admin.audit.show', ['log' => $auditLog]);
    }

    /**
     * Authentication / security event stream.
     */
    public function securityEvents(Request $request): View
    {
        $filters = $request->only(['q', 'event_type', 'outcome', 'from', 'to']);

        $events = SecurityEvent::with('user')
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
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()))
            ->latest()
            ->paginate(30)
            ->withQueryString();

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
