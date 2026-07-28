<?php

use App\Domains\Compliance\Models\ComplianceRecord;
use App\Domains\Organisation\Models\Organisation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeComplianceManager(): User
{
    $user = grantDomainAccess(User::factory()->create(), 'compliance');

    $department = StaffDepartment::query()->create([
        'name' => 'Compliance '.Str::upper(Str::random(4)),
        'description' => 'Compliance operations',
    ]);

    StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Lerato',
        'last_name' => 'Compliance',
        'email' => 'compliance-'.Str::lower(Str::random(6)).'@example.com',
        'employee_number' => 'EMP-COMP-'.Str::upper(Str::random(6)),
        'status' => 'active',
    ]);

    return $user;
}

test('compliance managers can view the compliance registry page', function () {
    $user = makeComplianceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action NPC',
        'registration_number' => 'POA-COMP-001',
        'organisation_type' => 'NPC',
        'status' => 'active',
    ]);

    ComplianceRecord::query()->create([
        'organisation_id' => $organisation->id,
        'title' => 'Annual CIPC return',
        'compliance_area' => 'CIPC',
        'filing_frequency' => 'annual',
        'due_date' => now()->addDays(10)->toDateString(),
        'status' => 'in_progress',
        'owner_name' => 'Governance Lead',
    ]);

    $this->actingAs($user)
        ->get(route('organization.compliance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Organization/Compliance')
            ->where('stats.total', 1)
            ->where('stats.due_soon', 1)
            ->where('stats.submitted', 0)
            ->where('stats.overdue', 0)
            ->where('records.0.title', 'Annual CIPC return')
            ->where('records.0.organisation_name', 'Programme of Action NPC')
        );
});

test('compliance managers can create and update compliance records', function () {
    $user = makeComplianceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action Foundation',
        'registration_number' => 'POA-COMP-002',
        'organisation_type' => 'NPC',
        'status' => 'active',
    ]);

    $create = $this->actingAs($user)->post(route('organization.compliance.store'), [
        'organisation_id' => $organisation->id,
        'title' => 'NPO narrative report',
        'compliance_area' => 'NPO Directorate',
        'reference_code' => 'NPO-2026-NARRATIVE',
        'filing_frequency' => 'annual',
        'due_date' => '2026-11-30',
        'submitted_at' => null,
        'status' => 'planned',
        'owner_name' => 'Compliance Officer',
        'notes' => 'Prepare after annual board approval.',
    ]);

    $create->assertRedirect();
    $create->assertSessionHas('success', 'Compliance record added.');

    $record = ComplianceRecord::query()->firstOrFail();

    $this->assertDatabaseHas('compliance_records', [
        'id' => $record->id,
        'title' => 'NPO narrative report',
        'status' => 'planned',
    ]);

    $update = $this->actingAs($user)->put(route('organization.compliance.update', $record->id), [
        'organisation_id' => $organisation->id,
        'title' => 'NPO narrative report',
        'compliance_area' => 'NPO Directorate',
        'reference_code' => 'NPO-2026-NARRATIVE',
        'filing_frequency' => 'annual',
        'due_date' => '2026-11-30',
        'submitted_at' => '2026-11-12',
        'status' => 'submitted',
        'owner_name' => 'Compliance Officer',
        'notes' => 'Submitted after annual board approval.',
    ]);

    $update->assertRedirect();
    $update->assertSessionHas('success', 'Compliance record updated.');

    $this->assertDatabaseHas('compliance_records', [
        'id' => $record->id,
        'status' => 'submitted',
    ]);

    expect($record->fresh()->submitted_at?->toDateString())->toBe('2026-11-12');
});

test('dashboard exposes the compliance widget for compliance users', function () {
    $user = makeComplianceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action Trust',
        'registration_number' => 'POA-COMP-003',
        'organisation_type' => 'Hybrid',
        'status' => 'active',
    ]);

    ComplianceRecord::query()->create([
        'organisation_id' => $organisation->id,
        'title' => 'SARS exempt entity submission',
        'compliance_area' => 'SARS',
        'filing_frequency' => 'annual',
        'due_date' => now()->subDays(5)->toDateString(),
        'status' => 'overdue',
        'owner_name' => 'Finance and Compliance',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.secondary.0.key', 'compliance')
            ->where('dashboard.secondary.0.title', 'Compliance tracker')
            ->where('dashboard.secondary.0.value', 1)
        );
});
