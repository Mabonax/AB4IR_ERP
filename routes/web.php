<?php

use App\Domains\Beneficiaries\Controllers\BeneficiaryController;
use App\Domains\Facilitators\Controllers\FacilitatorController;
use App\Domains\Programs\Controllers\ProgramController;
use App\Domains\Assets\Controllers\AssetCategoryController;
use App\Domains\Assets\Controllers\AssetController;
use App\Domains\Staff\Controllers\StaffDepartmentController;
use App\Domains\Staff\Controllers\StaffController;
use App\Domains\Stakeholders\Controllers\StakeholderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::redirect('/', 'dashboard')->name('home');

// Beneficiaries Routes
Route::resource('beneficiaries', BeneficiaryController::class);

// Stakeholders Routes
Route::resource('stakeholders', StakeholderController::class);

// Facilitators Routes
Route::resource('facilitators', FacilitatorController::class);

// Programs Routes
Route::resource('programs', ProgramController::class);

// Assets Routes
Route::resource('assets', AssetController::class);
Route::resource('asset-categories', AssetCategoryController::class);

// Staff Routes
Route::resource('staff', StaffController::class);
Route::resource('staff-departments', StaffDepartmentController::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
