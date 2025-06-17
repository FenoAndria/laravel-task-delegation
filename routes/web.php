<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CurrentOrganizationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionStatusController;
use App\Http\Controllers\MyTaskController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('invitations/{token}/decline', [InvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('current-organization', [CurrentOrganizationController::class, 'update'])->name('current-organization.update');

    Route::middleware('organization')->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');

        Route::resource('missions', MissionController::class)->except(['create', 'edit']);
        Route::post('missions/{mission}/complete', [MissionStatusController::class, 'complete'])->name('missions.complete');
        Route::post('missions/{mission}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('missions/{mission}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::put('tasks/{task}/status', [TaskStatusController::class, 'update'])->name('tasks.status.update');
        Route::get('my-tasks', [MyTaskController::class, 'index'])->name('tasks.mine');
        Route::post('missions/{mission}/cancel', [MissionStatusController::class, 'cancel'])->name('missions.cancel');
        Route::post('missions/{mission}/reopen', [MissionStatusController::class, 'reopen'])->name('missions.reopen');

        Route::resource('teams', TeamController::class)->except(['create', 'edit']);
        Route::post('teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
        Route::delete('teams/{team}/members/{member}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
        Route::put('teams/{team}/lead', [TeamMemberController::class, 'updateLead'])->name('teams.lead.update');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
