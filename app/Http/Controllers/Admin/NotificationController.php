<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->input('filter') === 'all' ? 'all' : 'unread';

        $notifications = $request->user()
            ->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.notifications.index', compact('notifications', 'filter'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }
}
