<?php

use App\Domains\Beneficiaries\Controllers\BeneficiaryController;
use App\Domains\Facilitators\Controllers\FacilitatorController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
