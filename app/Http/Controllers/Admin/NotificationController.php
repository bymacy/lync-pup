<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    /**
     * Mirrors Startup\NotificationController::show() exactly: opening one of
     * the Admin Dashboard's update cards marks that notification read and
     * forwards to wherever it points, so an admin who reaches the same page
     * some other way still has the card waiting for them.
     */
    public function show(string $notification): RedirectResponse
    {
        $record = auth()->user()->notifications()->whereKey($notification)->firstOrFail();

        $record->markAsRead();

        $route = $record->data['route'] ?? null;

        return redirect()->route(
            $route && Route::has($route) ? $route : 'dashboard'
        );
    }
}
