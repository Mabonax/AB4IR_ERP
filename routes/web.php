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
use App\Domains\Projects\Controllers\ProjectMilestoneAssessmentController;
use App\Domains\HumanResources\Controllers\HumanResourcesController;
use App\Domains\Leave\Controllers\LeaveRequestController;
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

    // Human Resources Routes
    Route::get('human-resources', [HumanResourcesController::class, 'dashboard'])
        ->name('human-resources.dashboard');

    // Leave Management Routes
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])
        ->name('leave-requests.index');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])
        ->name('leave-requests.store');
    Route::post('leave-requests/{leave_request}/manager-approve', [LeaveRequestController::class, 'managerApprove'])
        ->whereNumber('leave_request')
        ->name('leave-requests.manager-approve');
    Route::post('leave-requests/{leave_request}/manager-reject', [LeaveRequestController::class, 'managerReject'])
        ->whereNumber('leave_request')
        ->name('leave-requests.manager-reject');
    Route::post('leave-requests/{leave_request}/hr-approve', [LeaveRequestController::class, 'hrApprove'])
        ->whereNumber('leave_request')
        ->name('leave-requests.hr-approve');
    Route::post('leave-requests/{leave_request}/hr-reject', [LeaveRequestController::class, 'hrReject'])
        ->whereNumber('leave_request')
        ->name('leave-requests.hr-reject');

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
    Route::post('projects/{project}/milestones/sync', [ProjectController::class, 'syncMilestones'])
        ->whereNumber('project')
        ->name('projects.milestones.sync');
    Route::get('project-locations/dashboard', [ProjectLocationController::class, 'dashboard'])
        ->name('project-locations.dashboard');
    Route::resource('project-locations', ProjectLocationController::class);
    Route::get('project-locations/{project_location}/progress', [ProjectLocationController::class, 'progress'])
        ->whereNumber('project_location')
        ->name('project-locations.progress');
    Route::post('project-locations/{project_location}/assessments', [ProjectMilestoneAssessmentController::class, 'store'])
        ->whereNumber('project_location')
        ->name('project-locations.assessments.store');
    Route::resource('project-enrollments', ProjectEnrollmentController::class);
    Route::get('milestone-templates/programs/{program}', [MilestoneTemplateController::class, 'program'])
        ->whereNumber('program')
        ->name('milestone-templates.program');
    Route::resource('milestone-templates', MilestoneTemplateController::class)->except(['show', 'edit', 'create']);

    // Staff Routes
    Route::get('staff/profile', [StaffController::class, 'profile'])->name('staff.profile');
    Route::get('staff/{staff}/profile', [StaffController::class, 'profileShow'])
        ->whereNumber('staff')
        ->name('staff.profile.show');
    Route::get('staff', [StaffController::class, 'dashboard'])->name('staff.dashboard');
    Route::get('staff/list', [StaffController::class, 'index'])->name('staff.list');
    Route::resource('staff', StaffController::class)
        ->except(['index'])
        ->whereNumber('staff');
    Route::resource('staff-departments', StaffDepartmentController::class);
});

require __DIR__.'/settings.php';
