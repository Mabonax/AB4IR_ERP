<?php

use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Models\AssetAssignment;
use App\Domains\Assets\Models\AssetCategory;
use App\Domains\Assets\Models\AssetDecommissionRecord;
use App\Domains\Assets\Models\AssetMaintenanceRecord;
use App\Domains\Assets\Services\AssetService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'domain.assets.view',
        'domain.assets.manage',
        'domain.task-management.view',
        'technical-tickets.respond',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

function makeAssetUser(StaffDepartment $department, string $email, array $permissions = []): array
{
    $user = User::factory()->create([
        'email' => $email,
        'name' => strtok($email, '@'),
    ]);

    $staff = StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => ucfirst(strtok($email, '.')),
        'last_name' => 'User',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $user->staffMember()->save($staff);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return [$user, $staff];
}

function makeAssetCategory(): AssetCategory
{
    return AssetCategory::query()->create([
        'name' => 'Laptops',
        'description' => 'Portable devices',
    ]);
}

function makeAsset(AssetCategory $category, ?StaffMember $staff = null, string $status = 'unassigned'): Asset
{
    static $assetSequence = 1;
    $sequence = $assetSequence++;

    return Asset::query()->create([
        'asset_category_id' => $category->id,
        'staff_member_id' => $staff?->id,
        'name' => 'ThinkPad T14 '.$sequence,
        'type' => 'Laptop',
        'model_name' => 'Gen 4',
        'asset_code' => 'AST-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
        'serial_state' => 'recorded',
        'serial_number' => 'SN-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
        'status' => $status,
    ]);
}

test('asset maintenance workflow returns active assignment and restores asset to available pool', function () {
    $technical = StaffDepartment::query()->create(['name' => 'Technical', 'description' => 'Technical']);
    $operations = StaffDepartment::query()->create(['name' => 'Operations', 'description' => 'Operations']);
    [$manager] = makeAssetUser($technical, 'asset.manager@example.test', ['domain.assets.view', 'domain.assets.manage']);
    [$assigneeUser, $assigneeStaff] = makeAssetUser($operations, 'asset.assignee@example.test');
    $asset = makeAsset(makeAssetCategory(), $assigneeStaff, 'assigned');

    $assignment = AssetAssignment::query()->create([
        'asset_id' => $asset->id,
        'department_id' => $operations->id,
        'staff_member_id' => $assigneeStaff->id,
        'assigned_by' => $manager->id,
        'assigned_at' => now()->subDay(),
        'notes' => 'Issued to staff',
    ]);

    $this->actingAs($manager)
        ->post(route('assets.maintenance.start', $asset), [
            'issue_summary' => 'Battery failure',
            'maintenance_notes' => 'Sent to workshop',
        ])
        ->assertRedirect();

    $asset->refresh();
    $assignment->refresh();
    $record = AssetMaintenanceRecord::query()->firstOrFail();

    expect($asset->status)->toBe('maintenance')
        ->and($asset->staff_member_id)->toBeNull()
        ->and($assignment->returned_at)->not->toBeNull()
        ->and($record->status)->toBe('in_progress');

    $this->actingAs($manager)
        ->post(route('assets.maintenance.complete', $asset), [
            'completion_notes' => 'Battery replaced successfully',
        ])
        ->assertRedirect();

    $asset->refresh();
    $record->refresh();

    expect($asset->status)->toBe('unassigned')
        ->and($record->status)->toBe('completed')
        ->and($record->completed_at)->not->toBeNull();
});

test('asset decommission workflow retires the asset and records the reason', function () {
    $technical = StaffDepartment::query()->create(['name' => 'Technical', 'description' => 'Technical']);
    [$manager] = makeAssetUser($technical, 'decommission.manager@example.test', ['domain.assets.view', 'domain.assets.manage']);
    $asset = makeAsset(makeAssetCategory());

    $this->actingAs($manager)
        ->post(route('assets.decommission', $asset), [
            'reason' => 'Beyond economic repair',
            'notes' => 'Board approved replacement.',
        ])
        ->assertRedirect();

    $asset->refresh();
    $record = AssetDecommissionRecord::query()->firstOrFail();

    expect($asset->status)->toBe('retired')
        ->and($record->reason)->toBe('Beyond economic repair');
});

test('assigned staff member can report a fault and it lands in the technical support queue', function () {
    $technical = StaffDepartment::query()->create(['name' => 'Technical', 'description' => 'Technical']);
    $operations = StaffDepartment::query()->create(['name' => 'Operations', 'description' => 'Operations']);
    [$staffUser, $staff] = makeAssetUser($operations, 'fault.reporter@example.test', ['domain.task-management.view']);
    $asset = makeAsset(makeAssetCategory(), $staff, 'assigned');

    AssetAssignment::query()->create([
        'asset_id' => $asset->id,
        'department_id' => $operations->id,
        'staff_member_id' => $staff->id,
        'assigned_by' => $staffUser->id,
        'assigned_at' => now()->subHours(3),
        'notes' => 'Issued to staff',
    ]);

    $this->actingAs($staffUser)
        ->post(route('assets.report-fault', $asset), [
            'title' => 'Laptop not powering on',
            'description' => 'The device fails to start after charging.',
            'priority' => 'high',
        ])
        ->assertRedirect();

    $ticket = SupportTicket::query()->firstOrFail();

    expect($ticket->asset_id)->toBe($asset->id)
        ->and($ticket->requester_user_id)->toBe($staffUser->id)
        ->and($ticket->assigned_department_id)->toBe($technical->id);
});

test('asset register export streams a printable spreadsheet row set', function () {
    $technical = StaffDepartment::query()->create(['name' => 'Technical', 'description' => 'Technical']);
    [$manager] = makeAssetUser($technical, 'export.manager@example.test', ['domain.assets.view', 'domain.assets.manage']);
    $asset = makeAsset(makeAssetCategory());

    $response = $this->actingAs($manager)
        ->get(route('assets.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('Asset Code')
        ->and($content)->toContain($asset->asset_code)
        ->and($content)->toContain($asset->name);
});

test('asset manager dashboard retains department portfolio across assignment maintenance and retirement states', function () {
    $technical = StaffDepartment::query()->create(['name' => 'Technical', 'description' => 'Technical']);
    [$managerUser, $managerStaff] = makeAssetUser($technical, 'dashboard.manager@example.test', ['domain.assets.view', 'domain.assets.manage']);
    $category = makeAssetCategory();

    $assignedAsset = makeAsset($category, $managerStaff, 'assigned');
    AssetAssignment::query()->create([
        'asset_id' => $assignedAsset->id,
        'department_id' => $technical->id,
        'staff_member_id' => $managerStaff->id,
        'assigned_by' => $managerUser->id,
        'assigned_at' => now()->subDay(),
    ]);

    $maintenanceAsset = makeAsset($category, null, 'maintenance');
    AssetAssignment::query()->create([
        'asset_id' => $maintenanceAsset->id,
        'department_id' => $technical->id,
        'staff_member_id' => $managerStaff->id,
        'assigned_by' => $managerUser->id,
        'assigned_at' => now()->subDays(2),
        'returned_at' => now()->subDay(),
        'returned_by' => $managerUser->id,
        'notes' => 'Returned for maintenance',
    ]);
    AssetMaintenanceRecord::query()->create([
        'asset_id' => $maintenanceAsset->id,
        'started_by_user_id' => $managerUser->id,
        'issue_summary' => 'Display issue',
        'status' => 'in_progress',
        'started_at' => now()->subDay(),
    ]);

    $retiredAsset = makeAsset($category, null, 'retired');
    AssetAssignment::query()->create([
        'asset_id' => $retiredAsset->id,
        'department_id' => $technical->id,
        'staff_member_id' => $managerStaff->id,
        'assigned_by' => $managerUser->id,
        'assigned_at' => now()->subDays(4),
        'returned_at' => now()->subDays(3),
        'returned_by' => $managerUser->id,
        'notes' => 'Returned for decommissioning',
    ]);
    AssetDecommissionRecord::query()->create([
        'asset_id' => $retiredAsset->id,
        'decommissioned_by_user_id' => $managerUser->id,
        'reason' => 'Beyond repair',
        'decommissioned_at' => now()->subDays(2),
    ]);

    $data = $this->actingAs($managerUser)
        ->app
        ->make(AssetService::class)
        ->managerDashboardData();

    expect($data['stats']['portfolioAssets'])->toBe(3)
        ->and($data['stats']['staffAssets'])->toBe(1)
        ->and($data['stats']['maintenanceAssets'])->toBe(1)
        ->and($data['stats']['retiredAssets'])->toBe(1)
        ->and(collect($data['assetRows'])->pluck('status')->all())->toEqualCanonicalizing(['assigned', 'maintenance', 'retired']);
});
