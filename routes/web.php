<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Domains\Beneficiaries\Controllers\BeneficiaryController;

Route::redirect('/', 'dashboard')->name('home');


//Beneficiaries Routes 
Route::resource('beneficiaries', BeneficiaryController::class);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
