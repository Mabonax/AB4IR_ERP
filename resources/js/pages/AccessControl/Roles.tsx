import { Head, router, useForm } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";

import Heading from "@/components/heading";
import { DomainNav } from "@/components/domain-nav";
import { accessControlNavItems } from "@/config/domain-nav/access-control";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Access Control", href: "/access-control/roles" },
  { title: "Roles", href: "/access-control/roles" },
];

const toggleSelection = (items: string[], value: string) =>
  items.includes(value) ? items.filter((item) => item !== value) : [...items, value];

export default function AccessControlRoles({
  roles,
  permissions,
}: {
  roles: RoleRow[];
  permissions: PermissionRow[];
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

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Access Control - Roles" />

      <div className="space-y-8 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Access Control - Roles</h1>
            <p className="text-sm text-muted-foreground">Create, edit, and remove roles</p>
          </div>
          <DomainNav items={accessControlNavItems} />
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
            {createRoleForm.errors.name && <p className="text-sm text-red-600">{createRoleForm.errors.name}</p>}
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
                updateRoleForm.put(`/access-control/roles/${editingRole.id}`, {
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
      </div>
    </AppLayout>
  );
}
