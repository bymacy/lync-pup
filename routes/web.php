<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Admin-only route group (future modules nest here)
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin module routes will go here
});

// Startup-only route group (future modules nest here)
Route::middleware(['auth', 'role:Startup'])->prefix('startup')->name('startup.')->group(function () {
    // Startup module routes will go here
});