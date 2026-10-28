<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductQuestionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'q']);

        $counts = [
            'all'          => ProductQuestion::count(),
            'pending'      => ProductQuestion::where('is_approved', false)->count(),
            'unanswered'   => ProductQuestion::whereNull('answer')->count(),
            'approved'     => ProductQuestion::where('is_approved', true)->count(),
        ];

        $questions = ProductQuestion::with(['product', 'user', 'answerer'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($sub) use ($term) {
                    $sub->where('question', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhereHas('product', fn ($query) => $query->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->when($status === 'pending', fn ($q) => $q->pending())
                      ->when($status === 'approved', fn ($q) => $q->approved())
                      ->when($status === 'unanswered', fn ($q) => $q->whereNull('answer'));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'filters'   => $filters,
            'counts'    => $counts,
        ]);
    }

    public function answer(Request $request, ProductQuestion $question): RedirectResponse
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:2000'],
        ]);

        $question->submitAnswer($validated['answer'], auth()->user());
        $this->audit->record('questions.answer', $question);

        return back()->with('success', 'Answer saved for “'.\Illuminate\Support\Str::limit($question->question, 60).'”.');
    }

    public function approve(ProductQuestion $question): RedirectResponse
    {
        $question->approve();
        $this->audit->record('questions.approve', $question);

        return back()->with('success', 'Question approved & published.');
    }

    public function reject(ProductQuestion $question): RedirectResponse
    {
        $question->reject();
        $this->audit->record('questions.reject', $question);

        return back()->with('success', 'Question unpublished.');
    }

    public function destroy(ProductQuestion $question): RedirectResponse
    {
        $this->audit->record('questions.delete', $question);
        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'  => ['integer', 'exists:product_questions,id'],
            'action' => ['required', 'in:approve,reject,delete'],
        ]);

        $questions = ProductQuestion::whereIn('id', $validated['ids'])->get();

        foreach ($questions as $question) {
            match ($validated['action']) {
                'approve' => $question->approve(),
                'reject'  => $question->reject(),
                'delete'  => (function () use ($question) {
                    $this->audit->record('questions.bulk.delete', $question);
                    $question->delete();
                })(),
            };
        }

        $this->audit->record('questions.bulk.update', null, [], [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', count($validated['ids']).' question(s) updated.');
    }
}
