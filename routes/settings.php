<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])
        ->middleware('permission:domain.settings.view|domain.settings.manage')
        ->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->middleware('permission:domain.settings.manage')
        ->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->middleware('permission:domain.settings.manage')
        ->name('profile.destroy');

    Route::get('settings/leave', [ProfileController::class, 'leave'])
        ->middleware('permission:domain.leave.view|domain.leave.manage')
        ->name('profile.leave');

    Route::get('settings/password', [PasswordController::class, 'edit'])
        ->middleware('permission:domain.settings.view|domain.settings.manage')
        ->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware(['permission:domain.settings.manage', 'throttle:6,1'])
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })
        ->middleware('permission:domain.settings.view|domain.settings.manage')
        ->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->middleware('permission:domain.settings.view|domain.settings.manage')
        ->name('two-factor.show');
});
