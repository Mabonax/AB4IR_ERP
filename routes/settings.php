<?php

use App\Domains\StaffAttendance\Controllers\StaffAttendanceController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])
        ->middleware('permission:domain.settings.view|domain.settings.manage|project-activities.view|project-activities.manage')
        ->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->middleware('permission:domain.settings.manage|project-activities.view|project-activities.manage')
        ->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->middleware('permission:domain.settings.manage|project-activities.view|project-activities.manage')
        ->name('profile.destroy');

    Route::get('settings/leave', [ProfileController::class, 'leave'])
        ->middleware('permission:domain.leave.view|domain.leave.manage')
        ->name('profile.leave');

    Route::get('settings/attendance', [StaffAttendanceController::class, 'self'])
        ->name('profile.attendance');
    Route::post('settings/attendance/clock-in', [StaffAttendanceController::class, 'clockIn'])
        ->name('profile.attendance.clock-in');
    Route::post('settings/attendance/late-request', [StaffAttendanceController::class, 'requestLateClockIn'])
        ->name('profile.attendance.late-request');
    Route::post('settings/attendance/clock-out', [StaffAttendanceController::class, 'clockOut'])
        ->name('profile.attendance.clock-out');

    Route::get('settings/password', [PasswordController::class, 'edit'])
        ->middleware('permission:domain.settings.view|domain.settings.manage|project-activities.view|project-activities.manage')
        ->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware(['permission:domain.settings.manage|project-activities.view|project-activities.manage', 'throttle:6,1'])
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })
        ->middleware('permission:domain.settings.view|domain.settings.manage|project-activities.view|project-activities.manage')
        ->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->middleware('permission:domain.settings.view|domain.settings.manage|project-activities.view|project-activities.manage')
        ->name('two-factor.show');
});
