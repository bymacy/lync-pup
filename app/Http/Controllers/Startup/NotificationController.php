<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    /**
     * Opening one of the dashboard's update cards marks that notification
     * read and forwards to wherever it points. Read state is set here rather
     * than on the destination page, so a founder who reaches Meetings by some
     * other route still has the card waiting for them.
     */
    public function show(string $notification): RedirectResponse
    {
        $record = auth()->user()->notifications()->whereKey($notification)->firstOrFail();

        $record->markAsRead();

        // The route name travels inside the stored payload, so a notification
        // written before a route was renamed can't take the founder to a
        // 500 — fall back to the dashboard if it no longer resolves.
        $route = $record->data['route'] ?? null;

        return redirect()->route(
            $route && Route::has($route) ? $route : 'startup.dashboard'
        );
    }
}
