<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EnquiryReplyMail;
use App\Models\ContactEnquiry;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactEnquiryController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public const STATUSES = ['new', 'open', 'resolved', 'closed'];
    public const TYPES = ['general', 'support', 'complaint', 'wholesale', 'press'];

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'type', 'q', 'assigned']);

        $enquiries = ContactEnquiry::with('assignee')
            ->when(($filters['status'] ?? '') !== '', fn ($q, $s) => $q->where('status', $filters['status']),
                // Inbox default: anything unresolved.
                fn ($q) => $q->whereIn('status', ['new', 'open']))
            ->when($filters['type'] ?? '', fn ($q, $t) => $q->where('type', $t))
            ->when($filters['q'] ?? '', fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%")))
            ->when($filters['assigned'] ?? '', fn ($q, $val) => $val === 'me'
                ? $q->where('assigned_to', auth()->id())
                : $q->where('assigned_to', $val))
            ->orderByRaw(<<<'SQL'
                case status when 'new' then 1 when 'open' then 2
                    when 'resolved' then 3 else 4 end
            SQL)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.enquiries.index', [
            'enquiries' => $enquiries,
            'filters'   => $filters,
            'counts'    => [
                'new'      => ContactEnquiry::where('status', 'new')->count(),
                'open'     => ContactEnquiry::where('status', 'open')->count(),
                'resolved' => ContactEnquiry::where('status', 'resolved')->count(),
                'closed'   => ContactEnquiry::where('status', 'closed')->count(),
                'mine'     => ContactEnquiry::whereIn('status', ['new', 'open'])->where('assigned_to', auth()->id())->count(),
            ],
            'types'     => ContactEnquiryController::TYPES,
            'statuses'  => ContactEnquiryController::STATUSES,
            'staff'     => \App\Models\User::where('type', 'staff')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(ContactEnquiry $enquiry): View
    {
        return view('admin.enquiries.show', [
            'enquiry' => $enquiry,
            'statuses' => ContactEnquiryController::STATUSES,
            'types'    => ContactEnquiryController::TYPES,
            'staff'    => \App\Models\User::where('type', 'staff')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'in:open,resolve,close,export'],
        ]);

        if ($validated['action'] === 'export') {
            return redirect()->route('admin.enquiries.export', ['ids' => implode(',', $validated['ids'])]);
        }

        $enquiries = ContactEnquiry::whereIn('id', $validated['ids'])->get();
        $changed = 0;
        $statusMap = ['open' => 'open', 'resolve' => 'resolved', 'close' => 'closed'];

        foreach ($enquiries as $enquiry) {
            $enquiry->update(['status' => $statusMap[$validated['action']]]);
            $changed++;
        }

        $this->audit->record('enquiries.bulk.update', null, null, [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', $changed.' enquiry(ies) '.$statusMap[$validated['action']].'.');
    }

    public function assign(Request $request, ContactEnquiry $enquiry): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $was = $enquiry->assigned_to;
        $enquiry->update(['assigned_to' => $validated['assigned_to']]);

        // Claiming an unassigned enquiry also takes it out of the "new" pile.
        if ($validated['assigned_to'] && $enquiry->status === 'new') {
            $enquiry->update(['status' => 'open']);
        }

        $this->audit->record('enquiries.assign', $enquiry, ['assigned_to' => $was], [
            'assigned_to' => $enquiry->assigned_to,
        ]);

        return back()->with('success', $enquiry->assignee
            ? 'Assigned to '.$enquiry->assignee->name.'.'
            : 'Assignment cleared.');
    }

    /** Email a reply to the customer, auto-resolve and store the note. */
    public function reply(Request $request, ContactEnquiry $enquiry): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'resolve' => 'sometimes|boolean',
        ]);

        Mail::to($enquiry->email)
            ->send(new EnquiryReplyMail($enquiry, $validated['message']));

        $enquiry->update([
            'internal_notes' => trim(($enquiry->internal_notes ? $enquiry->internal_notes."\n" : '')
                .'['.now()->format('d M Y H:i').' — '.auth()->user()->name."] replied: {$validated['message']}"),
            'status'         => $request->boolean('resolve') ? 'resolved' : $enquiry->status,
        ]);

        $this->audit->record('enquiries.reply', $enquiry, null, [
            'email'  => $enquiry->email,
            'status' => $enquiry->fresh()->status,
        ]);

        return back()->with('success', 'Reply sent to '.$enquiry->email.'.');
    }

    public function destroy(ContactEnquiry $enquiry): RedirectResponse
    {
        $this->audit->record('enquiries.delete', $enquiry);
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $ids = collect(explode(',', (string) $request->input('ids', '')))->filter()->map(fn ($id) => (int) $id);

        $query = ContactEnquiry::query()
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q->latest());

        $this->audit->record('enquiries.export', null, null, [
            'ids'  => $ids->all(),
            'rows' => $query->count(),
        ]);

        $filename = 'enquiries-'.now()->format('ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($out, ['Reference', 'Name', 'Email', 'Phone', 'Type', 'Subject', 'Status', 'Assigned to', 'Message', 'Internal notes', 'Received']);

            $query->with('assignee')->chunk(500, function ($enquiries) use ($out) {
                foreach ($enquiries as $enquiry) {
                    fputcsv($out, [
                        $enquiry->uuid ?? $enquiry->id,
                        $enquiry->name,
                        $enquiry->email,
                        $enquiry->phone,
                        $enquiry->type,
                        $enquiry->subject,
                        $enquiry->status,
                        $enquiry->assignee?->name,
                        $enquiry->message,
                        $enquiry->internal_notes,
                        $enquiry->created_at?->toISOString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
