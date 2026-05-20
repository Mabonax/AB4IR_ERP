<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('deleting a staff member redirects back to the staff list', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'staff');

    $department = StaffDepartment::query()->create([
        'name' => 'Operations',
        'description' => 'Operations team',
    ]);

    $staff = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Delete',
        'last_name' => 'Me',
        'email' => 'delete.me@example.test',
        'employee_number' => 'OPS-404',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->delete(route('staff.destroy', $staff))
        ->assertRedirect(route('staff.list'))
        ->assertSessionHas('success', 'Staff member deleted');

    $this->assertDatabaseMissing('staff_members', [
        'id' => $staff->id,
    ]);
});
