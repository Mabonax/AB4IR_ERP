<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function createProjectLearningFixture(): array
{
    $program = Program::query()->create([
        'title' => 'Learning Mapping Programme',
        'description' => 'Learning mapping test programme',
        'slug' => 'learning-mapping-programme',
    ]);

    $department = StaffDepartment::query()->create([
        'name' => 'Learning Mapping Department',
        'description' => 'Learning Mapping Department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Learning',
        'last_name' => 'Manager',
        'email' => fake()->unique()->safeEmail(),
        'employee_number' => 'LM-'.fake()->unique()->numerify('###'),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => true,
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Learning Delivery Project',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $province = Provinces::query()->create(['name' => 'Gauteng']);

    $facilitator = Facilitator::query()->create([
        'name' => 'Learning',
        'surname' => 'Facilitator',
        'dob' => '1990-01-01',
        'id_number' => fake()->unique()->numerify('#############'),
        'address' => '1 Learning Street',
        'email' => 'learning.facilitator@example.test',
        'cell' => '0730000000',
        'specialization' => 'Digital Skills',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Learning Lab',
    ]);

    $beneficiary = Beneficiary::query()->create([
        'name' => 'Learning',
        'surname' => 'Beneficiary',
        'dob' => now()->subYears(22),
        'age' => 22,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'learning.beneficiary@example.test',
        'phone' => '0720000000',
        'gender' => 'female',
        'project_id' => $project->id,
        'attendance_status' => 'active',
        'status' => 'enrolled',
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    return compact('project', 'beneficiary', 'facilitator', 'location');
}

test('ERP project can map to an LMS offering and records history', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'projects');
    $fixture = createProjectLearningFixture();

    Http::fake([
        'https://lms.example.test/integrations/erp/project-mappings' => Http::response([
            'status' => 'mapped',
            'mapping' => [
                'offering' => ['id' => 12, 'name' => 'Drone Cohort A', 'status' => 'active'],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson("/projects/{$fixture['project']->id}/learning/mappings", ['lms_offering_id' => 12])
        ->assertOk()
        ->assertJsonPath('status', 'mapped');

    $this->assertDatabaseHas('project_learning_mappings', [
        'project_id' => $fixture['project']->id,
        'lms_offering_id' => '12',
    ]);
    $this->assertDatabaseHas('project_history', [
        'project_id' => $fixture['project']->id,
        'action' => 'lms_mapping_updated',
    ]);
});

test('ERP project mapping rejects failed LMS bridge responses without creating local mapping', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'projects');
    $fixture = createProjectLearningFixture();

    Http::fake([
        'https://lms.example.test/integrations/erp/project-mappings' => Http::response([
            'message' => 'CSRF token mismatch.',
        ], 419),
    ]);

    $this->actingAs($user)
        ->postJson("/projects/{$fixture['project']->id}/learning/mappings", ['lms_offering_id' => 12])
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'CSRF token mismatch.');

    $this->assertDatabaseMissing('project_learning_mappings', [
        'project_id' => $fixture['project']->id,
        'lms_offering_id' => '12',
    ]);
});

test('eligible project beneficiary can be provisioned through LMS command', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'projects');
    $fixture = createProjectLearningFixture();
    $fixture['project']->learningMappings()->create([
        'lms_offering_id' => '12',
        'status' => 'active',
        'mapped_at' => now(),
    ]);

    Http::fake([
        'https://lms.example.test/integrations/erp/provisioning/learners' => Http::response([
            'status' => 'invitation_created',
            'reason' => 'Learner invitation created.',
        ]),
    ]);

    $this->actingAs($user)
        ->postJson("/projects/{$fixture['project']->id}/learning/provision-learners", [
            'beneficiary_ids' => [$fixture['beneficiary']->id],
        ])
        ->assertOk()
        ->assertJsonPath('status', 'processed')
        ->assertJsonPath('items.0.status', 'invitation_created');
});

test('ERP beneficiary profile can provision LMS access through mapped project', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'beneficiaries');
    $fixture = createProjectLearningFixture();
    $fixture['project']->learningMappings()->create([
        'lms_offering_id' => '12',
        'status' => 'active',
        'mapped_at' => now(),
    ]);

    Http::fake([
        'https://lms.example.test/integrations/erp/provisioning/learners' => Http::response([
            'status' => 'invitation_created',
            'reason' => 'Learner invitation created.',
            'activation_url' => 'https://lms.example.test/register/invite/one-time-token',
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.lms-access.provision', $fixture['beneficiary']))
        ->assertRedirect(route('beneficiaries.show', $fixture['beneficiary']))
        ->assertSessionHas('success', 'Learner invitation created.')
        ->assertSessionHas('activation_url');

    Http::assertSent(fn ($request) => $request->url() === 'https://lms.example.test/integrations/erp/provisioning/learners'
        && $request['erp_beneficiary_id'] === (string) $fixture['beneficiary']->id);
});

test('ERP beneficiary profile provisioning reports missing LMS mapping before calling LMS', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'beneficiaries');
    $fixture = createProjectLearningFixture();

    Http::fake();

    $this->actingAs($user)
        ->post(route('beneficiaries.lms-access.provision', $fixture['beneficiary']))
        ->assertRedirect(route('beneficiaries.show', $fixture['beneficiary']))
        ->assertSessionHas('error', 'Project has no LMS learning delivery mapping.');

    Http::assertNothingSent();
});

test('ERP project learner provisioning returns an error response when mapping is missing', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'projects');
    $fixture = createProjectLearningFixture();

    Http::fake();

    $this->actingAs($user)
        ->postJson("/projects/{$fixture['project']->id}/learning/provision-learners", [
            'beneficiary_ids' => [$fixture['beneficiary']->id],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'Project has no LMS learning delivery mapping.');

    Http::assertNothingSent();
});

test('direct browser visit to ERP project learning mapping route redirects to project page', function () {
    $user = grantDomainAccess(User::factory()->create(), 'projects');
    $fixture = createProjectLearningFixture();

    $this->actingAs($user)
        ->get("/projects/{$fixture['project']->id}/learning/mappings")
        ->assertRedirect(route('projects.show', $fixture['project']))
        ->assertSessionHas('error', 'Use Configure Learning Delivery from the project page to map LMS delivery.');
});

test('ERP facilitator profile can provision LMS access through mapped assignment', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'facilitators');
    $fixture = createProjectLearningFixture();
    $fixture['project']->learningMappings()->create([
        'lms_offering_id' => '12',
        'status' => 'active',
        'mapped_at' => now(),
    ]);

    Http::fake([
        'https://lms.example.test/integrations/erp/provisioning/facilitators' => Http::response([
            'status' => 'invitation_created',
            'reason' => 'Facilitator invitation created.',
            'activation_url' => 'https://lms.example.test/register/invite/facilitator-token',
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('facilitators.lms-access.provision', $fixture['facilitator']))
        ->assertRedirect(route('facilitators.show', $fixture['facilitator']))
        ->assertSessionHas('success', 'Facilitator invitation created.')
        ->assertSessionHas('activation_url');

    Http::assertSent(fn ($request) => $request->url() === 'https://lms.example.test/integrations/erp/provisioning/facilitators'
        && $request['erp_facilitator_id'] === (string) $fixture['facilitator']->id);
});

test('ineligible beneficiary is rejected with clear reason before LMS command', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'projects');
    $fixture = createProjectLearningFixture();
    $fixture['project']->learningMappings()->create([
        'lms_offering_id' => '12',
        'status' => 'active',
        'mapped_at' => now(),
    ]);
    $fixture['beneficiary']->update(['status' => 'exited']);

    Http::fake();

    $this->actingAs($user)
        ->postJson("/projects/{$fixture['project']->id}/learning/provision-learners", [
            'beneficiary_ids' => [$fixture['beneficiary']->id],
        ])
        ->assertOk()
        ->assertJsonPath('items.0.status', 'rejected');

    Http::assertNothingSent();
});

test('teaching eligibility accepts assigned project facilitator and rejects unassigned facilitator', function () {
    config(['services.lms_bridge.token' => 'bridge-token']);
    $fixture = createProjectLearningFixture();
    $unassigned = Facilitator::query()->create([
        'name' => 'Unassigned',
        'surname' => 'Facilitator',
        'dob' => '1990-01-01',
        'id_number' => fake()->unique()->numerify('#############'),
        'address' => '2 Learning Street',
        'email' => 'unassigned.facilitator@example.test',
        'cell' => '0730000001',
        'specialization' => 'Digital Skills',
    ]);

    $this->withHeader('X-LMS-BRIDGE-TOKEN', 'bridge-token')
        ->getJson("/integrations/lms/projects/{$fixture['project']->id}/facilitators/{$fixture['facilitator']->id}/teaching-eligibility")
        ->assertOk()
        ->assertJsonPath('eligible', true);

    $this->withHeader('X-LMS-BRIDGE-TOKEN', 'bridge-token')
        ->getJson("/integrations/lms/projects/{$fixture['project']->id}/facilitators/{$unassigned->id}/teaching-eligibility")
        ->assertOk()
        ->assertJsonPath('eligible', false)
        ->assertJsonPath('reason', 'Facilitator is not currently assigned to this ERP project.');
});

test('unauthorized ERP user cannot map project learning delivery', function () {
    $user = User::factory()->create();
    $fixture = createProjectLearningFixture();

    $this->actingAs($user)
        ->postJson("/projects/{$fixture['project']->id}/learning/mappings", ['lms_offering_id' => 12])
        ->assertForbidden();
});

test('ERP beneficiary profile can request LMS invitation resend without receiving a password', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'beneficiaries');
    $fixture = createProjectLearningFixture();

    Http::fake([
        'https://lms.example.test/integrations/erp/invitations/resend' => Http::response([
            'status' => 'invitation_resent',
            'activation_url' => 'https://lms.example.test/register/invite/one-time-token',
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.lms-invitation.resend', $fixture['beneficiary']))
        ->assertRedirect(route('beneficiaries.show', $fixture['beneficiary']))
        ->assertSessionHas('success', 'LMS invitation resent.')
        ->assertSessionHas('activation_url');

    Http::assertSent(fn ($request) => $request->url() === 'https://lms.example.test/integrations/erp/invitations/resend'
        && $request['identity_type'] === 'beneficiary'
        && $request['erp_identity_id'] === (string) $fixture['beneficiary']->id
        && ! array_key_exists('password', $request->data()));
});

test('ERP beneficiary suspension asks LMS to suspend access while preserving local lifecycle record', function () {
    config(['services.lms.app_url' => 'https://lms.example.test', 'services.lms_bridge.token' => 'bridge-token']);
    $user = grantDomainAccess(User::factory()->create(), 'beneficiaries');
    $fixture = createProjectLearningFixture();

    Http::fake([
        'https://lms.example.test/integrations/erp/access-lifecycle' => Http::response([
            'status' => 'suspended',
            'access_state' => 'suspended',
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('beneficiaries.suspend', $fixture['beneficiary']), [
            'reason' => 'Policy suspension test',
        ])
        ->assertRedirect(route('beneficiaries.show', $fixture['beneficiary']));

    expect($fixture['beneficiary']->refresh()->status)->toBe('suspended');

    Http::assertSent(fn ($request) => $request->url() === 'https://lms.example.test/integrations/erp/access-lifecycle'
        && $request['identity_type'] === 'beneficiary'
        && $request['erp_identity_id'] === (string) $fixture['beneficiary']->id
        && $request['action'] === 'suspend');
});
