<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HomepageSectionController — scaffold. Implements index/show/edit/update/destroy as required.
 * Wired through routes/admin.php resource declarations.
 */
class HomepageSectionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        return view('admin.homepagesectioncontroller.index');
    }
}
