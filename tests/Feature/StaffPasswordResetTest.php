<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('staff manager can reset a staff members password', function () {
    $admin = User::factory()->create();
    grantDomainAccess($admin, 'staff');

    $department = StaffDepartment::query()->create([
        'name' => 'Admin',
        'description' => 'Administration',
    ]);

    $staffUser = User::factory()->create([
        'email' => 'locked.staff@example.test',
        'password' => 'OldPass123!',
    ]);

    $staff = StaffMember::query()->create([
        'user_id' => $staffUser->id,
        'department_id' => $department->id,
        'first_name' => 'Locked',
        'last_name' => 'Staff',
        'email' => 'locked.staff@example.test',
        'employee_number' => 'ADM-001',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $staffUser->forceFill(['staff_id' => $staff->id])->save();

    $this->actingAs($admin)
        ->post(route('staff.reset-password', $staff), [
            'password' => 'FreshPass123!',
            'password_confirmation' => 'FreshPass123!',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Staff member password reset successfully.');

    expect(Hash::check('FreshPass123!', $staffUser->fresh()->password))->toBeTrue();
});
