<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StartupProfileController;
use App\Http\Controllers\Admin\CoordinatorAssignmentController;
use App\Http\Controllers\Admin\InformationSheetController;
use App\Http\Controllers\Admin\MentorController;
use App\Http\Controllers\ProfileController;

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin-only routes
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('startups', [StartupProfileController::class, 'index'])->name('startups.index');
    Route::get('startups/{startup}', [StartupProfileController::class, 'show'])->name('startups.show');

    Route::post('startups/{startup}/coordinator', [CoordinatorAssignmentController::class, 'store'])
        ->name('startups.coordinator.store');

    Route::get('startups/{startup}/information-sheet', [InformationSheetController::class, 'show'])
        ->name('information-sheet.show');
    Route::patch('startups/{startup}/information-sheet/approve', [InformationSheetController::class, 'approve'])
        ->name('information-sheet.approve');
    Route::patch('startups/{startup}/information-sheet/reject', [InformationSheetController::class, 'reject'])
        ->name('information-sheet.reject');

    Route::resource('mentors', MentorController::class)
        ->except(['create', 'edit', 'show'])
        ->names('mentors');

    // Future modules (Coordinator Profile, Assessment Hub, Roadblock Management, Risk Monitoring) nest here
});

// Startup-only routes (future modules nest here)
Route::middleware(['auth', 'role:Startup'])->prefix('startup')->name('startup.')->group(function () {
    //
});