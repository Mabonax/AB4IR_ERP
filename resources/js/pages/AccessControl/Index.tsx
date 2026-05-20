import { Head, router, useForm } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";

import AppLayout from "@/layouts/app-layout";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { type BreadcrumbItem } from "@/types";

type RoleRow = {
  id: number;
  name: string;
  guard_name: string;
  permissions: string[];
  is_protected: boolean;
};

type PermissionRow = {
  id: number;
  name: string;
  guard_name: string;
  is_protected: boolean;
};

type UserRow = {
  id: number;
  name: string;
  email: string;
  department: string | null;
  roles: string[];
  permissions: string[];
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Access Control", href: "/access-control" },
];

const toggleSelection = (items: string[], value: string) =>
  items.includes(value) ? items.filter((item) => item !== value) : [...items, value];

export default function AccessControlIndex({
  roles,
  permissions,
  users,
}: {
  roles: RoleRow[];
  permissions: PermissionRow[];
  users: UserRow[];
}) {
  const permissionNames = useMemo(() => permissions.map((permission) => permission.name), [permissions]);

  const createRoleForm = useForm({
    name: "",
    permissions: [] as string[],
  });

  const [editingRole, setEditingRole] = useState<RoleRow | null>(null);
  const [roleActionError, setRoleActionError] = useState<string | null>(null);
  const roleEditorRef = useRef<HTMLDivElement | null>(null);
  const updateRoleForm = useForm({
    name: "",
    permissions: [] as string[],
  });

  const createPermissionForm = useForm({
    name: "",
  });

  const [editingPermission, setEditingPermission] = useState<PermissionRow | null>(null);
  const updatePermissionForm = useForm({
    name: "",
  });

  const [selectedUserId, setSelectedUserId] = useState<number | "">("");
  const selectedUser = users.find((user) => user.id === selectedUserId) ?? null;

  const userRolesForm = useForm({
    roles: [] as string[],
  });

  const userPermissionsForm = useForm({
    permissions: [] as string[],
  });

  useEffect(() => {
    if (!editingRole) return;
    setRoleActionError(null);
    updateRoleForm.setData({
      name: editingRole.name,
      permissions: [...editingRole.permissions],
    });

    requestAnimationFrame(() => {
      roleEditorRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }, [editingRole]);

  useEffect(() => {
    if (!editingPermission) return;
    updatePermissionForm.setData({
      name: editingPermission.name,
    });
  }, [editingPermission]);

  useEffect(() => {
    if (!selectedUser) {
      userRolesForm.setData("roles", []);
      userPermissionsForm.setData("permissions", []);
      return;
    }

    userRolesForm.setData("roles", [...selectedUser.roles]);
    userPermissionsForm.setData("permissions", [...selectedUser.permissions]);
  }, [selectedUserId]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Access Control" />

      <div className="space-y-8 p-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Access Control</h1>
            <p className="text-sm text-muted-foreground">
              Roles, permissions, and user assignment management
            </p>
          </div>
        </div>

        <div className="rounded-xl border bg-card p-6 shadow-sm">
          <Heading variant="small" title="Create Role" description="Define a role and attach permissions" />
          <form
            className="mt-4 space-y-3"
            onSubmit={(e) => {
              e.preventDefault();
              createRoleForm.post("/access-control/roles", {
                preserveScroll: true,
                onSuccess: () => createRoleForm.reset(),
              });
            }}
          >
            <Input
              value={createRoleForm.data.name}
              onChange={(e) => createRoleForm.setData("name", e.target.value)}
              placeholder="Role name"
              required
            />
            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
              {permissionNames.map((permissionName) => (
                <label key={permissionName} className="inline-flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={createRoleForm.data.permissions.includes(permissionName)}
                    onChange={() =>
                      createRoleForm.setData(
                        "permissions",
                        toggleSelection(createRoleForm.data.permissions, permissionName)
                      )
                    }
                  />
                  {permissionName}
                </label>
              ))}
            </div>
            {createRoleForm.errors.name && (
              <p className="text-sm text-red-600">{createRoleForm.errors.name}</p>
            )}
            <Button type="submit" disabled={createRoleForm.processing}>
              Create Role
            </Button>
          </form>
        </div>

        <div className="rounded-xl border bg-card p-6 shadow-sm">
          <Heading variant="small" title="Roles" description="Update or remove existing roles" />
          {roleActionError && <p className="mt-2 text-sm text-red-600">{roleActionError}</p>}
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="px-2 py-2 text-left">Role</th>
                  <th className="px-2 py-2 text-left">Permissions</th>
                  <th className="px-2 py-2 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id} className="border-b align-top">
                    <td className="px-2 py-2 font-medium">{role.name}</td>
                    <td className="px-2 py-2">{role.permissions.join(", ") || "-"}</td>
                    <td className="px-2 py-2">
                      <div className="flex gap-2">
                        <Button type="button" variant="outline" onClick={() => setEditingRole(role)}>
                          Edit
                        </Button>
                        <Button
                          type="button"
                          variant="destructive"
                          disabled={role.is_protected}
                          title={role.is_protected ? "Core roles cannot be deleted." : undefined}
                          onClick={() => {
                            setRoleActionError(null);
                            router.delete(`/access-control/roles/${role.id}`, {
                              preserveScroll: true,
                              onError: (errors) => {
                                setRoleActionError(errors.role ?? "Unable to delete role.");
                              },
                            });
                          }}
                        >
                          Delete
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {editingRole && (
          <div ref={roleEditorRef} className="rounded-xl border bg-card p-6 shadow-sm">
            <Heading variant="small" title={`Edit Role: ${editingRole.name}`} />
            <form
              className="mt-4 space-y-3"
              onSubmit={(e) => {
                e.preventDefault();
                updateRoleForm.patch(`/access-control/roles/${editingRole.id}`, {
                  preserveScroll: true,
                  onSuccess: () => setEditingRole(null),
                  onError: (errors) => {
                    setRoleActionError(errors.role ?? errors.name ?? "Unable to update role.");
                  },
                });
              }}
            >
              <Input
                value={updateRoleForm.data.name}
                onChange={(e) => updateRoleForm.setData("name", e.target.value)}
                required
              />
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {permissionNames.map((permissionName) => (
                  <label key={permissionName} className="inline-flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={updateRoleForm.data.permissions.includes(permissionName)}
                      onChange={() =>
                        updateRoleForm.setData(
                          "permissions",
                          toggleSelection(updateRoleForm.data.permissions, permissionName)
                        )
                      }
                    />
                    {permissionName}
                  </label>
                ))}
              </div>
              {((updateRoleForm.errors as Record<string, string | undefined>).role || updateRoleForm.errors.name) && (
                <p className="text-sm text-red-600">
                  {(updateRoleForm.errors as Record<string, string | undefined>).role ?? updateRoleForm.errors.name}
                </p>
              )}
              <div className="flex gap-2">
                <Button type="submit" disabled={updateRoleForm.processing}>
                  Save Role
                </Button>
                <Button type="button" variant="outline" onClick={() => setEditingRole(null)}>
                  Cancel
                </Button>
              </div>
            </form>
          </div>
        )}

        <div className="rounded-xl border bg-card p-6 shadow-sm">
          <Heading variant="small" title="Create Permission" description="Add a granular permission string" />
          <form
            className="mt-4 flex gap-2"
            onSubmit={(e) => {
              e.preventDefault();
              createPermissionForm.post("/access-control/permissions", {
                preserveScroll: true,
                onSuccess: () => createPermissionForm.reset(),
              });
            }}
          >
            <Input
              value={createPermissionForm.data.name}
              onChange={(e) => createPermissionForm.setData("name", e.target.value)}
              placeholder="permission.name"
              required
            />
            <Button type="submit" disabled={createPermissionForm.processing}>
              Create Permission
            </Button>
          </form>
          {createPermissionForm.errors.name && (
            <p className="mt-2 text-sm text-red-600">{createPermissionForm.errors.name}</p>
          )}
        </div>

        <div className="rounded-xl border bg-card p-6 shadow-sm">
          <Heading variant="small" title="Permissions" description="Update or delete permissions" />
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="px-2 py-2 text-left">Permission</th>
                  <th className="px-2 py-2 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                {permissions.map((permission) => (
                  <tr key={permission.id} className="border-b">
                    <td className="px-2 py-2 font-medium">{permission.name}</td>
                    <td className="px-2 py-2">
                      <div className="flex gap-2">
                        <Button type="button" variant="outline" onClick={() => setEditingPermission(permission)}>
                          Edit
                        </Button>
                        <Button
                          type="button"
                          variant="destructive"
                          onClick={() => {
                            router.delete(`/access-control/permissions/${permission.id}`, { preserveScroll: true });
                          }}
                        >
                          Delete
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {editingPermission && (
          <div className="rounded-xl border bg-card p-6 shadow-sm">
            <Heading variant="small" title={`Edit Permission: ${editingPermission.name}`} />
            <form
              className="mt-4 flex gap-2"
              onSubmit={(e) => {
                e.preventDefault();
                updatePermissionForm.patch(`/access-control/permissions/${editingPermission.id}`, {
                  preserveScroll: true,
                  onSuccess: () => setEditingPermission(null),
                });
              }}
            >
              <Input
                value={updatePermissionForm.data.name}
                onChange={(e) => updatePermissionForm.setData("name", e.target.value)}
                required
              />
              <Button type="submit" disabled={updatePermissionForm.processing}>
                Save Permission
              </Button>
              <Button type="button" variant="outline" onClick={() => setEditingPermission(null)}>
                Cancel
              </Button>
            </form>
          </div>
        )}

        <div className="rounded-xl border bg-card p-6 shadow-sm">
          <Heading
            variant="small"
            title="User Role & Permission Assignment"
            description="Assign roles and direct permissions to a selected user"
          />
          <div className="mt-4 space-y-4">
            <select
              value={selectedUserId}
              onChange={(e) => setSelectedUserId(e.target.value ? Number(e.target.value) : "")}
              className="w-full rounded-md border px-3 py-2 text-sm"
            >
              <option value="">Select user</option>
              {users.map((user) => (
                <option key={user.id} value={user.id}>
                  {user.name} ({user.email}){user.department ? ` - ${user.department}` : ""}
                </option>
              ))}
            </select>

            {selectedUser && (
              <div className="grid gap-6 lg:grid-cols-2">
                <form
                  className="space-y-3 rounded-lg border p-4"
                  onSubmit={(e) => {
                    e.preventDefault();
                    userRolesForm.post(`/access-control/users/${selectedUser.id}/roles`, {
                      preserveScroll: true,
                    });
                  }}
                >
                  <h3 className="font-semibold">Roles</h3>
                  <div className="grid gap-2">
                    {roles.map((role) => (
                      <label key={role.id} className="inline-flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={userRolesForm.data.roles.includes(role.name)}
                          onChange={() =>
                            userRolesForm.setData("roles", toggleSelection(userRolesForm.data.roles, role.name))
                          }
                        />
                        {role.name}
                      </label>
                    ))}
                  </div>
                  <Button type="submit" disabled={userRolesForm.processing}>
                    Save User Roles
                  </Button>
                </form>

                <form
                  className="space-y-3 rounded-lg border p-4"
                  onSubmit={(e) => {
                    e.preventDefault();
                    userPermissionsForm.post(`/access-control/users/${selectedUser.id}/permissions`, {
                      preserveScroll: true,
                    });
                  }}
                >
                  <h3 className="font-semibold">Direct Permissions</h3>
                  <div className="max-h-80 overflow-y-auto">
                    <div className="grid gap-2">
                      {permissionNames.map((permissionName) => (
                        <label key={permissionName} className="inline-flex items-center gap-2 text-sm">
                          <input
                            type="checkbox"
                            checked={userPermissionsForm.data.permissions.includes(permissionName)}
                            onChange={() =>
                              userPermissionsForm.setData(
                                "permissions",
                                toggleSelection(userPermissionsForm.data.permissions, permissionName)
                              )
                            }
                          />
                          {permissionName}
                        </label>
                      ))}
                    </div>
                  </div>
                  <Button type="submit" disabled={userPermissionsForm.processing}>
                    Save Direct Permissions
                  </Button>
                </form>
              </div>
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
