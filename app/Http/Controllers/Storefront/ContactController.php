<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Models\StoreLocation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\AuditService;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('storefront.contact.index', [
            'locations' => StoreLocation::active()->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:120',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
            'type'    => 'nullable|in:general,support,complaint,wholesale,press',
        ]);

        $enquiry = ContactEnquiry::create(array_merge($validated, [
            'status'     => 'new',
            'ip_address' => $request->ip(),
        ]));

        $audit->record('contact_enquiry.create', $enquiry);
        return redirect()->route('contact')->with('success',
            'Thank you for reaching out. Our team will respond within 24 hours.');
    }
}
