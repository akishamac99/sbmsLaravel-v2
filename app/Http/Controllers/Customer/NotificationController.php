<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

// Replaces customer/notifications.php. "Mark read" is a POST button, not a link.
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::forUser('customer', $this->customerId())
            ->latest('created_at')
            ->latest('notifID')
            ->limit(100)
            ->get();

        return view('customer.notifications', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->where('is_read', 0)->count(),
        ]);
    }

    public function read(int $id)
    {
        UserNotification::forUser('customer', $this->customerId())
            ->where('notifID', $id)
            ->update(['is_read' => 1]);

        return redirect()->route('customer.notifications');
    }

    public function readAll()
    {
        UserNotification::forUser('customer', $this->customerId())->update(['is_read' => 1]);

        return redirect()->route('customer.notifications')->with('status', 'All notifications marked as read.');
    }

    private function customerId(): int
    {
        return (int) Auth::guard('customer')->id();
    }
}
