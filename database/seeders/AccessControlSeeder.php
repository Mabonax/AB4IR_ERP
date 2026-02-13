<?php

namespace Database\Seeders;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('access_control.guard', 'web');
        $domains = config('access_control.domains', []);

        $allDomainPermissions = [];
        $viewPermissions = [];
        $managePermissions = [];

        foreach ($domains as $domain) {
            $view = "domain.{$domain}.view";
            $manage = "domain.{$domain}.manage";

            $viewPermissions[] = $view;
            $managePermissions[] = $manage;
            $allDomainPermissions[] = $view;
            $allDomainPermissions[] = $manage;
        }

        $accessControlPermissions = [
            'access-control.view',
            'roles.create',
            'roles.view',
            'roles.update',
            'roles.delete',
            'permissions.create',
            'permissions.view',
            'permissions.update',
            'permissions.delete',
            'assignments.manage',
        ];

        $projectActivityPermissions = [
            'project-activities.view',
            'project-activities.manage',
        ];

        $attendancePermissions = [
            'attendance.view',
            'attendance.manage',
        ];

        $allPermissions = array_values(array_unique([
            ...$allDomainPermissions,
            ...$accessControlPermissions,
            ...$projectActivityPermissions,
            ...$attendancePermissions,
        ]));

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => $guard,
        ]);
        $superAdmin->syncPermissions($allPermissions);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guard,
        ]);
        $admin->syncPermissions($allPermissions);

        $viewerCrossDomain = Role::firstOrCreate([
            'name' => 'viewer-cross-domain',
            'guard_name' => $guard,
        ]);
        $viewerCrossDomain->syncPermissions(array_values(array_unique([
            ...$viewPermissions,
            'domain.settings.view',
            'domain.leave.view',
        ])));

        $facilitatorRole = Role::firstOrCreate([
            'name' => 'facilitator',
            'guard_name' => $guard,
        ]);
        $facilitatorRole->syncPermissions(array_values(array_unique([
            ...$projectActivityPermissions,
            ...$attendancePermissions,
        ])));

        $departmentMap = config('access_control.department_domain_map', []);

        $departments = StaffDepartment::query()->orderBy('name')->get();
        foreach ($departments as $department) {
            $departmentName = strtolower((string) $department->name);
            $departmentSlug = Str::slug($department->name);
            $mappedDomains = $departmentMap[$departmentName] ?? $departmentMap['default'] ?? [];

            $domainAdminPermissions = [];
            $departmentManagerPermissions = [];
            $departmentUserPermissions = [];

            foreach ($mappedDomains as $domain) {
                $domainAdminPermissions[] = "domain.{$domain}.view";
                $domainAdminPermissions[] = "domain.{$domain}.manage";
                $departmentManagerPermissions[] = "domain.{$domain}.view";
                $departmentManagerPermissions[] = "domain.{$domain}.manage";
                $departmentUserPermissions[] = "domain.{$domain}.view";
            }

            $departmentManagerPermissions[] = 'domain.leave.view';
            $departmentManagerPermissions[] = 'domain.leave.manage';
            $departmentManagerPermissions[] = 'domain.settings.view';
            $departmentManagerPermissions[] = 'domain.staff.view';

            $departmentUserPermissions[] = 'domain.leave.view';
            $departmentUserPermissions[] = 'domain.leave.manage';
            $departmentUserPermissions[] = 'domain.settings.view';

            $domainAdminRole = Role::firstOrCreate([
                'name' => "domain-admin-{$departmentSlug}",
                'guard_name' => $guard,
            ]);
            $domainAdminRole->syncPermissions(array_values(array_unique($domainAdminPermissions)));

            $departmentManagerRole = Role::firstOrCreate([
                'name' => "department-manager-{$departmentSlug}",
                'guard_name' => $guard,
            ]);
            $departmentManagerRole->syncPermissions(array_values(array_unique($departmentManagerPermissions)));

            $departmentUserRole = Role::firstOrCreate([
                'name' => "department-user-{$departmentSlug}",
                'guard_name' => $guard,
            ]);
            $departmentUserRole->syncPermissions(array_values(array_unique($departmentUserPermissions)));
        }

        $facilitatorUserIds = Facilitator::query()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! empty($facilitatorUserIds)) {
            User::query()
                ->whereIn('id', $facilitatorUserIds)
                ->get()
                ->each(function (User $user): void {
                    if (! $user->hasRole('facilitator')) {
                        $user->assignRole('facilitator');
                    }
                });
        }

        $legacyFacilitatorEmails = Facilitator::query()
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();

        $users = User::query()->with(['staffMember.department', 'staffMember.directReports'])->get();
        foreach ($users as $user) {
            if ($user->roles()->exists()) {
                continue;
            }

            $staff = $user->staffMember;
            if (! $staff) {
                $isFacilitatorUser = in_array(
                    strtolower(trim((string) $user->email)),
                    $legacyFacilitatorEmails,
                    true
                );

                if ($isFacilitatorUser) {
                    $user->syncRoles(['facilitator']);
                    continue;
                }

                $user->syncRoles(['viewer-cross-domain']);
                continue;
            }

            if ((bool) $staff->is_ceo) {
                $user->syncRoles(['super-admin']);
                continue;
            }

            $departmentSlug = $staff->department ? Str::slug($staff->department->name) : null;
            $hasReports = $staff->directReports()->exists();

            $rolesToAssign = [];
            if ($staff->department && strtolower($staff->department->name) === 'admin') {
                $rolesToAssign[] = 'admin';
            } elseif ($departmentSlug && $hasReports) {
                $rolesToAssign[] = "department-manager-{$departmentSlug}";
            } elseif ($departmentSlug) {
                $rolesToAssign[] = "department-user-{$departmentSlug}";
            } else {
                $rolesToAssign[] = 'viewer-cross-domain';
            }

            if ((bool) $staff->is_board_member) {
                $rolesToAssign[] = 'viewer-cross-domain';
            }

            $user->syncRoles(array_values(array_unique($rolesToAssign)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
