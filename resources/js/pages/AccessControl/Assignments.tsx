import { Head, useForm } from "@inertiajs/react";
import { useEffect, useMemo, useState } from "react";

import Heading from "@/components/heading";
import { DomainNav } from "@/components/domain-nav";
import { accessControlNavItems } from "@/config/domain-nav/access-control";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
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
  { title: "Access Control", href: "/access-control/assignments" },
  { title: "Assignments", href: "/access-control/assignments" },
];

const toggleSelection = (items: string[], value: string) =>
  items.includes(value) ? items.filter((item) => item !== value) : [...items, value];

export default function AccessControlAssignments({
  roles,
  permissions,
  users,
}: {
  roles: RoleRow[];
  permissions: PermissionRow[];
  users: UserRow[];
}) {
  const permissionNames = useMemo(() => permissions.map((permission) => permission.name), [permissions]);

  const [selectedUserId, setSelectedUserId] = useState<number | "">("");
  const selectedUser = users.find((user) => user.id === selectedUserId) ?? null;

  const userRolesForm = useForm({
    roles: [] as string[],
  });

  const userPermissionsForm = useForm({
    permissions: [] as string[],
  });

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
      <Head title="Access Control - Assignments" />

      <div className="space-y-8 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Access Control - Assignments</h1>
            <p className="text-sm text-muted-foreground">Assign roles and direct permissions to users</p>
          </div>
          <DomainNav items={accessControlNavItems} />
        </div>

        <div className="rounded-xl border bg-white p-6 shadow-sm">
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
                    userRolesForm.put(`/access-control/users/${selectedUser.id}/roles`, {
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
                    userPermissionsForm.put(`/access-control/users/${selectedUser.id}/permissions`, {
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
