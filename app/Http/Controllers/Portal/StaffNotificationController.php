<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffNotificationController extends Controller
{
    public function index(): View
    {
        $notifications = UserNotification::forUser($this->type(), $this->id())
            ->latest('created_at')->latest('notifID')->limit(100)->get();

        return view('portal.notifications', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->where('is_read', 0)->count(),
            'role' => $this->type(),
        ]);
    }

    public function read(int $id): RedirectResponse
    {
        UserNotification::forUser($this->type(), $this->id())->where('notifID', $id)->update(['is_read' => 1]);

        return redirect()->route($this->type().'.notifications');
    }

    public function readAll(): RedirectResponse
    {
        UserNotification::forUser($this->type(), $this->id())->update(['is_read' => 1]);

        return redirect()->route($this->type().'.notifications')->with('status', 'All notifications marked as read.');
    }

    private function type(): string
    {
        return Auth::guard('staff')->user()->role_slug;
    }

    private function id(): int
    {
        return (int) Auth::guard('staff')->id();
    }
}
