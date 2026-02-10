<?php

use App\Domains\Beneficiaries\Controllers\BeneficiaryController;
use App\Domains\Facilitators\Controllers\FacilitatorController;
use App\Domains\Programs\Controllers\ProgramController;
use App\Domains\Assets\Controllers\AssetCategoryController;
use App\Domains\Assets\Controllers\AssetController;
use App\Domains\Staff\Controllers\StaffDepartmentController;
use App\Domains\Staff\Controllers\StaffController;
use App\Domains\Stakeholders\Controllers\StakeholderController;
use App\Domains\Projects\Controllers\ProjectController;
use App\Domains\Projects\Controllers\ProjectLocationController;
use App\Domains\Projects\Controllers\ProjectEnrollmentController;
use App\Domains\Projects\Controllers\MilestoneTemplateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Beneficiaries Routes
    Route::resource('beneficiaries', BeneficiaryController::class);

    // Stakeholders Routes
    Route::resource('stakeholders', StakeholderController::class);

    // Facilitators Routes
    Route::resource('facilitators', FacilitatorController::class);

    // Programs Routes
    Route::resource('programs', ProgramController::class);

    // Assets Routes
    Route::get('assets', [AssetController::class, 'dashboard'])->name('assets.dashboard');
    Route::get('assets/list', [AssetController::class, 'index'])->name('assets.list');
    Route::resource('assets', AssetController::class)
        ->except(['index'])
        ->whereNumber('asset');
    Route::resource('asset-categories', AssetCategoryController::class);

    // Projects Routes
    Route::get('projects', [ProjectController::class, 'dashboard'])->name('projects.dashboard');
    Route::get('projects/list', [ProjectController::class, 'index'])->name('projects.list');
    Route::resource('projects', ProjectController::class)
        ->except(['index'])
        ->whereNumber('project');
    Route::post('projects/{project}/milestones', [ProjectController::class, 'addMilestone'])
        ->whereNumber('project')
        ->name('projects.milestones.store');
    Route::resource('project-locations', ProjectLocationController::class);
    Route::get('project-locations/{project_location}/progress', [ProjectLocationController::class, 'progress'])
        ->whereNumber('project_location')
        ->name('project-locations.progress');
    Route::resource('project-enrollments', ProjectEnrollmentController::class);
    Route::resource('milestone-templates', MilestoneTemplateController::class)->except(['show', 'edit', 'create']);

    // Staff Routes
    Route::get('staff', [StaffController::class, 'dashboard'])->name('staff.dashboard');
    Route::get('staff/list', [StaffController::class, 'index'])->name('staff.list');
    Route::resource('staff', StaffController::class)
        ->except(['index'])
        ->whereNumber('staff');
    Route::resource('staff-departments', StaffDepartmentController::class);
});

require __DIR__.'/settings.php';
