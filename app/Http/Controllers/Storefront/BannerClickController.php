<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BannerClickController extends Controller
{
    /** Public click-through endpoint; records the click then forwards. */
    public function click(Request $request, Banner $banner): RedirectResponse
    {
        if (! $banner->is_published) {
            abort(404);
        }

        if (! $banner->link_url) {
            // No destination — still count the interaction and send them shopping.
            $banner->recordClick();

            return redirect()->route('shop.index');
        }

        $banner->recordClick();

        return redirect()->away($banner->link_url);
    }
}
