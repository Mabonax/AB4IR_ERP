<?php

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\AttendanceEntry;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Services\ProjectAttendanceWorkflowService;
use App\Domains\Projects\Services\ProjectMilestoneAssessmentService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\NextOfKin;
use App\Models\Provinces;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-05-16 09:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function makeWorkflowGraph(): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Workflow Department '.Str::upper(Str::random(4)),
        'description' => 'Workflow Department',
    ]);

    $managerUser = User::factory()->create();
    $manager = StaffMember::query()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'first_name' => 'Will',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Workflow Program',
        'description' => 'Workflow Program',
        'slug' => 'workflow-program-'.Str::lower(Str::random(5)),
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Workflow Project',
        'start_date' => '2026-05-11',
        'end_date' => '2026-05-30',
        'status' => 'active',
        'description' => 'Workflow project',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Workflow Province '.Str::upper(Str::random(4)),
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Faye',
        'surname' => 'Trainer',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '1 Training Street',
        'email' => 'facilitator-'.Str::lower(Str::random(8)).'@example.com',
        'cell' => '0712345678',
        'specialization' => 'Training',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Workflow Hall',
    ]);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Nora',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0710000000',
        'email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $beneficiary = \App\Domains\Beneficiaries\Models\Beneficiary::query()->create([
        'name' => 'Ben',
        'surname' => 'Eficiary',
        'dob' => now()->subYears(21),
        'age' => 21,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'male',
        'project_id' => $project->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    $template = ProgramMilestoneTemplate::query()->create([
        'program_id' => $program->id,
        'title' => 'Module 1 Template',
        'description' => 'Module 1 Template',
        'sort_order' => 1,
        'max_score' => 10,
    ]);

    $milestone = ProjectMilestone::query()->create([
        'project_id' => $project->id,
        'program_milestone_template_id' => $template->id,
        'title' => 'Module 1',
        'description' => 'Module 1',
        'sort_order' => 1,
        'max_score' => 10,
    ]);

    return compact('managerUser', 'project', 'location', 'facilitator', 'beneficiary', 'milestone');
}

test('attendance workflow saves registers for active enrolled beneficiaries only', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectAttendanceWorkflowService::class);

    $register = $service->saveRegister($graph['location'], $graph['facilitator'], [
        'attendance_date' => '2026-05-15',
        'entries' => [
            [
                'beneficiary_id' => $graph['beneficiary']->id,
                'status' => 'excused',
                'excused_reason' => 'Medical appointment',
            ],
        ],
    ]);

    expect($register->is_holiday)->toBeFalse();
    $this->assertDatabaseHas('attendance_entries', [
        'attendance_register_id' => $register->id,
        'beneficiary_id' => $graph['beneficiary']->id,
        'status' => 'excused',
    ]);
});

test('attendance workflow rejects weekend registers', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectAttendanceWorkflowService::class);

    expect(fn () => $service->saveRegister($graph['location'], $graph['facilitator'], [
        'attendance_date' => '2026-05-16',
        'entries' => [
            [
                'beneficiary_id' => $graph['beneficiary']->id,
                'status' => 'present',
                'excused_reason' => null,
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

test('attendance workflow rejects future dated registers even when the project end date is later', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectAttendanceWorkflowService::class);

    expect(fn () => $service->saveRegister($graph['location'], $graph['facilitator'], [
        'attendance_date' => '2026-05-19',
        'entries' => [
            [
                'beneficiary_id' => $graph['beneficiary']->id,
                'status' => 'present',
                'excused_reason' => null,
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

test('attendance workflow rejects corrections older than the allowed window', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectAttendanceWorkflowService::class);

    expect(fn () => $service->saveRegister($graph['location'], $graph['facilitator'], [
        'attendance_date' => '2026-05-14',
        'entries' => [
            [
                'beneficiary_id' => $graph['beneficiary']->id,
                'status' => 'present',
                'excused_reason' => null,
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

test('attendance workflow rejects capture once the project is no longer active', function () {
    $graph = makeWorkflowGraph();
    $graph['project']->update([
        'status' => 'completed',
    ]);

    $service = app(ProjectAttendanceWorkflowService::class);

    expect(fn () => $service->saveRegister($graph['location']->fresh('project', 'enrollments.beneficiary'), $graph['facilitator'], [
        'attendance_date' => '2026-05-16',
        'entries' => [
            [
                'beneficiary_id' => $graph['beneficiary']->id,
                'status' => 'present',
                'excused_reason' => null,
            ],
        ],
    ]))->toThrow(ValidationException::class);
});

test('attendance workflow marks holiday and clears existing entries', function () {
    $graph = makeWorkflowGraph();
    $this->actingAs($graph['managerUser']);
    $service = app(ProjectAttendanceWorkflowService::class);

    $register = $service->saveRegister($graph['location'], $graph['facilitator'], [
        'attendance_date' => '2026-05-15',
        'entries' => [
            [
                'beneficiary_id' => $graph['beneficiary']->id,
                'status' => 'present',
                'excused_reason' => null,
            ],
        ],
    ]);

    expect(AttendanceEntry::query()->where('attendance_register_id', $register->id)->count())->toBe(1);

    $holiday = $service->markHoliday($graph['location'], [
        'attendance_date' => '2026-05-15',
        'holiday_reason' => 'Public event',
    ]);

    expect($holiday->is_holiday)->toBeTrue();
    expect(AttendanceEntry::query()->where('attendance_register_id', $holiday->id)->count())->toBe(0);
});

test('milestone assessment service derives failed or completed status from score', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectMilestoneAssessmentService::class);

    $assessment = $service->storeAssessment($graph['location'], $graph['milestone'], [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 4,
        'comments' => 'Needs work',
    ], $graph['facilitator']);

    expect($assessment->status)->toBe('failed');

    $assessment = $service->storeAssessment($graph['location'], $graph['milestone'], [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 8,
        'comments' => 'Good work',
    ], $graph['facilitator']);

    expect($assessment->status)->toBe('completed');
    $this->assertDatabaseHas('project_milestone_assessments', [
        'id' => $assessment->id,
        'score' => 8,
        'status' => 'completed',
    ]);
});

test('milestone assessment service uses the configured pass mark when deriving status', function () {
    $graph = makeWorkflowGraph();
    $graph['milestone']->update([
        'max_score' => 10,
        'pass_mark' => 8,
    ]);
    $service = app(ProjectMilestoneAssessmentService::class);

    $assessment = $service->storeAssessment($graph['location'], $graph['milestone']->fresh(), [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 7,
        'comments' => 'Below custom threshold',
    ], $graph['facilitator']);

    expect($assessment->status)->toBe('failed');

    $assessment = $service->storeAssessment($graph['location'], $graph['milestone']->fresh(), [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 8,
        'comments' => 'Meets custom threshold',
    ], $graph['facilitator']);

    expect($assessment->status)->toBe('completed');
});

test('milestone assessment service can save a milestone score for multiple beneficiaries at one location', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectMilestoneAssessmentService::class);

    $secondBeneficiary = \App\Domains\Beneficiaries\Models\Beneficiary::query()->create([
        'name' => 'Beth',
        'surname' => 'Second',
        'dob' => now()->subYears(22),
        'age' => 22,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-second-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0733333333',
        'gender' => 'female',
        'project_id' => $graph['project']->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => \App\Models\NextOfKin::query()->value('id'),
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'beneficiary_id' => $secondBeneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    $assessments = $service->storeBulkAssessments($graph['location']->fresh('project', 'enrollments.beneficiary'), $graph['milestone'], [
        [
            'beneficiary_id' => $graph['beneficiary']->id,
            'score' => 9,
            'comments' => 'Strong',
        ],
        [
            'beneficiary_id' => $secondBeneficiary->id,
            'score' => 4,
            'comments' => 'Needs support',
        ],
    ], $graph['facilitator']);

    expect($assessments)->toHaveCount(2);

    $this->assertDatabaseHas('project_milestone_assessments', [
        'beneficiary_id' => $graph['beneficiary']->id,
        'status' => 'completed',
    ]);

    $this->assertDatabaseHas('project_milestone_assessments', [
        'beneficiary_id' => $secondBeneficiary->id,
        'status' => 'failed',
    ]);
});

test('location progress page exposes milestone and beneficiary assessment workspace props', function () {
    $graph = makeWorkflowGraph();
    grantDomainAccess($graph['managerUser'], 'projects');

    $service = app(ProjectMilestoneAssessmentService::class);
    $service->storeAssessment($graph['location'], $graph['milestone'], [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 8,
        'comments' => 'Ready',
    ], $graph['facilitator']);

    $this->actingAs($graph['managerUser'])
        ->get(route('project-locations.progress', $graph['location']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ProjectLocations/Progress')
            ->where('location.id', $graph['location']->id)
            ->where('summary.beneficiaries_enrolled', 1)
            ->where('summary.milestones_attached', 1)
            ->where('summary.assessments_completed', 1)
            ->where('summary.passed_assessments', 1)
            ->where('canAssess', true)
            ->where('assessmentUnavailableMessage', null)
            ->has('milestoneOptions', 1)
            ->has('beneficiaries', 1)
            ->where('beneficiaries.0.status', 'Passed')
            ->where('beneficiaries.0.assessments.'.$graph['milestone']->id.'.status', 'completed')
        );
});

test('milestone assessment service rejects scores above the milestone maximum', function () {
    $graph = makeWorkflowGraph();
    $service = app(ProjectMilestoneAssessmentService::class);

    expect(fn () => $service->storeAssessment($graph['location'], $graph['milestone'], [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 11,
        'comments' => null,
    ], $graph['facilitator']))->toThrow(ValidationException::class);
});

test('milestone assessment service rejects corrections once the project is no longer active', function () {
    $graph = makeWorkflowGraph();
    $graph['project']->update([
        'status' => 'completed',
    ]);

    $service = app(ProjectMilestoneAssessmentService::class);

    expect(fn () => $service->storeAssessment($graph['location']->fresh('project'), $graph['milestone'], [
        'beneficiary_id' => $graph['beneficiary']->id,
        'score' => 8,
        'comments' => 'Late correction',
    ], $graph['facilitator']))->toThrow(ValidationException::class);
});
