<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StoreLocation;
use Illuminate\View\View;

class StoreLocationController extends Controller
{
    public function index(): View
    {
        return view('storefront.locations.index', [
            'locations' => StoreLocation::active()->orderBy('sort_order')->get(),
        ]);
    }
}
