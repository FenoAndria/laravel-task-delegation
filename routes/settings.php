<?php

use App\Http\Controllers\Settings\MemberController;
use App\Http\Controllers\Settings\OrganizationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('settings/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::patch('settings/organization', [OrganizationController::class, 'update'])->name('organization.update');

    Route::get('settings/members', [MemberController::class, 'index'])->name('members.index');
    Route::patch('settings/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('settings/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
});
