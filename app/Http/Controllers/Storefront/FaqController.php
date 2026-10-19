<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::where('is_published', true)->orderBy('sort_order')->get();
        $grouped = $faqs->groupBy(fn ($f) => $f->category ?? 'General');
        return view('storefront.faq.index', [
            'groupedFaqs' => $grouped,
        ]);
    }
}
