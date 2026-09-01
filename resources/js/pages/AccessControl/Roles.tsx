import { Head, router, useForm } from "@inertiajs/react";
import { CheckCircle2, LockKeyhole, Plus, Search, ShieldCheck, Trash2, UsersRound } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";

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

const groupPermission = (permission: string) => permission.split(".")[0]?.replaceAll("-", " ") || "General";

const chipClass = "inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700";

export default function AccessControlRoles({
  roles,
  permissions,
}: {
  roles: RoleRow[];
  permissions: PermissionRow[];
}) {
  const permissionNames = useMemo(() => permissions.map((permission) => permission.name), [permissions]);
  const permissionGroups = useMemo(() => {
    return permissions.reduce<Record<string, PermissionRow[]>>((groups, permission) => {
      const group = groupPermission(permission.name);
      groups[group] = [...(groups[group] ?? []), permission];

      return groups;
    }, {});
  }, [permissions]);
  const mostAssignedRoles = useMemo(() => roles.slice(0, 5), [roles]);

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

      <div className="space-y-6 bg-white p-4 text-slate-950 md:p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p className="text-sm text-slate-500">Access Control / Roles</p>
            <h1 className="mt-1 text-3xl font-semibold tracking-normal">Access Control - Roles</h1>
            <p className="mt-1 text-sm text-slate-500">Create, edit, and remove roles with precise permission coverage.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <button className="inline-flex h-10 w-10 items-center justify-center rounded-lg border text-slate-600 hover:bg-slate-50" type="button">
              <Search className="h-4 w-4" />
            </button>
            <DomainNav items={accessControlNavItems} />
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {[
            { label: "Roles", value: roles.length, icon: ShieldCheck, tone: "bg-red-50 text-red-600" },
            { label: "Permissions", value: permissions.length, icon: LockKeyhole, tone: "bg-orange-50 text-orange-600" },
            { label: "Protected Roles", value: roles.filter((role) => role.is_protected).length, icon: CheckCircle2, tone: "bg-emerald-50 text-emerald-600" },
            { label: "Assignable Roles", value: roles.filter((role) => !role.is_protected).length, icon: UsersRound, tone: "bg-blue-50 text-blue-600" },
          ].map((item) => (
            <section key={item.label} className="rounded-lg border bg-white p-5 shadow-sm">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="text-sm font-medium text-slate-500">{item.label}</p>
                  <p className="mt-2 text-3xl font-semibold">{item.value}</p>
                </div>
                <span className={`inline-flex h-11 w-11 items-center justify-center rounded-full ${item.tone}`}>
                  <item.icon className="h-5 w-5" />
                </span>
              </div>
            </section>
          ))}
        </div>

        <div className="grid gap-5 xl:grid-cols-[1fr_1.1fr]">
          <section className="rounded-lg border bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold">Create Role</h2>
              <p className="text-sm text-slate-500">Define a role and attach permissions.</p>
            </div>
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-red-50 text-red-600">
              <Plus className="h-5 w-5" />
            </span>
          </div>
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
            <div className="max-h-[480px] space-y-3 overflow-auto pr-1">
              {Object.entries(permissionGroups).map(([group, groupPermissions]) => (
                <div key={group} className="rounded-lg border p-3">
                  <div className="mb-2 text-sm font-semibold capitalize">{group}</div>
                  <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    {groupPermissions.map((permission) => (
                      <label key={permission.name} className="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input
                          type="checkbox"
                          checked={createRoleForm.data.permissions.includes(permission.name)}
                          onChange={() =>
                            createRoleForm.setData(
                              "permissions",
                              toggleSelection(createRoleForm.data.permissions, permission.name)
                            )
                          }
                        />
                        {permission.name}
                      </label>
                    ))}
                  </div>
                </div>
              ))}
            </div>
            {createRoleForm.errors.name && <p className="text-sm text-red-600">{createRoleForm.errors.name}</p>}
            <Button type="submit" disabled={createRoleForm.processing} className="bg-red-600 hover:bg-red-700">
              Create Role
            </Button>
          </form>
          </section>

          <section className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Roles Overview</h2>
            <p className="text-sm text-slate-500">Permission coverage and assignment readiness.</p>
            <div className="mt-4 space-y-3">
              {mostAssignedRoles.map((role) => (
                <div key={role.id} className="rounded-lg border p-4">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="font-semibold">{role.name}</div>
                      <div className="mt-1 text-xs text-slate-500">{role.permissions.length} permissions attached</div>
                    </div>
                    <span className={role.is_protected ? "rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600" : "rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600"}>
                      {role.is_protected ? "Protected" : "Editable"}
                    </span>
                  </div>
                  <div className="mt-3 flex flex-wrap gap-2">
                    {role.permissions.slice(0, 5).map((permission) => (
                      <span key={permission} className={chipClass}>{permission}</span>
                    ))}
                    {role.permissions.length > 5 ? <span className={chipClass}>+{role.permissions.length - 5}</span> : null}
                  </div>
                </div>
              ))}
            </div>
          </section>
        </div>

        <div className="rounded-lg border bg-white p-5 shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold">Roles</h2>
              <p className="text-sm text-slate-500">Update or remove existing roles.</p>
            </div>
          </div>
          {roleActionError && <p className="mt-2 text-sm text-red-600">{roleActionError}</p>}
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b bg-slate-50 text-xs uppercase text-slate-500">
                  <th className="px-3 py-3 text-left">Role</th>
                  <th className="px-3 py-3 text-left">Permissions</th>
                  <th className="px-3 py-3 text-left">Guard</th>
                  <th className="px-3 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id} className="border-b align-top">
                    <td className="px-3 py-3 font-medium">{role.name}</td>
                    <td className="px-3 py-3">
                      <div className="flex max-w-3xl flex-wrap gap-2">
                        {role.permissions.length ? role.permissions.slice(0, 8).map((permission) => (
                          <span key={permission} className={chipClass}>{permission}</span>
                        )) : <span className="text-slate-400">No permissions</span>}
                        {role.permissions.length > 8 ? <span className={chipClass}>+{role.permissions.length - 8}</span> : null}
                      </div>
                    </td>
                    <td className="px-3 py-3">{role.guard_name}</td>
                    <td className="px-3 py-3">
                      <div className="flex justify-end gap-2">
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
                          <Trash2 className="mr-2 h-4 w-4" />
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
          <div ref={roleEditorRef} className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Edit Role: {editingRole.name}</h2>
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
