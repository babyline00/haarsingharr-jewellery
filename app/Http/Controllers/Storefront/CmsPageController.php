<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function show(CmsPage $page): View
    {
        abort_if($page->status !== 'published' && ! auth()->user()?->isStaff(), 404);
        abort_if($page->published_at && $page->published_at->isFuture() && ! auth()->user()?->isStaff(), 404);

        return view('storefront.cms.show', [
            'page' => $page,
        ]);
    }
}
