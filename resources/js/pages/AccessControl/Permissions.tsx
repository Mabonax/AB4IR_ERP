import { Head, router, useForm } from "@inertiajs/react";
import { useEffect, useState } from "react";

import Heading from "@/components/heading";
import { DomainNav } from "@/components/domain-nav";
import { accessControlNavItems } from "@/config/domain-nav/access-control";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type PermissionRow = {
  id: number;
  name: string;
  guard_name: string;
  is_protected: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Access Control", href: "/access-control/permissions" },
  { title: "Permissions", href: "/access-control/permissions" },
];

export default function AccessControlPermissions({
  permissions,
}: {
  permissions: PermissionRow[];
}) {
  const createPermissionForm = useForm({
    name: "",
  });

  const [editingPermission, setEditingPermission] = useState<PermissionRow | null>(null);
  const [permissionActionError, setPermissionActionError] = useState<string | null>(null);
  const updatePermissionForm = useForm({
    name: "",
  });

  useEffect(() => {
    if (!editingPermission) return;
    setPermissionActionError(null);
    updatePermissionForm.setData({
      name: editingPermission.name,
    });
  }, [editingPermission]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Access Control - Permissions" />

      <div className="space-y-8 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Access Control - Permissions</h1>
            <p className="text-sm text-muted-foreground">Create, edit, and remove permissions</p>
          </div>
          <DomainNav items={accessControlNavItems} />
        </div>

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
          {permissionActionError && <p className="mt-2 text-sm text-red-600">{permissionActionError}</p>}
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
                          disabled={permission.is_protected}
                          title={permission.is_protected ? "Core permissions cannot be deleted." : undefined}
                          onClick={() => {
                            setPermissionActionError(null);
                            router.delete(`/access-control/permissions/${permission.id}`, {
                              preserveScroll: true,
                              onError: (errors) => {
                                setPermissionActionError(errors.permission ?? "Unable to delete permission.");
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

        {editingPermission && (
          <div className="rounded-xl border bg-card p-6 shadow-sm">
            <Heading variant="small" title={`Edit Permission: ${editingPermission.name}`} />
            <form
              className="mt-4 flex gap-2"
              onSubmit={(e) => {
                e.preventDefault();
                updatePermissionForm.put(`/access-control/permissions/${editingPermission.id}`, {
                  preserveScroll: true,
                  onSuccess: () => setEditingPermission(null),
                  onError: (errors) => {
                    setPermissionActionError(errors.permission ?? errors.name ?? "Unable to update permission.");
                  },
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
      </div>
    </AppLayout>
  );
}
