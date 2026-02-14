import { Head, useForm } from "@inertiajs/react";
import { useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { assetNavItems } from "@/config/domain-nav/assets";
import { AssetModelFormConfig } from "@/config/forms/asset-model-form";
import { AssetTableConfig } from "@/config/tables/asset-table";
import AppLayout from "@/layouts/app-layout";
import assets from "@/routes/assets";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Assets", href: "/assets" },
  { title: "List", href: "/assets/list" },
];

type AssetRow = {
  id: number;
  asset_code: string | null;
  asset_category_id: number | null;
  name: string;
  type: string;
  model_name: string | null;
  serial_state: string;
  serial_number: string | null;
  status: string;
  current_assignment?: {
    id: number;
    department_id: number | null;
    staff_member_id: number | null;
    project_id: number | null;
    department_name: string | null;
    staff_member_name: string | null;
    project_name: string | null;
  } | null;
};

/* =========================================================
| PAGE
========================================================= */

export default function AssetIndex({
  assets: assetPagination,
  categories,
  departments,
  staffMembers,
  projects,
  batches,
  filters,
}: {
  assets: { data: AssetRow[] };
  categories: { id: number; name: string }[];
  departments: { id: number; name: string }[];
  staffMembers: { id: number; name: string; department_id: number | null }[];
  projects: { id: number; name: string; project_manager_id: number | null }[];
  batches: Array<{ id: number; name: string; category_name: string | null; type: string; model_name: string | null; quantity: number; serial_state: string; notes?: string | null; created_at: string | null }>;
  filters: {
    category_id?: string | number | null;
  };
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedAsset, setSelectedAsset] = useState<AssetRow | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [assetToDelete, setAssetToDelete] = useState<AssetRow | null>(null);
  const [assignOpen, setAssignOpen] = useState(false);
  const [selectedAssignAsset, setSelectedAssignAsset] = useState<AssetRow | null>(null);
  const [editBatchOpen, setEditBatchOpen] = useState(false);
  const [selectedBatchId, setSelectedBatchId] = useState<number | null>(null);
  const [deleteBatchOpen, setDeleteBatchOpen] = useState(false);
  const [batchToDelete, setBatchToDelete] = useState<{ id: number; name: string } | null>(null);
  const [selectedCategoryId, setSelectedCategoryId] = useState<string>(
    filters?.category_id ? String(filters.category_id) : ""
  );

  const batchForm = useForm({
    name: "",
    asset_category_id: "",
    type: "",
    model_name: "",
    quantity: 1,
    serial_state: "pending",
    notes: "",
  });

  const assignForm = useForm({
    assignment_mode: "department_staff",
    department_id: "",
    staff_member_id: "",
    project_id: "",
    notes: "",
  });

  const returnForm = useForm({
    notes: "",
  });

  const editBatchForm = useForm({
    name: "",
    asset_category_id: "",
    type: "",
    model_name: "",
    serial_state: "pending",
    notes: "",
  });

  const mappedAssetData = selectedAsset
    ? {
        name: selectedAsset.name ?? "",
        asset_category_id:
          selectedAsset.asset_category_id !== null &&
          selectedAsset.asset_category_id !== undefined
            ? String(selectedAsset.asset_category_id)
            : "",
        type: selectedAsset.type ?? "",
        model_name: selectedAsset.model_name ?? "",
        serial_state: selectedAsset.serial_state ?? "recorded",
        serial_number: selectedAsset.serial_number ?? "",
        status: selectedAsset.status ?? "unassigned",
      }
    : {};

  const submitBatch = (e: React.FormEvent) => {
    e.preventDefault();

    batchForm.post("/assets/batches", {
      preserveScroll: true,
      onSuccess: () => batchForm.reset(),
    });
  };

  const submitAssign = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedAssignAsset) return;

    assignForm.clearErrors();

    if (
      assignForm.data.assignment_mode === "department_staff" &&
      !assignForm.data.department_id &&
      !assignForm.data.staff_member_id
    ) {
      assignForm.setError(
        "department_id",
        "Select a department or staff member before assigning."
      );
      return;
    }

    if (assignForm.data.assignment_mode === "project" && !assignForm.data.project_id) {
      assignForm.setError("project_id", "Select a project before assigning.");
      return;
    }

    assignForm.post(`/assets/${selectedAssignAsset.id}/assign`, {
      preserveScroll: true,
      onSuccess: () => {
        setAssignOpen(false);
        assignForm.reset();
      },
    });
  };

  const submitReturn = (assetId: number) => {
    returnForm.post(`/assets/${assetId}/return`, {
      preserveScroll: true,
    });
  };

  const applyCategoryFilter = (categoryId: string) => {
    setSelectedCategoryId(categoryId);
    window.location.href = categoryId
      ? `/assets/list?category_id=${encodeURIComponent(categoryId)}`
      : "/assets/list";
  };

  const openEditBatch = (batch: {
    id: number;
    name: string;
    category_name: string | null;
    type: string;
    model_name: string | null;
    serial_state: string;
    notes?: string | null;
  }) => {
    const matchedCategory = categories.find((category) => category.name === batch.category_name);

    setSelectedBatchId(batch.id);
    editBatchForm.setData({
      name: batch.name,
      asset_category_id: matchedCategory ? String(matchedCategory.id) : "",
      type: batch.type,
      model_name: batch.model_name ?? "",
      serial_state: (batch.serial_state as "pending" | "no_serial") ?? "pending",
      notes: batch.notes ?? "",
    });
    setEditBatchOpen(true);
  };

  const submitEditBatch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedBatchId) return;

    editBatchForm.put(`/assets/batches/${selectedBatchId}`, {
      preserveScroll: true,
      onSuccess: () => {
        setEditBatchOpen(false);
        setSelectedBatchId(null);
      },
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Assets" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Assets</h1>
          <DomainNav items={assetNavItems} />
   
       

          <CustomModelForm
            addButton={AssetModelFormConfig.addButton}
            title="Add Asset"
            description={AssetModelFormConfig.description}
            fields={AssetModelFormConfig.fields}
            submitRoute={assets.store}
            options={{ categories }}
          />
        </div>

        <div className="rounded-xl border bg-white p-4 shadow-sm">
          <div className="flex flex-wrap items-center gap-3">
            <label className="text-sm font-medium">Filter by Category</label>
            <select
              className="min-w-[240px] rounded-md border px-3 py-2 text-sm"
              value={selectedCategoryId}
              onChange={(e) => applyCategoryFilter(e.currentTarget.value)}
            >
              <option value="">All categories</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="rounded-xl border bg-white p-4 shadow-sm">
          <h2 className="text-base font-semibold">Create Inventory Batch</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Create multiple asset records at once. Use pending/no serial when serial numbers are not captured yet.
          </p>
          <form className="mt-3 grid gap-3 md:grid-cols-3" onSubmit={submitBatch}>
            <input
              className="rounded-md border px-3 py-2 text-sm"
              placeholder="Batch name"
              value={batchForm.data.name}
              onChange={(e) => batchForm.setData("name", e.currentTarget.value)}
              required
            />
            <select
              className="rounded-md border px-3 py-2 text-sm"
              value={batchForm.data.asset_category_id}
              onChange={(e) => batchForm.setData("asset_category_id", e.currentTarget.value)}
              required
            >
              <option value="">Select category</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
            <input
              className="rounded-md border px-3 py-2 text-sm"
              placeholder="Type"
              value={batchForm.data.type}
              onChange={(e) => batchForm.setData("type", e.currentTarget.value)}
              required
            />
            <input
              className="rounded-md border px-3 py-2 text-sm"
              placeholder="Model"
              value={batchForm.data.model_name}
              onChange={(e) => batchForm.setData("model_name", e.currentTarget.value)}
              required
            />
            <input
              type="number"
              min={1}
              className="rounded-md border px-3 py-2 text-sm"
              placeholder="Quantity"
              value={batchForm.data.quantity}
              onChange={(e) => batchForm.setData("quantity", Number(e.currentTarget.value || 1))}
              required
            />
            <select
              className="rounded-md border px-3 py-2 text-sm"
              value={batchForm.data.serial_state}
              onChange={(e) => batchForm.setData("serial_state", e.currentTarget.value as "pending" | "no_serial")}
              required
            >
              <option value="pending">Pending Serial</option>
              <option value="no_serial">No Serial</option>
            </select>
            <input
              className="rounded-md border px-3 py-2 text-sm"
              placeholder="Notes (optional)"
              value={batchForm.data.notes}
              onChange={(e) => batchForm.setData("notes", e.currentTarget.value)}
            />
            <div className="md:col-span-3">
              <button
                type="submit"
                className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                disabled={batchForm.processing}
              >
                {batchForm.processing ? "Creating..." : "Create Batch"}
              </button>
            </div>
          </form>

          {batches.length > 0 ? (
            <div className="mt-4 overflow-x-auto">
              <table className="min-w-full text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-3 py-2 text-left">Batch</th>
                    <th className="px-3 py-2 text-left">Category</th>
                    <th className="px-3 py-2 text-left">Type</th>
                    <th className="px-3 py-2 text-left">Model</th>
                    <th className="px-3 py-2 text-left">Qty</th>
                    <th className="px-3 py-2 text-left">Serial State</th>
                    <th className="px-3 py-2 text-left">Created</th>
                    <th className="px-3 py-2 text-left">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {batches.map((batch) => (
                    <tr key={batch.id} className="border-t">
                      <td className="px-3 py-2">{batch.name}</td>
                      <td className="px-3 py-2">{batch.category_name ?? "-"}</td>
                      <td className="px-3 py-2">{batch.type}</td>
                      <td className="px-3 py-2">{batch.model_name ?? "-"}</td>
                      <td className="px-3 py-2">{batch.quantity}</td>
                      <td className="px-3 py-2">{batch.serial_state}</td>
                      <td className="px-3 py-2">{batch.created_at ?? "-"}</td>
                      <td className="px-3 py-2">
                        <div className="flex gap-2">
                          <button
                            type="button"
                            onClick={() => openEditBatch(batch)}
                            className="rounded-md border border-orange-500 px-2 py-1 text-xs text-orange-600 hover:bg-orange-500 hover:text-white"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              setBatchToDelete({ id: batch.id, name: batch.name });
                              setDeleteBatchOpen(true);
                            }}
                            className="rounded-md border border-red-600 px-2 py-1 text-xs text-red-600 hover:bg-red-600 hover:text-white"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : null}
        </div>

        <CustomTable
          columns={AssetTableConfig.columns}
          data={assetPagination.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedAsset(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "ArrowRightLeft",
              onClick: (row) => {
                setSelectedAssignAsset(row);
                setAssignOpen(true);
                assignForm.reset();
              },
            },
            {
              icon: "Undo2",
              onClick: (row) => submitReturn(row.id),
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedAsset(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setAssetToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedAsset && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Asset Details" : "Edit Asset"}
            fields={AssetModelFormConfig.fields}
            mode={mode}
            initialData={mappedAssetData}
            submitRoute={assets.update}
            routeParams={selectedAsset.id}
            options={{ categories }}
          />
        )}

        {assetToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Asset"
            submitRoute={assets.destroy}
            routeParams={assetToDelete.id}
          />
        )}

        <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
          <DialogContent className="sm:max-w-[700px]">
            <DialogHeader>
              <DialogTitle>Assign Asset</DialogTitle>
              <DialogDescription>
                {selectedAssignAsset ? `${selectedAssignAsset.asset_code ?? "-"} | ${selectedAssignAsset.name}` : "Select assignment target"}
              </DialogDescription>
            </DialogHeader>
            <form className="grid gap-3" onSubmit={submitAssign}>
              {(assignForm.errors.assignment_mode ||
                assignForm.errors.department_id ||
                assignForm.errors.staff_member_id ||
                assignForm.errors.project_id ||
                assignForm.errors.notes) && (
                <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
                  {assignForm.errors.assignment_mode ||
                    assignForm.errors.department_id ||
                    assignForm.errors.staff_member_id ||
                    assignForm.errors.project_id ||
                    assignForm.errors.notes}
                </div>
              )}

              <div>
                <label className="mb-1 block text-sm font-medium">Assignment Mode</label>
                <select
                  value={assignForm.data.assignment_mode}
                  onChange={(e) => assignForm.setData("assignment_mode", e.currentTarget.value as "department_staff" | "project")}
                  className="w-full rounded-md border px-3 py-2 text-sm"
                >
                  <option value="department_staff">Department/Staff</option>
                  <option value="project">Project (Exclusive)</option>
                </select>
              </div>

              {assignForm.data.assignment_mode === "project" ? (
                <div>
                  <label className="mb-1 block text-sm font-medium">Project</label>
                  <select
                    value={assignForm.data.project_id}
                    onChange={(e) => assignForm.setData("project_id", e.currentTarget.value)}
                    className="w-full rounded-md border px-3 py-2 text-sm"
                    required
                  >
                    <option value="">Select project</option>
                    {projects.map((project) => (
                      <option key={project.id} value={project.id}>
                        {project.name}
                      </option>
                    ))}
                  </select>
                  {assignForm.errors.project_id && (
                    <p className="mt-1 text-sm text-red-600">{assignForm.errors.project_id}</p>
                  )}
                </div>
              ) : (
                <>
                  <div>
                    <label className="mb-1 block text-sm font-medium">Department (Optional)</label>
                    <select
                      value={assignForm.data.department_id}
                      onChange={(e) => assignForm.setData("department_id", e.currentTarget.value)}
                      className="w-full rounded-md border px-3 py-2 text-sm"
                    >
                      <option value="">Select department</option>
                      {departments.map((department) => (
                        <option key={department.id} value={department.id}>
                          {department.name}
                        </option>
                      ))}
                    </select>
                    {assignForm.errors.department_id && (
                      <p className="mt-1 text-sm text-red-600">{assignForm.errors.department_id}</p>
                    )}
                  </div>
                  <div>
                    <label className="mb-1 block text-sm font-medium">Staff (Optional)</label>
                    <select
                      value={assignForm.data.staff_member_id}
                      onChange={(e) => assignForm.setData("staff_member_id", e.currentTarget.value)}
                      className="w-full rounded-md border px-3 py-2 text-sm"
                    >
                      <option value="">Select staff</option>
                      {staffMembers.map((staff) => (
                        <option key={staff.id} value={staff.id}>
                          {staff.name}
                        </option>
                      ))}
                    </select>
                    {assignForm.errors.staff_member_id && (
                      <p className="mt-1 text-sm text-red-600">{assignForm.errors.staff_member_id}</p>
                    )}
                  </div>
                </>
              )}

              <div>
                <label className="mb-1 block text-sm font-medium">Notes</label>
                <textarea
                  rows={3}
                  className="w-full rounded-md border px-3 py-2 text-sm"
                  value={assignForm.data.notes}
                  onChange={(e) => assignForm.setData("notes", e.currentTarget.value)}
                />
                {assignForm.errors.notes && (
                  <p className="mt-1 text-sm text-red-600">{assignForm.errors.notes}</p>
                )}
              </div>

              <div>
                <button
                  type="submit"
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                  disabled={assignForm.processing}
                >
                  {assignForm.processing ? "Assigning..." : "Assign Asset"}
                </button>
              </div>
            </form>
          </DialogContent>
        </Dialog>

        <Dialog open={editBatchOpen} onOpenChange={setEditBatchOpen}>
          <DialogContent className="sm:max-w-[700px]">
            <DialogHeader>
              <DialogTitle>Edit Asset Batch</DialogTitle>
              <DialogDescription>
                Update batch details. Quantity is fixed to preserve generated item records.
              </DialogDescription>
            </DialogHeader>
            <form className="grid gap-3 md:grid-cols-2" onSubmit={submitEditBatch}>
              <input
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Batch name"
                value={editBatchForm.data.name}
                onChange={(e) => editBatchForm.setData("name", e.currentTarget.value)}
                required
              />
              <select
                className="rounded-md border px-3 py-2 text-sm"
                value={editBatchForm.data.asset_category_id}
                onChange={(e) => editBatchForm.setData("asset_category_id", e.currentTarget.value)}
                required
              >
                <option value="">Select category</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
              <input
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Type"
                value={editBatchForm.data.type}
                onChange={(e) => editBatchForm.setData("type", e.currentTarget.value)}
                required
              />
              <input
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Model"
                value={editBatchForm.data.model_name}
                onChange={(e) => editBatchForm.setData("model_name", e.currentTarget.value)}
                required
              />
              <select
                className="rounded-md border px-3 py-2 text-sm"
                value={editBatchForm.data.serial_state}
                onChange={(e) =>
                  editBatchForm.setData("serial_state", e.currentTarget.value as "pending" | "no_serial")
                }
                required
              >
                <option value="pending">Pending Serial</option>
                <option value="no_serial">No Serial</option>
              </select>
              <input
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Notes"
                value={editBatchForm.data.notes}
                onChange={(e) => editBatchForm.setData("notes", e.currentTarget.value)}
              />
              <div className="md:col-span-2">
                <button
                  type="submit"
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                  disabled={editBatchForm.processing}
                >
                  {editBatchForm.processing ? "Saving..." : "Save Batch"}
                </button>
              </div>
            </form>
          </DialogContent>
        </Dialog>

        {batchToDelete && (
          <ConfirmDeleteModal
            open={deleteBatchOpen}
            onOpenChange={setDeleteBatchOpen}
            title={`Delete Batch: ${batchToDelete.name}`}
            submitRoute={(id) => ({ url: `/assets/batches/${id}`, method: "delete" })}
            routeParams={batchToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
