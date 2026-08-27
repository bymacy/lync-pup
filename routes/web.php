<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\Admin\StartupProfileController;
use App\Http\Controllers\Admin\CoordinatorAssignmentController;
use App\Http\Controllers\Admin\InformationSheetController;
use App\Http\Controllers\Admin\MentorController;
use App\Http\Controllers\Admin\CoordinatorProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Startup\RoadblockController;

use App\Http\Controllers\Startup\DashboardController as StartupDashboardController;
use App\Http\Controllers\Startup\StartupProfileController as FounderProfileController;
use App\Http\Controllers\Startup\InformationSheetController as FounderInfoSheetController;
use App\Http\Controllers\Startup\MeetingController as FounderMeetingController;
use App\Http\Controllers\Admin\RoadblockController as AdminRoadblockController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\AssessmentHubController;
use App\Http\Controllers\Admin\EvaluationScheduleController;
use App\Http\Controllers\Admin\FounderApplicationController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\RiskMonitoringController;
use App\Http\Controllers\Startup\FounderReadinessController;


require __DIR__.'/auth.php';

// Placeholder legal pages linked from the Founder registration form.
Route::get('/terms-of-service', function () {
    return view('legal.terms');
})->name('legal.terms');

Route::get('/privacy-policy', function () {
    return view('legal.privacy');
})->name('legal.privacy');

// Fallback for machines where `php artisan storage:link` hasn't been run
// (e.g. Windows testers — creating a symlink there needs Developer Mode or
// an elevated shell). Every image URL in the app already resolves to
// "/storage/{path}" via Storage::url(). Where the symlink exists, the
// webserver serves that path as a static file and this route is never
// reached; where it doesn't, this route streams the same file straight out
// of storage/app/public instead, so the symlink step is no longer required
// for images to show up. See app/Http/Controllers/StorageController.php.
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.show');

// No 'verified' middleware here — email verification is a step in the
// self-registered Founder flow only. This /dashboard route is what Admin
// accounts land on (Startups get their own 'startup.dashboard' instead),
// and Admins are created directly (e.g. via seeder) without ever going
// through that verification step, so gating this route on it would lock
// admins out whenever their email_verified_at happens to be null.
//
// 'role:Admin' is required here too: without it, this route is reachable by
// any authenticated user regardless of role. Normally a Founder never ends
// up here because AuthenticatedSessionController sends them to
// 'startup.dashboard' — but redirect()->intended() will happily send a
// freshly-logged-in Founder to a stale intended URL of '/dashboard' left
// over from an earlier guest visit (e.g. typing /dashboard before logging
// in), and without a role check this route rendered the full admin layout
// for them. CheckRole now catches that and bounces them to their own
// dashboard instead.
Route::middleware(['auth', 'role:Admin'])->group(function () {
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
    Route::patch('startups/{startup}/information-sheet', [InformationSheetController::class, 'update'])
        ->name('information-sheet.update');

    Route::post('startups/{startup}/information-sheet/team-members', [InformationSheetController::class, 'storeTeamMember'])
        ->name('information-sheet.team-members.store');
    Route::patch('information-sheet/team-members/{teamMember}', [InformationSheetController::class, 'updateTeamMember'])
        ->name('information-sheet.team-members.update');
    Route::delete('information-sheet/team-members/{teamMember}', [InformationSheetController::class, 'destroyTeamMember'])
        ->name('information-sheet.team-members.destroy');

    Route::post('startups/{startup}/information-sheet/incubation', [InformationSheetController::class, 'storeIncubation'])
        ->name('information-sheet.incubation.store');
    Route::patch('information-sheet/incubation/{incubationInvolvement}', [InformationSheetController::class, 'updateIncubation'])
        ->name('information-sheet.incubation.update');
    Route::delete('information-sheet/incubation/{incubationInvolvement}', [InformationSheetController::class, 'destroyIncubation'])
        ->name('information-sheet.incubation.destroy');

    Route::post('startups/{startup}/information-sheet/ld', [InformationSheetController::class, 'storeLd'])
        ->name('information-sheet.ld.store');
    Route::patch('information-sheet/ld/{ldIntervention}', [InformationSheetController::class, 'updateLd'])
        ->name('information-sheet.ld.update');
    Route::delete('information-sheet/ld/{ldIntervention}', [InformationSheetController::class, 'destroyLd'])
        ->name('information-sheet.ld.destroy');

    Route::post('startups/{startup}/information-sheet/references', [InformationSheetController::class, 'storeReference'])
        ->name('information-sheet.references.store');
    Route::patch('information-sheet/references/{reference}', [InformationSheetController::class, 'updateReference'])
        ->name('information-sheet.references.update');
    Route::delete('information-sheet/references/{reference}', [InformationSheetController::class, 'destroyReference'])
        ->name('information-sheet.references.destroy');

    Route::resource('mentors', MentorController::class)
        ->except(['create', 'edit', 'show'])
        ->names('mentors');
    
    Route::resource('coordinators', CoordinatorProfileController::class)
        ->except(['create', 'edit', 'show'])
        ->names('coordinators');

    Route::post('startups/{startup}/request-pitch-deck', [StartupProfileController::class, 'requestPitchDeck'])
        ->name('startups.request-pitch-deck');

    Route::get('/roadblocks', [AdminRoadblockController::class, 'index'])->name('roadblocks.index');
    Route::put('/roadblocks/{roadblock}/assign', [AdminRoadblockController::class, 'assign'])->name('roadblocks.assign');
    Route::delete('/roadblocks/{roadblock}/assign', [AdminRoadblockController::class, 'unassign'])->name('roadblocks.unassign');
    Route::post('/roadblocks/{roadblock}/resolve', [AdminRoadblockController::class, 'resolve'])->name('roadblocks.resolve');
    Route::post('/roadblocks/{roadblock}/fail', [AdminRoadblockController::class, 'fail'])->name('roadblocks.fail');
    Route::post('/roadblocks/{roadblock}/recover', [AdminRoadblockController::class, 'recover'])->name('roadblocks.recover');
    Route::delete('/roadblocks/{roadblock}', [AdminRoadblockController::class, 'destroy'])->name('roadblocks.destroy');

    Route::get('/assessment-hub', [AssessmentHubController::class, 'index'])->name('assessment-hub.index');
    Route::post('/assessment-hub/evaluations', [EvaluationScheduleController::class, 'store'])->name('assessment-hub.evaluations.store');
    Route::put('/assessment-hub/evaluations/{evaluationSchedule}', [EvaluationScheduleController::class, 'update'])->name('assessment-hub.evaluations.update');
    Route::delete('/assessment-hub/evaluations/{evaluationSchedule}', [EvaluationScheduleController::class, 'destroy'])->name('assessment-hub.evaluations.destroy');
    Route::put('/assessment-hub/assessments/{startup}', [AssessmentController::class, 'update'])->name('assessment-hub.assessments.update');
    Route::put('/assessment-hub/assessments/{startup}/documents', [AssessmentController::class, 'updateDocuments'])->name('assessment-hub.assessments.update-documents');

    Route::get('/founder-applications', [FounderApplicationController::class, 'index'])->name('founder-applications.index');
    Route::post('/founder-applications/{startup}/approve', [FounderApplicationController::class, 'approve'])->name('founder-applications.approve');
    Route::post('/founder-applications/{startup}/reject', [FounderApplicationController::class, 'reject'])->name('founder-applications.reject');

    Route::resource('cohorts', CohortController::class)
        ->except(['create', 'edit', 'show'])
        ->names('cohorts');

    Route::get('/risk-monitoring', [RiskMonitoringController::class, 'index'])->name('risk-monitoring.index');
});

// Startup-only routes (future modules nest here)
// "approved" blocks a self-registered Founder whose account is still
// Pending/Rejected from reaching any of these routes directly — closes the
// gap where LoginRequest's approval check could otherwise be bypassed by
// navigating straight here while the post-registration session is still
// active. Safe for existing accounts: account_status defaults to "Active".
Route::middleware(['auth', 'role:Startup', 'approved'])->prefix('startup')->name('startup.')->group(function () {
    Route::get('dashboard', [StartupDashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [FounderProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [FounderProfileController::class, 'update'])->name('profile.update');

    Route::post('profile/team-members', [FounderProfileController::class, 'storeTeamMember'])->name('team-members.store');
    Route::patch('profile/team-members/{teamMember}', [FounderProfileController::class, 'updateTeamMember'])->name('team-members.update');
    Route::delete('profile/team-members/{teamMember}', [FounderProfileController::class, 'destroyTeamMember'])->name('team-members.destroy');

    Route::get('information-sheet', [FounderInfoSheetController::class, 'edit'])->name('information-sheet.edit');

    Route::get('meetings', [FounderMeetingController::class, 'index'])->name('meetings.index');
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

    Route::get('/roadblocks', [RoadblockController::class, 'index'])->name('submissions.index');
    Route::post('/roadblocks', [RoadblockController::class, 'store'])->name('submissions.store');

    Route::get('readiness', [FounderReadinessController::class, 'index'])->name('readiness.index');
});