<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'routePrefix' => $this->routePrefix($request),
            'notifications' => Notification::query()
                ->where('user_id', $request->user()->user_id)
                ->with('deliveries')
                ->orderByDesc('created_at')
                ->orderByDesc('notification_id')
                ->paginate(15),
        ]);
    }

    public function markAsRead(Request $request, int $notification): RedirectResponse
    {
        $record = $this->ownedNotification($request, $notification);
        $record->is_read = true;
        $record->save();

        return redirect()
            ->route($this->routePrefix($request).'notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    public function destroy(Request $request, int $notification): RedirectResponse
    {
        $this->ownedNotification($request, $notification)->delete();

        return redirect()
            ->route($this->routePrefix($request).'notifications.index')
            ->with('success', 'Notification deleted.');
    }

    private function ownedNotification(Request $request, int $notification): Notification
    {
        return Notification::query()
            ->where('user_id', $request->user()->user_id)
            ->whereKey($notification)
            ->firstOrFail();
    }

    private function routePrefix(Request $request): string
    {
        return match ($request->user()->role) {
            'Admin' => 'admin.',
            'Staff' => 'staff.',
            default => 'member.',
        };
    }
}
