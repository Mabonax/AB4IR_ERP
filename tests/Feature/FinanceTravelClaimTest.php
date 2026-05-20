<?php

use App\Domains\Finance\Models\TravelClaim;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeFinanceDepartment(string $name): StaffDepartment
{
    return StaffDepartment::query()->create([
        'name' => $name,
        'description' => $name.' department',
    ]);
}

function makeFinanceStaffUser(
    StaffDepartment $department,
    string $email,
    ?StaffMember $manager = null,
    array $permissions = [],
    array $staffOverrides = [],
): array {
    $user = User::factory()->create([
        'email' => $email,
        'name' => strtok($email, '@'),
    ]);

    $staff = StaffMember::query()->create(array_merge([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'manager_id' => $manager?->id,
        'first_name' => ucfirst(strtok($email, '.')),
        'last_name' => 'Staff',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => false,
    ], $staffOverrides));

    if ($permissions !== []) {
        grantPermissions($user, $permissions);
    }

    return [$user, $staff];
}

function travelClaimPayload(int $claimantStaffId): array
{
    return [
        'claimant_staff_member_id' => $claimantStaffId,
        'claim_month' => '2026-05-01',
        'claimant_address' => '454 Lucas Mangope Rd, Unit M Mabopane, 0190',
        'vehicle_make_model' => 'Opel Corsa',
        'vehicle_type' => 'Passenger',
        'vehicle_year' => 2006,
        'engine_volume' => '1.4',
        'tariff_per_km' => 4.84,
        'home_distance_km' => 3.8,
        'trips' => [
            [
                'travel_date' => '2026-05-29',
                'route_from' => 'Work',
                'route_to' => 'Cam-A-Lot',
                'start_time' => '08:00',
                'end_time' => '18:00',
                'nature_of_duty' => 'Rental of technical support equipment',
                'actual_distance_km' => 113,
                'claimable_distance_km' => 113,
            ],
        ],
    ];
}

test('manager can create a travel claim for a direct report and totals are calculated', function () {
    $operations = makeFinanceDepartment('Operations');
    [, $ceoStaff] = makeFinanceStaffUser($operations, 'ceo.claim@example.test', staffOverrides: ['is_ceo' => true, 'is_manager' => true]);
    [$managerUser, $managerStaff] = makeFinanceStaffUser(
        $operations,
        'manager.claim@example.test',
        permissions: ['travel-claims.submit'],
        manager: $ceoStaff,
        staffOverrides: ['is_manager' => true]
    );

    $this->actingAs($managerUser)
        ->post('/finance/travel-claims', travelClaimPayload($managerStaff->id))
        ->assertRedirect();

    $claim = TravelClaim::query()->with('trips')->first();

    expect($claim)->not->toBeNull()
        ->and($claim->status)->toBe('submitted')
        ->and($claim->approval_status)->toBe('pending')
        ->and((float) $claim->total_amount)->toBe(546.92)
        ->and($claim->trips)->toHaveCount(1);
});

test('manager cannot create a travel claim for another staff member', function () {
    $operations = makeFinanceDepartment('Operations');
    [$managerUser, $managerStaff] = makeFinanceStaffUser(
        $operations,
        'manager.scope@example.test',
        permissions: ['travel-claims.submit'],
        staffOverrides: ['is_manager' => true]
    );
    [, $outsiderStaff] = makeFinanceStaffUser($operations, 'outsider.scope@example.test');

    $this->actingAs($managerUser)
        ->post('/finance/travel-claims', travelClaimPayload($outsiderStaff->id))
        ->assertForbidden();
});

test('executive approver must approve before finance can receive and pay a claim', function () {
    $operations = makeFinanceDepartment('Operations');
    $financeDepartment = makeFinanceDepartment('Admin');
    [$ceoUser, $ceoStaff] = makeFinanceStaffUser($operations, 'ceo.finance@example.test', staffOverrides: ['is_ceo' => true, 'is_manager' => true]);
    [$managerUser, $managerStaff] = makeFinanceStaffUser($operations, 'manager.finance@example.test', manager: $ceoStaff, permissions: ['travel-claims.submit'], staffOverrides: ['is_manager' => true]);
    [$financeUser] = makeFinanceStaffUser($financeDepartment, 'finance.user@example.test', permissions: ['domain.finance.view', 'domain.finance.manage']);

    $this->actingAs($managerUser)->post('/finance/travel-claims', travelClaimPayload($managerStaff->id));

    $claim = TravelClaim::query()->firstOrFail();

    $this->actingAs($financeUser)
        ->post("/finance/travel-claims/{$claim->id}/receive", ['finance_comment' => 'Received'])
        ->assertSessionHasErrors('approval_status');

    $this->actingAs($ceoUser)
        ->post("/finance/travel-claims/{$claim->id}/approve", ['approval_comment' => 'Approved'])
        ->assertSessionHasNoErrors();

    expect($claim->fresh()->approval_status)->toBe('approved');

    $this->actingAs($financeUser)
        ->post("/finance/travel-claims/{$claim->id}/receive", ['finance_comment' => 'Received'])
        ->assertSessionHasNoErrors();

    expect($claim->fresh()->status)->toBe('received');

    $this->actingAs($financeUser)
        ->post("/finance/travel-claims/{$claim->id}/pay", ['finance_comment' => 'Paid'])
        ->assertSessionHasNoErrors();

    expect($claim->fresh()->status)->toBe('paid')
        ->and($claim->fresh()->finance_paid_at)->not->toBeNull();
});

test('manager sees only claims they submitted or manage while finance sees all claims', function () {
    $operations = makeFinanceDepartment('Operations');
    $financeDepartment = makeFinanceDepartment('Admin');
    [$ceoUser, $ceoStaff] = makeFinanceStaffUser($operations, 'ceo.index@example.test', staffOverrides: ['is_ceo' => true, 'is_manager' => true]);
    [$managerUser, $managerStaff] = makeFinanceStaffUser($operations, 'manager.index@example.test', manager: $ceoStaff, permissions: ['travel-claims.submit'], staffOverrides: ['is_manager' => true]);
    [$otherManagerUser, $otherManagerStaff] = makeFinanceStaffUser($operations, 'other.manager@example.test', manager: $ceoStaff, permissions: ['travel-claims.submit'], staffOverrides: ['is_manager' => true]);
    [$financeUser] = makeFinanceStaffUser($financeDepartment, 'finance.index@example.test', permissions: ['domain.finance.view']);

    $this->actingAs($managerUser)->post('/finance/travel-claims', travelClaimPayload($managerStaff->id));
    $this->actingAs($otherManagerUser)->post('/finance/travel-claims', travelClaimPayload($otherManagerStaff->id));

    $this->actingAs($managerUser)
        ->get('/finance/travel-claims')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Finance/TravelClaims/Index')
            ->has('claims', 1)
            ->where('claims.0.claimant.name', trim($managerStaff->first_name.' '.$managerStaff->last_name))
        );

    $this->actingAs($financeUser)
        ->get('/finance/travel-claims')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Finance/TravelClaims/Index')
            ->has('claims', 2)
        );
});

test('travel claim create page exposes self and direct report claimant options only', function () {
    $operations = makeFinanceDepartment('Operations');
    [$managerUser, $managerStaff] = makeFinanceStaffUser($operations, 'manager.create@example.test', permissions: ['travel-claims.submit'], staffOverrides: ['is_manager' => true]);
    makeFinanceStaffUser($operations, 'outsider.create@example.test');

    $this->actingAs($managerUser)
        ->get('/finance/travel-claims/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Finance/TravelClaims/Create')
            ->has('claimants', 1)
            ->where('claimants.0.id', $managerStaff->id)
        );
});

test('travel claim pdf export is available to visible users', function () {
    $operations = makeFinanceDepartment('Operations');
    [, $ceoStaff] = makeFinanceStaffUser($operations, 'ceo.pdf@example.test', staffOverrides: ['is_ceo' => true, 'is_manager' => true]);
    [$managerUser, $managerStaff] = makeFinanceStaffUser($operations, 'manager.pdf@example.test', manager: $ceoStaff, permissions: ['travel-claims.submit'], staffOverrides: ['is_manager' => true]);

    $this->actingAs($managerUser)->post('/finance/travel-claims', travelClaimPayload($managerStaff->id));
    $claim = TravelClaim::query()->firstOrFail();

    $this->actingAs($managerUser)
        ->get("/finance/travel-claims/{$claim->id}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('non manager cannot submit a travel claim even with submit permission', function () {
    $operations = makeFinanceDepartment('Operations');
    [$user, $staff] = makeFinanceStaffUser($operations, 'staff.claim@example.test', permissions: ['travel-claims.submit']);

    $this->actingAs($user)
        ->post('/finance/travel-claims', travelClaimPayload($staff->id))
        ->assertForbidden();
});
