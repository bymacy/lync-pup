<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StartupProfileController;
use App\Http\Controllers\Admin\CoordinatorAssignmentController;
use App\Http\Controllers\Admin\InformationSheetController;
use App\Http\Controllers\Admin\MentorController;
use App\Http\Controllers\Admin\CoordinatorProfileController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Startup\DashboardController as StartupDashboardController;
use App\Http\Controllers\Startup\StartupProfileController as FounderProfileController;
use App\Http\Controllers\Startup\InformationSheetController as FounderInfoSheetController;

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
    
    Route::resource('coordinators', CoordinatorProfileController::class)
        ->except(['create', 'edit', 'show'])
        ->names('coordinators');

    // Future modules (Coordinator Profile, Assessment Hub, Roadblock Management, Risk Monitoring) nest here
});

// Startup-only routes (future modules nest here)
Route::middleware(['auth', 'role:Startup'])->prefix('startup')->name('startup.')->group(function () {
    Route::get('dashboard', [StartupDashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [FounderProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [FounderProfileController::class, 'update'])->name('profile.update');

    Route::post('profile/team-members', [FounderProfileController::class, 'storeTeamMember'])->name('team-members.store');
    Route::patch('profile/team-members/{teamMember}', [FounderProfileController::class, 'updateTeamMember'])->name('team-members.update');
    Route::delete('profile/team-members/{teamMember}', [FounderProfileController::class, 'destroyTeamMember'])->name('team-members.destroy');

    Route::get('information-sheet', [FounderInfoSheetController::class, 'edit'])->name('information-sheet.edit');
    Route::patch('information-sheet', [FounderInfoSheetController::class, 'update'])->name('information-sheet.update');

    Route::patch('team-members/{teamMember}/details', [FounderProfileController::class, 'updateTeamMemberDetails'])->name('team-members.update-details');

    Route::post('information-sheet/incubation', [FounderInfoSheetController::class, 'storeIncubation'])->name('incubation.store');
    Route::patch('information-sheet/incubation/{incubationInvolvement}', [FounderInfoSheetController::class, 'updateIncubation'])->name('incubation.update');
    Route::delete('information-sheet/incubation/{incubationInvolvement}', [FounderInfoSheetController::class, 'destroyIncubation'])->name('incubation.destroy');

    Route::post('information-sheet/ld', [FounderInfoSheetController::class, 'storeLd'])->name('ld.store');
    Route::patch('information-sheet/ld/{ldIntervention}', [FounderInfoSheetController::class, 'updateLd'])->name('ld.update');
    Route::delete('information-sheet/ld/{ldIntervention}', [FounderInfoSheetController::class, 'destroyLd'])->name('ld.destroy');

    Route::post('information-sheet/references', [FounderInfoSheetController::class, 'storeReference'])->name('references.store');
    Route::patch('information-sheet/references/{reference}', [FounderInfoSheetController::class, 'updateReference'])->name('references.update');
    Route::delete('information-sheet/references/{reference}', [FounderInfoSheetController::class, 'destroyReference'])->name('references.destroy');
});