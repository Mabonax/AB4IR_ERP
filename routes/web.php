<?php

use App\Domains\Assets\Controllers\AssetCategoryController;
use App\Domains\Assets\Controllers\AssetController;
use App\Domains\Beneficiaries\Controllers\BeneficiaryController;
use App\Domains\BusinessDevelopment\Controllers\BdsApplicationController;
use App\Domains\BusinessDevelopment\Controllers\BdsDashboardController;
use App\Domains\BusinessDevelopment\Controllers\BdsIncubateeController;
use App\Domains\Facilitators\Controllers\FacilitatorController;
use App\Domains\HumanResources\Controllers\HumanResourcesController;
use App\Domains\Leave\Controllers\LeaveRequestController;
use App\Domains\Programs\Controllers\ProgramController;
use App\Domains\Projects\Controllers\MilestoneTemplateController;
use App\Domains\Projects\Controllers\ProjectAttendanceController;
use App\Domains\Projects\Controllers\ProjectController;
use App\Domains\Projects\Controllers\ProjectEnrollmentController;
use App\Domains\Projects\Controllers\ProjectLocationController;
use App\Domains\Projects\Controllers\ProjectMilestoneAssessmentController;
use App\Domains\Staff\Controllers\StaffController;
use App\Domains\Staff\Controllers\StaffDepartmentController;
use App\Domains\Stakeholders\Controllers\StakeholderController;
use App\Http\Controllers\AccessControl\AccessControlController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    $viewPermission = static fn (string $domain): string => "permission:domain.{$domain}.view|domain.{$domain}.manage";
    $managePermission = static fn (string $domain): string => "permission:domain.{$domain}.manage";

    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('beneficiaries', BeneficiaryController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('beneficiaries'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('beneficiaries'));

    Route::get('business-development', BdsDashboardController::class)
        ->middleware('permission:domain.business-development.view|domain.business-development.manage')
        ->name('business-development.dashboard');
    Route::get('business-development/applications', [BdsApplicationController::class, 'index'])
        ->middleware('permission:domain.business-development.view|domain.business-development.manage')
        ->name('business-development.applications.index');
    Route::get('business-development/applications/{bds_application}', [BdsApplicationController::class, 'show'])
        ->middleware('permission:domain.business-development.view|domain.business-development.manage')
        ->whereNumber('bds_application')
        ->name('business-development.applications.show');
    Route::post('business-development/applications/import', [BdsApplicationController::class, 'import'])
        ->middleware('permission:domain.business-development.manage')
        ->name('business-development.applications.import');
    Route::post('business-development/applications/{bds_application}/assess', [BdsApplicationController::class, 'assess'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('bds_application')
        ->name('business-development.applications.assess');
    Route::post('business-development/applications/{bds_application}/schedule-pitch', [BdsApplicationController::class, 'schedulePitch'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('bds_application')
        ->name('business-development.applications.schedule-pitch');
    Route::resource('business-development/incubatees', BdsIncubateeController::class)
        ->parameters(['incubatees' => 'incubatee'])
        ->middlewareFor(['index', 'show'], $viewPermission('business-development'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('business-development'));

    Route::resource('stakeholders', StakeholderController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('stakeholders'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('stakeholders'));
    Route::post('stakeholders/{stakeholder}/contacts', [StakeholderController::class, 'storeContact'])
        ->middleware($managePermission('stakeholders'))
        ->whereNumber('stakeholder')
        ->name('stakeholders.contacts.store');
    Route::delete('stakeholders/{stakeholder}/contacts/{contact}', [StakeholderController::class, 'destroyContact'])
        ->middleware($managePermission('stakeholders'))
        ->whereNumber('stakeholder')
        ->whereNumber('contact')
        ->name('stakeholders.contacts.destroy');

    Route::resource('facilitators', FacilitatorController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('facilitators'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('facilitators'));

    Route::resource('programs', ProgramController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('programs'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('programs'));

    Route::get('human-resources', [HumanResourcesController::class, 'dashboard'])
        ->middleware('permission:domain.human-resources.view|domain.human-resources.manage')
        ->name('human-resources.dashboard');

    Route::get('leave-requests', [LeaveRequestController::class, 'index'])
        ->middleware('permission:domain.leave.view|domain.leave.manage|domain.staff.view|domain.staff.manage')
        ->name('leave-requests.index');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])
        ->middleware('permission:domain.leave.manage')
        ->name('leave-requests.store');
    Route::post('leave-requests/{leave_request}/manager-approve', [LeaveRequestController::class, 'managerApprove'])
        ->middleware('permission:domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.manager-approve');
    Route::post('leave-requests/{leave_request}/manager-reject', [LeaveRequestController::class, 'managerReject'])
        ->middleware('permission:domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.manager-reject');
    Route::post('leave-requests/{leave_request}/hr-approve', [LeaveRequestController::class, 'hrApprove'])
        ->middleware('permission:domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.hr-approve');
    Route::post('leave-requests/{leave_request}/hr-reject', [LeaveRequestController::class, 'hrReject'])
        ->middleware('permission:domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.hr-reject');

    Route::get('assets', [AssetController::class, 'dashboard'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->name('assets.dashboard');
    Route::get('assets/register', [AssetController::class, 'registerCategories'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->name('assets.register.categories');
    Route::get('assets/register/{category}/models', [AssetController::class, 'registerModels'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->whereNumber('category')
        ->name('assets.register.models');
    Route::get('assets/register/{category}/models/{model}', [AssetController::class, 'registerItems'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->whereNumber('category')
        ->name('assets.register.items');
    Route::get('assets/manager-dashboard', [AssetController::class, 'managerDashboard'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->name('assets.manager-dashboard');
    Route::get('assets/list', [AssetController::class, 'index'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->name('assets.list');
    Route::resource('assets', AssetController::class)
        ->except(['index'])
        ->whereNumber('asset')
        ->middlewareFor('show', $viewPermission('assets'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('assets'));
    Route::post('assets/batches', [AssetController::class, 'storeBatch'])
        ->middleware('permission:domain.assets.manage')
        ->name('assets.batches.store');
    Route::put('assets/batches/{batch}', [AssetController::class, 'updateBatch'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('batch')
        ->name('assets.batches.update');
    Route::delete('assets/batches/{batch}', [AssetController::class, 'destroyBatch'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('batch')
        ->name('assets.batches.destroy');
    Route::post('assets/{asset}/assign', [AssetController::class, 'assign'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('asset')
        ->name('assets.assign');
    Route::post('assets/{asset}/return', [AssetController::class, 'returnAsset'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('asset')
        ->name('assets.return');
    Route::resource('asset-categories', AssetCategoryController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('assets'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('assets'));

    Route::get('projects', [ProjectController::class, 'dashboard'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->name('projects.dashboard');
    Route::get('projects/list', [ProjectController::class, 'index'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->name('projects.list');
    Route::resource('projects', ProjectController::class)
        ->except(['index'])
        ->whereNumber('project')
        ->middlewareFor('show', $viewPermission('projects'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('projects'));
    Route::post('projects/{project}/milestones', [ProjectController::class, 'addMilestone'])
        ->middleware('permission:domain.projects.manage')
        ->whereNumber('project')
        ->name('projects.milestones.store');
    Route::post('projects/{project}/milestones/sync', [ProjectController::class, 'syncMilestones'])
        ->middleware('permission:domain.projects.manage')
        ->whereNumber('project')
        ->name('projects.milestones.sync');
    Route::get('project-locations/dashboard', [ProjectLocationController::class, 'dashboard'])
        ->middleware('permission:domain.projects.view|domain.projects.manage|project-activities.view')
        ->name('project-locations.dashboard');
    Route::resource('project-locations', ProjectLocationController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('projects'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('projects'));
    Route::get('project-locations/{project_location}/progress', [ProjectLocationController::class, 'progress'])
        ->middleware('permission:domain.projects.view|domain.projects.manage|project-activities.view')
        ->whereNumber('project_location')
        ->name('project-locations.progress');
    Route::get('project-locations/{project_location}/attendance', [ProjectAttendanceController::class, 'locationRegister'])
        ->middleware('permission:domain.projects.view|domain.projects.manage|project-activities.view|project-activities.manage|attendance.view|attendance.manage')
        ->whereNumber('project_location')
        ->name('project-locations.attendance');
    Route::post('project-locations/{project_location}/attendance', [ProjectAttendanceController::class, 'saveLocationRegister'])
        ->middleware('permission:domain.projects.manage|project-activities.manage|attendance.manage')
        ->whereNumber('project_location')
        ->name('project-locations.attendance.save');
    Route::post('project-locations/{project_location}/attendance/holiday', [ProjectAttendanceController::class, 'markHoliday'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project_location')
        ->name('project-locations.attendance.holiday');
    Route::get('attendance-registers/{attendance_register}/export/pdf', [ProjectAttendanceController::class, 'exportRegisterPdf'])
        ->middleware('permission:domain.projects.view|domain.projects.manage|project-activities.view|project-activities.manage|attendance.view|attendance.manage')
        ->whereNumber('attendance_register')
        ->name('attendance-registers.export.pdf');
    Route::post('project-locations/{project_location}/assessments', [ProjectMilestoneAssessmentController::class, 'store'])
        ->middleware('permission:domain.projects.manage|project-activities.manage')
        ->whereNumber('project_location')
        ->name('project-locations.assessments.store');
    Route::get('projects/attendance-summary', [ProjectAttendanceController::class, 'projectSummary'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->name('projects.attendance-summary');
    Route::resource('project-enrollments', ProjectEnrollmentController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('projects'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('projects'));
    Route::get('milestone-templates/programs/{program}', [MilestoneTemplateController::class, 'program'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('program')
        ->name('milestone-templates.program');
    Route::resource('milestone-templates', MilestoneTemplateController::class)
        ->except(['show', 'edit', 'create'])
        ->middlewareFor('index', $viewPermission('projects'))
        ->middlewareFor(['store', 'update', 'destroy'], $managePermission('projects'));

    Route::get('staff/profile', [StaffController::class, 'profile'])
        ->middleware('permission:domain.staff.view|domain.staff.manage')
        ->name('staff.profile');
    Route::get('staff/{staff}/profile', [StaffController::class, 'profileShow'])
        ->middleware('permission:domain.staff.view|domain.staff.manage')
        ->whereNumber('staff')
        ->name('staff.profile.show');
    Route::get('staff', [StaffController::class, 'dashboard'])
        ->middleware('permission:domain.staff.view|domain.staff.manage')
        ->name('staff.dashboard');
    Route::get('staff/list', [StaffController::class, 'index'])
        ->middleware('permission:domain.staff.view|domain.staff.manage')
        ->name('staff.list');
    Route::resource('staff', StaffController::class)
        ->except(['index'])
        ->whereNumber('staff')
        ->middlewareFor('show', $viewPermission('staff'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('staff'));
    Route::resource('staff-departments', StaffDepartmentController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('staff'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('staff'));

    Route::prefix('access-control')
        ->name('access-control.')
        ->middleware(['role:super-admin|super admin|admin', 'permission:access-control.view'])
        ->group(function () {
            Route::redirect('/', '/access-control/roles')->name('index');
            Route::get('roles', [AccessControlController::class, 'rolesPage'])
                ->middleware('permission:roles.view|roles.create|roles.update|roles.delete')
                ->name('roles.page');
            Route::get('permissions', [AccessControlController::class, 'permissionsPage'])
                ->middleware('permission:permissions.view|permissions.create|permissions.update|permissions.delete')
                ->name('permissions.page');
            Route::get('assignments', [AccessControlController::class, 'assignmentsPage'])
                ->middleware('permission:assignments.manage')
                ->name('assignments.page');

            Route::post('roles', [AccessControlController::class, 'storeRole'])
                ->middleware('permission:roles.create')
                ->name('roles.store');
            Route::patch('roles/{role}', [AccessControlController::class, 'updateRole'])
                ->middleware('permission:roles.update')
                ->name('roles.update');
            Route::delete('roles/{role}', [AccessControlController::class, 'destroyRole'])
                ->middleware('permission:roles.delete')
                ->name('roles.destroy');

            Route::post('permissions', [AccessControlController::class, 'storePermission'])
                ->middleware('permission:permissions.create')
                ->name('permissions.store');
            Route::patch('permissions/{permission}', [AccessControlController::class, 'updatePermission'])
                ->middleware('permission:permissions.update')
                ->name('permissions.update');
            Route::delete('permissions/{permission}', [AccessControlController::class, 'destroyPermission'])
                ->middleware('permission:permissions.delete')
                ->name('permissions.destroy');

            Route::put('users/{user}/roles', [AccessControlController::class, 'syncUserRoles'])
                ->middleware('permission:assignments.manage')
                ->name('users.roles.sync');
            Route::put('users/{user}/permissions', [AccessControlController::class, 'syncUserPermissions'])
                ->middleware('permission:assignments.manage')
                ->name('users.permissions.sync');
        });
});

require __DIR__.'/settings.php';
