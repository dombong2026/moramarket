<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['all', 'unread', 'read'])],
        ]);
        $filter = $data['status'] ?? 'all';

        $query = $request->user()->notifications();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(10)->withQueryString();
        $workspace = match (true) {
            $request->user()->isAdmin() => 'admin',
            $request->user()->isApprovedVendor() => 'vendor',
            default => 'account',
        };

        return view('dashboard.notifications', compact('notifications', 'filter', 'workspace'));
    }

    public function markRead(Request $request, string $notification)
    {
        $notice = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $notice->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
