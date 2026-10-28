<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $faqs = Faq::when($request->input('q'), fn ($q, $term) => $q->where('question', 'like', "%{$term}%"))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.faqs.index', [
            'faqs'    => $faqs,
            'filters' => $request->only(['q']),
        ]);
    }

    public function create(): View
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question'     => 'required|string|max:300',
            'answer'       => 'required|string',
            'category'     => 'nullable|string|max:80',
            'sort_order'   => 'sometimes|integer|min:0',
            'is_published' => 'sometimes|boolean',
        ]);

        $faq = Faq::create($validated);
        $this->audit->record('faqs.create', $faq);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question'     => 'required|string|max:300',
            'answer'       => 'required|string',
            'category'     => 'nullable|string|max:80',
            'sort_order'   => 'sometimes|integer|min:0',
            'is_published' => 'sometimes|boolean',
        ]);

        $faq->update($validated);
        $this->audit->record('faqs.update', $faq);

        return back()->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq)
    {
        $this->audit->record('faqs.delete', $faq);
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }
}
