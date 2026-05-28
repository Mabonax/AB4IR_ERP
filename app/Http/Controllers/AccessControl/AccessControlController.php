<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlController extends Controller
{
    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,guard_name:string,permissions:\Illuminate\Support\Collection<int,string>,is_protected:bool}>
     */
    protected function roleRows()
    {
        return Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'is_protected' => in_array($role->name, $this->protectedRoleNames(), true),
            ])
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,guard_name:string,is_protected:bool}>
     */
    protected function permissionRows()
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name'])
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'is_protected' => in_array($permission->name, $this->protectedPermissionNames(), true),
            ])
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,email:string,department:?string,roles:\Illuminate\Support\Collection<int,string>,permissions:\Illuminate\Support\Collection<int,string>}>
     */
    protected function userRows()
    {
        return User::query()
            ->with(['roles:id,name', 'permissions:id,name', 'staffMember.department:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->staffMember?->department?->name,
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->permissions->pluck('name')->values(),
            ])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    protected function protectedRoleNames(): array
    {
        return [
            'super-admin',
            'super admin',
            'admin',
            'viewer-cross-domain',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function protectedPermissionNames(): array
    {
        return [
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
    }

    public function index(): Response
    {
        $this->assertSuperUser(request());

        return Inertia::render('AccessControl/Index', [
            'roles' => $this->roleRows(),
            'permissions' => $this->permissionRows(),
            'users' => $this->userRows(),
        ]);
    }

    public function rolesPage(): Response
    {
        $this->assertSuperUser(request());

        return Inertia::render('AccessControl/Roles', [
            'roles' => $this->roleRows(),
            'permissions' => $this->permissionRows(),
        ]);
    }

    public function permissionsPage(): Response
    {
        $this->assertSuperUser(request());

        return Inertia::render('AccessControl/Permissions', [
            'permissions' => $this->permissionRows(),
        ]);
    }

    public function assignmentsPage(): Response
    {
        $this->assertSuperUser(request());

        return Inertia::render('AccessControl/Assignments', [
            'roles' => $this->roleRows(),
            'permissions' => $this->permissionRows(),
            'users' => $this->userRows(),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $this->assertSuperUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $this->assertSuperUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (
            in_array($role->name, $this->protectedRoleNames(), true)
            && $validated['name'] !== $role->name
        ) {
            return redirect()->back()->withErrors([
                'role' => 'Core roles cannot be renamed.',
            ]);
        }

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        $this->assertSuperUser(request());

        if (in_array($role->name, $this->protectedRoleNames(), true)) {
            return redirect()->back()->withErrors([
                'role' => 'Core roles cannot be deleted.',
            ]);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $this->assertSuperUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name'],
        ]);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $this->assertSuperUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name,'.$permission->id],
        ]);

        if (
            in_array($permission->name, $this->protectedPermissionNames(), true)
            && $validated['name'] !== $permission->name
        ) {
            return redirect()->back()->withErrors([
                'permission' => 'Core access-control permissions cannot be renamed.',
            ]);
        }

        $permission->update([
            'name' => $validated['name'],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permission updated successfully.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        $this->assertSuperUser(request());

        if (in_array($permission->name, $this->protectedPermissionNames(), true)) {
            return redirect()->back()->withErrors([
                'permission' => 'Core access-control permissions cannot be deleted.',
            ]);
        }

        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }

    public function syncUserRoles(Request $request, User $user): RedirectResponse
    {
        $this->assertSuperUser($request);

        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($validated['roles'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'User roles updated successfully.');
    }

    public function syncUserPermissions(Request $request, User $user): RedirectResponse
    {
        $this->assertSuperUser($request);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', 'User direct permissions updated successfully.');
    }

    protected function assertSuperUser(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['super-admin', 'super admin']), 403);
    }
}
