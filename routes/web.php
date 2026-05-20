<?php

/*
| Copyright (c) 2026 John Mabona. All rights reserved.
| Proprietary and confidential. System Architecture by John Mabona.
*/

use App\Domains\Assets\Controllers\AssetCategoryController;
use App\Domains\Assets\Controllers\AssetController;
use App\Domains\Beneficiaries\Controllers\BeneficiaryController;
use App\Domains\BusinessDevelopment\Controllers\BdsApplicationController;
use App\Domains\BusinessDevelopment\Controllers\BdsDashboardController;
use App\Domains\BusinessDevelopment\Controllers\BdsIncubateeController;
use App\Domains\BusinessDevelopment\Controllers\BdsIncubateeKpiController;
use App\Domains\BusinessDevelopment\Controllers\BdsPitchSessionController;
use App\Domains\Events\Controllers\EventController;
use App\Domains\Facilitators\Controllers\FacilitatorController;
use App\Domains\Finance\Controllers\TravelClaimController;
use App\Domains\HumanResources\Controllers\HumanResourcesController;
use App\Domains\Leave\Controllers\LeaveRequestController;
use App\Domains\Organization\Controllers\OrganizationProfileController;
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
use App\Domains\TaskManagement\Controllers\SupportTicketController;
use App\Domains\TaskManagement\Controllers\WorkTaskController;
use App\Http\Controllers\AccessControl\AccessControlController;
use App\Http\Controllers\BusinessDevelopment\AdjudicationAssessmentController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    $viewPermission = static fn (string $domain): string => "permission:domain.{$domain}.view|domain.{$domain}.manage";
    $managePermission = static fn (string $domain): string => "permission:domain.{$domain}.manage";
    $adjudicationPermission = 'permission:domain.business-development.view|domain.business-development.manage|business-development.adjudications.score';
    $adjudicationManagePermission = 'permission:domain.business-development.manage|business-development.adjudications.score';

    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.mark-all-read');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    Route::resource('beneficiaries', BeneficiaryController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('beneficiaries'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('beneficiaries'));

    Route::get('business-development', BdsDashboardController::class)
        ->middleware('permission:domain.business-development.view|domain.business-development.manage')
        ->name('business-development.dashboard');
    Route::get('task-management', \App\Domains\TaskManagement\Controllers\TaskManagementDashboardController::class)
        ->middleware('permission:domain.task-management.view|domain.task-management.manage')
        ->name('task-management.dashboard');
    Route::get('finance/travel-claims', [TravelClaimController::class, 'index'])
        ->middleware('permission:domain.finance.view|domain.finance.manage|travel-claims.submit')
        ->name('finance.travel-claims.index');
    Route::get('finance/travel-claims/create', [TravelClaimController::class, 'create'])
        ->middleware('permission:travel-claims.submit')
        ->name('finance.travel-claims.create');
    Route::post('finance/travel-claims', [TravelClaimController::class, 'store'])
        ->middleware('permission:travel-claims.submit')
        ->name('finance.travel-claims.store');
    Route::get('finance/travel-claims/{travelClaim}', [TravelClaimController::class, 'show'])
        ->middleware('permission:domain.finance.view|domain.finance.manage|travel-claims.submit')
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.show');
    Route::get('finance/travel-claims/{travelClaim}/pdf', [TravelClaimController::class, 'pdf'])
        ->middleware('permission:domain.finance.view|domain.finance.manage|travel-claims.submit')
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.pdf');
    Route::post('finance/travel-claims/{travelClaim}/approve', [TravelClaimController::class, 'approve'])
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.approve');
    Route::post('finance/travel-claims/{travelClaim}/approval-reject', [TravelClaimController::class, 'rejectApproval'])
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.approval-reject');
    Route::post('finance/travel-claims/{travelClaim}/receive', [TravelClaimController::class, 'receive'])
        ->middleware('permission:domain.finance.view|domain.finance.manage')
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.receive');
    Route::post('finance/travel-claims/{travelClaim}/pay', [TravelClaimController::class, 'pay'])
        ->middleware('permission:domain.finance.view|domain.finance.manage')
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.pay');
    Route::post('finance/travel-claims/{travelClaim}/reject', [TravelClaimController::class, 'reject'])
        ->middleware('permission:domain.finance.view|domain.finance.manage')
        ->whereNumber('travelClaim')
        ->name('finance.travel-claims.reject');
    Route::get('task-management/tasks', [WorkTaskController::class, 'index'])
        ->middleware('permission:domain.task-management.view|domain.task-management.manage')
        ->name('task-management.tasks.index');
    Route::post('task-management/tasks', [WorkTaskController::class, 'store'])
        ->middleware('permission:domain.task-management.manage')
        ->name('task-management.tasks.store');
    Route::post('task-management/tasks/{task}/status', [WorkTaskController::class, 'updateStatus'])
        ->middleware('permission:domain.task-management.view|domain.task-management.manage')
        ->whereNumber('task')
        ->name('task-management.tasks.status');
    Route::post('task-management/tasks/{task}/comment', [WorkTaskController::class, 'comment'])
        ->middleware('permission:domain.task-management.view|domain.task-management.manage')
        ->whereNumber('task')
        ->name('task-management.tasks.comment');
    Route::post('task-management/tasks/{task}/reassign', [WorkTaskController::class, 'reassign'])
        ->middleware('permission:domain.task-management.manage')
        ->whereNumber('task')
        ->name('task-management.tasks.reassign');
    Route::get('task-management/tickets', [SupportTicketController::class, 'index'])
        ->name('task-management.tickets.index');
    Route::post('task-management/tickets', [SupportTicketController::class, 'store'])
        ->name('task-management.tickets.store');
    Route::post('task-management/tickets/{ticket}/assign', [SupportTicketController::class, 'assign'])
        ->whereNumber('ticket')
        ->name('task-management.tickets.assign');
    Route::post('task-management/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])
        ->whereNumber('ticket')
        ->name('task-management.tickets.reply');
    Route::post('task-management/tickets/{ticket}/resolve', [SupportTicketController::class, 'resolve'])
        ->whereNumber('ticket')
        ->name('task-management.tickets.resolve');
    Route::post('task-management/tickets/{ticket}/close', [SupportTicketController::class, 'close'])
        ->whereNumber('ticket')
        ->name('task-management.tickets.close');
    Route::post('task-management/tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen'])
        ->whereNumber('ticket')
        ->name('task-management.tickets.reopen');
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
    Route::get('business-development/pitch-sessions', [BdsPitchSessionController::class, 'index'])
        ->middleware('permission:domain.business-development.view|domain.business-development.manage')
        ->name('business-development.pitch-sessions.index');
    Route::get('business-development/pitch-sessions/{pitch_session}', [BdsPitchSessionController::class, 'show'])
        ->middleware('permission:domain.business-development.view|domain.business-development.manage')
        ->whereNumber('pitch_session')
        ->name('business-development.pitch-sessions.show');
    Route::post('business-development/pitch-sessions', [BdsPitchSessionController::class, 'store'])
        ->middleware('permission:domain.business-development.manage')
        ->name('business-development.pitch-sessions.store');
    Route::post('business-development/pitch-sessions/{pitch_session}/start', [BdsPitchSessionController::class, 'start'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('pitch_session')
        ->name('business-development.pitch-sessions.start');
    Route::post('business-development/pitch-sessions/{pitch_session}/prospects/{prospect}/consolidate', [BdsPitchSessionController::class, 'consolidate'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('pitch_session')
        ->whereNumber('prospect')
        ->name('business-development.pitch-sessions.prospects.consolidate');
    Route::post('business-development/pitch-sessions/{pitch_session}/prospects/{prospect}/approve', [BdsPitchSessionController::class, 'approve'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('pitch_session')
        ->whereNumber('prospect')
        ->name('business-development.pitch-sessions.prospects.approve');
    Route::resource('business-development/incubatees', BdsIncubateeController::class)
        ->parameters(['incubatees' => 'incubatee'])
        ->middlewareFor(['index', 'show'], $viewPermission('business-development'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('business-development'));
    Route::post('business-development/incubatees/{incubatee}/kpis', [BdsIncubateeKpiController::class, 'assign'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('incubatee')
        ->name('business-development.incubatees.kpis.assign');
    Route::post('business-development/incubatee-kpis/{kpi}/reviews', [BdsIncubateeKpiController::class, 'review'])
        ->middleware('permission:domain.business-development.manage')
        ->whereNumber('kpi')
        ->name('business-development.incubatee-kpis.reviews.store');
    Route::resource('business-development/adjudications', AdjudicationAssessmentController::class)
        ->parameters(['adjudications' => 'assessment'])
        ->names('business-development.adjudications')
        ->middlewareFor(['index', 'show'], $adjudicationPermission)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $adjudicationManagePermission);
    Route::post('business-development/adjudications/{assessment}/submit', [AdjudicationAssessmentController::class, 'submit'])
        ->middleware($adjudicationManagePermission)
        ->name('business-development.adjudications.submit');
    Route::post('business-development/adjudications/{assessment}/unlock', [AdjudicationAssessmentController::class, 'unlock'])
        ->middleware('permission:domain.business-development.manage')
        ->name('business-development.adjudications.unlock');

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
    Route::get('organization', [OrganizationProfileController::class, 'show'])
        ->middleware($viewPermission('organization'))
        ->name('organization.show');
    Route::put('organization', [OrganizationProfileController::class, 'update'])
        ->middleware($managePermission('organization'))
        ->name('organization.update');
    Route::post('organization/logos', [OrganizationProfileController::class, 'updateLogos'])
        ->middleware($managePermission('organization'))
        ->name('organization.logos.update');
    Route::get('organization/logos/{variant}', [OrganizationProfileController::class, 'showLogo'])
        ->middleware($viewPermission('organization'))
        ->name('organization.logos.show');
    Route::get('events/series/{seriesKey}', [EventController::class, 'series'])
        ->middleware($viewPermission('events'))
        ->name('events.series.show');
    Route::get('events/{event}/participants', [EventController::class, 'participants'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->name('events.participants.page');
    Route::get('events/{event}/registers', [EventController::class, 'registersPage'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->name('events.registers.page');
    Route::get('events/{event}/event-day', [EventController::class, 'eventDay'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->name('events.event-day');
    Route::get('events/{event}/workstreams/create', [EventController::class, 'createWorkstreamPage'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.workstreams.create');
    Route::get('events/{event}/workstreams/{workstream}/edit', [EventController::class, 'editWorkstreamPage'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('workstream')
        ->name('events.workstreams.edit');
    Route::get('events/{event}/tasks/create', [EventController::class, 'createTaskPage'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.tasks.create');
    Route::get('events/{event}/tasks/{task}/edit', [EventController::class, 'editTaskPage'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('task')
        ->name('events.tasks.edit');
    Route::resource('events', EventController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('events'))
        ->middlewareFor(['create', 'edit', 'store', 'update', 'destroy'], $managePermission('events'));
    Route::get('events/{event}/report/pdf', [EventController::class, 'reportPdf'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->name('events.report.pdf');
    Route::get('events/{event}/registers/{category?}/pdf', [EventController::class, 'registerPdf'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->name('events.registers.pdf');
    Route::get('events/{event}/registers/{category?}/csv', [EventController::class, 'registerCsv'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->name('events.registers.csv');
    Route::post('events/{event}/speakers', [EventController::class, 'storeSpeaker'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.speakers.store');
    Route::delete('events/{event}/speakers/{speaker}', [EventController::class, 'destroySpeaker'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('speaker')
        ->name('events.speakers.destroy');
    Route::post('events/{event}/attendees', [EventController::class, 'storeAttendee'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.attendees.store');
    Route::post('events/{event}/attendees/{attendee}/status', [EventController::class, 'updateAttendeeStatus'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('attendee')
        ->name('events.attendees.status');
    Route::delete('events/{event}/attendees/{attendee}', [EventController::class, 'destroyAttendee'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('attendee')
        ->name('events.attendees.destroy');
    Route::post('events/{event}/participants', [EventController::class, 'storeParticipant'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.participants.store');
    Route::put('events/{event}/participants/{participant}', [EventController::class, 'updateParticipant'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('participant')
        ->name('events.participants.update');
    Route::post('events/{event}/participants/{participant}/status', [EventController::class, 'updateParticipantStatus'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('participant')
        ->name('events.participants.status');
    Route::delete('events/{event}/participants/{participant}', [EventController::class, 'destroyParticipant'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('participant')
        ->name('events.participants.destroy');
    Route::post('events/{event}/participants/import', [EventController::class, 'importParticipants'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.participants.import');
    Route::post('events/{event}/outcome-report', [EventController::class, 'upsertOutcomeReport'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.outcome-report.upsert');
    Route::post('events/{event}/workstreams', [EventController::class, 'storeWorkstream'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.workstreams.store');
    Route::put('events/{event}/workstreams/{workstream}', [EventController::class, 'updateWorkstream'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('workstream')
        ->name('events.workstreams.update');
    Route::delete('events/{event}/workstreams/{workstream}', [EventController::class, 'destroyWorkstream'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('workstream')
        ->name('events.workstreams.destroy');
    Route::post('events/{event}/tasks', [EventController::class, 'storeTask'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->name('events.tasks.store');
    Route::put('events/{event}/tasks/{task}', [EventController::class, 'updateTask'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('task')
        ->name('events.tasks.update');
    Route::get('events/{event}/tasks/{task}/evidence', [EventController::class, 'downloadTaskEvidence'])
        ->middleware($viewPermission('events'))
        ->whereNumber('event')
        ->whereNumber('task')
        ->name('events.tasks.evidence');
    Route::delete('events/{event}/tasks/{task}', [EventController::class, 'destroyTask'])
        ->middleware($managePermission('events'))
        ->whereNumber('event')
        ->whereNumber('task')
        ->name('events.tasks.destroy');

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
        ->middleware('permission:domain.leave.view|domain.leave.manage')
        ->name('leave-requests.store');
    Route::post('leave-requests/{leave_request}/manager-approve', [LeaveRequestController::class, 'managerApprove'])
        ->middleware('permission:domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.manager-approve');
    Route::post('leave-requests/{leave_request}/manager-reject', [LeaveRequestController::class, 'managerReject'])
        ->middleware('permission:domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.manager-reject');
    Route::post('leave-requests/{leave_request}/revoke', [LeaveRequestController::class, 'revoke'])
        ->middleware('permission:domain.leave.view|domain.leave.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.revoke');
    Route::post('leave-requests/{leave_request}/hr-approve', [LeaveRequestController::class, 'hrApprove'])
        ->middleware('permission:domain.human-resources.manage')
        ->whereNumber('leave_request')
        ->name('leave-requests.hr-approve');
    Route::post('leave-requests/{leave_request}/hr-reject', [LeaveRequestController::class, 'hrReject'])
        ->middleware('permission:domain.human-resources.manage')
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
    Route::get('assets/export', [AssetController::class, 'exportRegister'])
        ->middleware('permission:domain.assets.view|domain.assets.manage')
        ->name('assets.export');
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
    Route::post('assets/{asset}/maintenance/start', [AssetController::class, 'startMaintenance'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('asset')
        ->name('assets.maintenance.start');
    Route::post('assets/{asset}/maintenance/complete', [AssetController::class, 'completeMaintenance'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('asset')
        ->name('assets.maintenance.complete');
    Route::post('assets/{asset}/decommission', [AssetController::class, 'decommission'])
        ->middleware('permission:domain.assets.manage')
        ->whereNumber('asset')
        ->name('assets.decommission');
    Route::post('assets/{asset}/report-fault', [AssetController::class, 'reportFault'])
        ->whereNumber('asset')
        ->name('assets.report-fault');
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
    Route::post('projects/{project}/conclude', [ProjectController::class, 'conclude'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project')
        ->name('projects.conclude');
    Route::post('projects/{project}/reports', [ProjectController::class, 'createReport'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project')
        ->name('projects.reports.store');
    Route::get('projects/{project}/reports/{report}/pdf', [ProjectController::class, 'downloadReport'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project')
        ->whereNumber('report')
        ->name('projects.reports.pdf');
    Route::post('projects/{project}/closure-evidence', [ProjectController::class, 'uploadClosureEvidence'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project')
        ->name('projects.closure-evidence.store');
    Route::get('projects/{project}/closure-evidence/{evidence}', [ProjectController::class, 'downloadClosureEvidence'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project')
        ->whereNumber('evidence')
        ->name('projects.closure-evidence.download');
    Route::delete('projects/{project}/closure-evidence/{evidence}', [ProjectController::class, 'deleteClosureEvidence'])
        ->middleware('permission:domain.projects.view|domain.projects.manage')
        ->whereNumber('project')
        ->whereNumber('evidence')
        ->name('projects.closure-evidence.destroy');
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
        ->name('milestone-templates.programs');
    Route::resource('milestone-templates', MilestoneTemplateController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('projects'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('projects'));

    Route::get('staff/{staff}/profile', [StaffController::class, 'profileShow'])
        ->middleware($viewPermission('staff'))
        ->whereNumber('staff')
        ->name('staff.profile');
    Route::post('staff/{staff}/promote-manager', [StaffController::class, 'promote'])
        ->middleware($managePermission('staff'))
        ->whereNumber('staff')
        ->name('staff.promote-manager');
    Route::post('staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])
        ->middleware($managePermission('staff'))
        ->whereNumber('staff')
        ->name('staff.reset-password');
    Route::get('staff/dashboard', [StaffController::class, 'dashboard'])
        ->middleware($viewPermission('staff'))
        ->name('staff.dashboard');
    Route::get('staff/list', [StaffController::class, 'index'])
        ->middleware($viewPermission('staff'))
        ->name('staff.list');
    Route::resource('staff', StaffController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('staff'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('staff'));
    Route::resource('staff-departments', StaffDepartmentController::class)
        ->middlewareFor(['index', 'show'], $viewPermission('staff'))
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], $managePermission('staff'));

    Route::get('access-control', [AccessControlController::class, 'index'])
        ->middleware('permission:access-control.view')
        ->name('access-control.index');
    Route::get('access-control/roles', [AccessControlController::class, 'rolesPage'])
        ->middleware('permission:access-control.view')
        ->name('access-control.roles.index');
    Route::post('access-control/roles', [AccessControlController::class, 'storeRole'])
        ->middleware('permission:roles.create|roles.update')
        ->name('access-control.roles.store');
    Route::put('access-control/roles/{role}', [AccessControlController::class, 'updateRole'])
        ->middleware('permission:roles.update')
        ->whereNumber('role')
        ->name('access-control.roles.update');
    Route::delete('access-control/roles/{role}', [AccessControlController::class, 'destroyRole'])
        ->middleware('permission:roles.delete')
        ->whereNumber('role')
        ->name('access-control.roles.destroy');
    Route::get('access-control/permissions', [AccessControlController::class, 'permissionsPage'])
        ->middleware('permission:access-control.view')
        ->name('access-control.permissions.index');
    Route::post('access-control/permissions', [AccessControlController::class, 'storePermission'])
        ->middleware('permission:permissions.create|permissions.update')
        ->name('access-control.permissions.store');
    Route::put('access-control/permissions/{permission}', [AccessControlController::class, 'updatePermission'])
        ->middleware('permission:permissions.update')
        ->whereNumber('permission')
        ->name('access-control.permissions.update');
    Route::delete('access-control/permissions/{permission}', [AccessControlController::class, 'destroyPermission'])
        ->middleware('permission:permissions.delete')
        ->whereNumber('permission')
        ->name('access-control.permissions.destroy');
    Route::get('access-control/assignments', [AccessControlController::class, 'assignmentsPage'])
        ->middleware('permission:access-control.view')
        ->name('access-control.assignments.index');
    Route::post('access-control/users/{user}/roles', [AccessControlController::class, 'syncUserRoles'])
        ->middleware('permission:assignments.manage')
        ->whereNumber('user')
        ->name('access-control.users.roles.sync');
    Route::post('access-control/users/{user}/permissions', [AccessControlController::class, 'syncUserPermissions'])
        ->middleware('permission:assignments.manage')
        ->whereNumber('user')
        ->name('access-control.users.permissions.sync');
});

require __DIR__.'/settings.php';
