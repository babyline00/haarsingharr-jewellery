<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateTestMail;
use App\Models\EmailTemplate;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /** Event keys available for per-event mapping, by convention <mailable-key>. */
    public function index(): View
    {
        $templates = EmailTemplate::orderBy('key')->get();

        return view('admin.emails.index', [
            'templates' => $templates,
            'stats'     => [
                'total'   => $templates->count(),
                'active'  => $templates->where('is_active', true)->count(),
                'paused'  => $templates->where('is_active', false)->count(),
            ],
        ]);
    }

    public function edit(EmailTemplate $email_template): View
    {
        return view('admin.emails.edit', [
            'template' => $email_template,
            'sample'   => EmailTemplate::sampleData($email_template->variables ?? []),
            'previewRenders' => $email_template->render(
                EmailTemplate::sampleData($email_template->variables ?? [])
            ),
        ]);
    }

    public function update(Request $request, EmailTemplate $email_template): RedirectResponse
    {
        $validated = $request->validate([
            'subject'   => 'required|string|max:200',
            'body'      => 'required|string|max:20000',
            'variables' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $email_template->update([
            'subject'   => $validated['subject'],
            'body'      => $validated['body'],
            'variables' => collect(array_map('trim', explode(',', (string) $validated['variables'])))
                ->filter()->unique()->values()->all(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->audit->record('email_templates.update', $email_template);

        return back()->with('success', 'Template updated.');
    }

    public function preview(Request $request, EmailTemplate $email_template): View
    {
        $data = EmailTemplate::sampleData($email_template->variables ?? [], $request->only(
            collect($email_template->variables ?? [])
                ->map(fn ($v) => is_array($v) ? ($v['name'] ?? '') : $v)->all()
        ));

        return view('admin.emails.preview', [
            'template' => $email_template,
            'sample'   => $data,
            'rendered' => $email_template->render($data),
        ]);
    }

    /** Send a rendered sample to the current staff member. */
    public function testSend(Request $request, EmailTemplate $email_template): RedirectResponse
    {
        $user = $request->user();

        $rendered = $email_template->render(
            EmailTemplate::sampleData($email_template->variables ?? [], [
                'customer_name' => $user->name ?: 'There',
            ])
        );

        Mail::to($user->email)->send(
            new TemplateTestMail($email_template, '[TEST] '.$rendered['subject'], $rendered['body'])
        );

        $this->audit->record('email_templates.test_send', $email_template, null, [
            'to' => $user->email,
        ]);

        return back()->with('success', "Test email sent to {$user->email}.");
    }

    public function toggle(EmailTemplate $email_template): RedirectResponse
    {
        $email_template->update(['is_active' => ! $email_template->is_active]);
        $this->audit->record('email_templates.toggle', $email_template, null, [
            'is_active' => $email_template->is_active,
        ]);

        return back()->with('success', $email_template->is_active ? 'Template enabled.' : 'Template disabled.');
    }
}
