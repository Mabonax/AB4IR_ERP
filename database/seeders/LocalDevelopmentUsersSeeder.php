<?php

namespace Database\Seeders;

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class LocalDevelopmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'development'])) {
            return;
        }

        $defaultPassword = 'password';
        $guard = config('access_control.guard', 'web');

        app(StaffDepartmentsSeeder::class)->run();
        app(AccessControlSeeder::class)->run();

        $departments = StaffDepartment::query()
            ->get()
            ->keyBy(fn (StaffDepartment $department) => strtolower($department->name));

        $ceo = $this->upsertStaffUser(
            department: $departments->get('admin'),
            email: 'executive@poa.org.za',
            firstName: 'Chief',
            lastName: 'Executive',
            employeeNumber: 'DEV-CEO-001',
            password: $defaultPassword,
            staffOverrides: [
                'is_ceo' => true,
                'is_manager' => true,
            ],
            roleNames: ['super-admin'],
            guard: $guard,
        );

        $technicalManager = $this->upsertStaffUser(
            department: $departments->get('technical'),
            email: 'admin@poa.org.za',
            firstName: 'Platform',
            lastName: 'Manager',
            employeeNumber: 'DEV-TEC-001',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $ceo->id,
                'is_manager' => true,
            ],
            roleNames: ['super-admin', 'department-manager-technical'],
            guard: $guard,
        );

        $this->upsertStaffUser(
            department: $departments->get('technical'),
            email: 'compliance@poa.org.za',
            firstName: 'Compliance',
            lastName: 'Officer',
            employeeNumber: 'DEV-TEC-002',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $technicalManager->id,
            ],
            roleNames: ['department-user-technical'],
            guard: $guard,
        );

        $marketingManager = $this->upsertStaffUser(
            department: $departments->get('marketing'),
            email: 'funding@poa.org.za',
            firstName: 'Funding',
            lastName: 'Manager',
            employeeNumber: 'DEV-MKT-001',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $ceo->id,
                'is_manager' => true,
            ],
            roleNames: ['department-manager-marketing'],
            guard: $guard,
        );

        $this->upsertStaffUser(
            department: $departments->get('marketing'),
            email: 'volunteer@poa.org.za',
            firstName: 'Volunteer',
            lastName: 'Coordinator',
            employeeNumber: 'DEV-MKT-002',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $marketingManager->id,
            ],
            roleNames: ['department-user-marketing'],
            guard: $guard,
        );

        $adminManager = $this->upsertStaffUser(
            department: $departments->get('admin'),
            email: 'monitoring@poa.org.za',
            firstName: 'Monitoring',
            lastName: 'Manager',
            employeeNumber: 'DEV-ADM-001',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $ceo->id,
                'is_manager' => true,
            ],
            roleNames: ['department-manager-admin'],
            guard: $guard,
        );

        $this->upsertStaffUser(
            department: $departments->get('admin'),
            email: 'ops-admin@poa.org.za',
            firstName: 'Operations',
            lastName: 'Admin',
            employeeNumber: 'DEV-ADM-002',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $adminManager->id,
            ],
            roleNames: ['department-user-admin'],
            guard: $guard,
        );

        $businessDevelopmentManager = $this->upsertStaffUser(
            department: $departments->get('business development'),
            email: 'programme.manager@poa.org.za',
            firstName: 'Programme',
            lastName: 'Manager',
            employeeNumber: 'DEV-BDS-001',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $ceo->id,
                'is_manager' => true,
            ],
            roleNames: ['department-manager-business-development'],
            guard: $guard,
        );

        $this->upsertStaffUser(
            department: $departments->get('business development'),
            email: 'programme.officer@poa.org.za',
            firstName: 'Programme',
            lastName: 'Officer',
            employeeNumber: 'DEV-BDS-002',
            password: $defaultPassword,
            staffOverrides: [
                'manager_id' => $businessDevelopmentManager->id,
            ],
            roleNames: ['department-user-business-development'],
            guard: $guard,
        );
    }

    protected function upsertStaffUser(
        ?StaffDepartment $department,
        string $email,
        string $firstName,
        string $lastName,
        string $employeeNumber,
        string $password,
        array $staffOverrides,
        string|array $roleNames,
        string $guard,
    ): StaffMember {
        if ($department === null) {
            throw new RuntimeException('Required department is missing for local development seeding.');
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = trim($firstName.' '.$lastName);
        $user->password = Hash::make($password);
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        $staff = StaffMember::query()->updateOrCreate(
            ['email' => $email],
            array_merge([
                'user_id' => $user->id,
                'department_id' => $department->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => '0710000000',
                'employee_number' => $employeeNumber,
                'start_date' => '2026-01-01',
                'status' => 'active',
                'is_ceo' => false,
                'is_board_member' => false,
                'is_manager' => false,
            ], $staffOverrides)
        );

        if ((int) ($user->staff_id ?? 0) !== (int) $staff->id) {
            $user->forceFill(['staff_id' => $staff->id])->save();
        }

        if ((int) ($staff->user_id ?? 0) !== (int) $user->id) {
            $staff->forceFill(['user_id' => $user->id])->save();
        }

        $roleNames = array_values(array_unique((array) $roleNames));

        foreach ($roleNames as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);
        }

        $user->syncRoles($roleNames);

        return $staff->refresh();
    }
}
