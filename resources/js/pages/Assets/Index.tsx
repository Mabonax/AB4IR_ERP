import { Head, useForm, usePage } from "@inertiajs/react";
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
import { type BreadcrumbItem, type SharedData } from "@/types";

import assets from "@/routes/assets";

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
  assigned_to?: string | null;
};

type BatchRow = {
  id: number;
  name: string;
  category_name: string | null;
  type: string;
  model_name: string | null;
  quantity: number;
  serial_state: string;
  notes?: string | null;
  created_at: string | null;
};

export default function AssetIndex({
  assets: assetPagination,
  categories,
  batches,
  filters,
}: {
  assets: { data: AssetRow[] };
  categories: { id: number; name: string }[];
  batches: BatchRow[];
  filters: { category_id?: string | number | null };
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [selectedCategoryId, setSelectedCategoryId] = useState(
    filters?.category_id ? String(filters.category_id) : "",
  );
  const [editBatchOpen, setEditBatchOpen] = useState(false);
  const [selectedBatchId, setSelectedBatchId] = useState<number | null>(null);
  const [deleteBatchOpen, setDeleteBatchOpen] = useState(false);
  const [batchToDelete, setBatchToDelete] = useState<{ id: number; name: string } | null>(null);

  const batchForm = useForm({
    name: "",
    asset_category_id: "",
    type: "",
    model_name: "",
    quantity: 1,
    serial_state: "pending",
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

  const submitBatch = (e: React.FormEvent) => {
    e.preventDefault();

    batchForm.post("/assets/batches", {
      preserveScroll: true,
      onSuccess: () => batchForm.reset(),
    });
  };

  const applyCategoryFilter = (categoryId: string) => {
    setSelectedCategoryId(categoryId);
    window.location.href = categoryId
      ? `/assets/list?category_id=${encodeURIComponent(categoryId)}`
      : "/assets/list";
  };

  const openEditBatch = (batch: BatchRow) => {
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

      <div className="space-y-4 p-4">
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
          <a
            href={selectedCategoryId ? `/assets/export?category_id=${encodeURIComponent(selectedCategoryId)}` : "/assets/export"}
            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Export Spreadsheet
          </a>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}
        {flash.warning ? (
          <div className="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800">
            {String(flash.warning)}
          </div>
        ) : null}
        {flash.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        <div className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="flex flex-wrap items-center gap-3">
            <label className="text-sm font-medium">Filter by Category</label>
            <select
              className="min-w-[240px] rounded-md border bg-card px-3 py-2 text-sm text-foreground"
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

        <div className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Create Inventory Batch</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Create multiple asset records at once. Use pending or no serial when serial numbers are not captured yet.
          </p>
          <form className="mt-3 grid gap-3 md:grid-cols-3" onSubmit={submitBatch}>
            <input
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
              placeholder="Batch name"
              value={batchForm.data.name}
              onChange={(e) => batchForm.setData("name", e.currentTarget.value)}
              required
            />
            <select
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
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
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
              placeholder="Type"
              value={batchForm.data.type}
              onChange={(e) => batchForm.setData("type", e.currentTarget.value)}
              required
            />
            <input
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
              placeholder="Model"
              value={batchForm.data.model_name}
              onChange={(e) => batchForm.setData("model_name", e.currentTarget.value)}
              required
            />
            <input
              type="number"
              min={1}
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
              placeholder="Quantity"
              value={batchForm.data.quantity}
              onChange={(e) => batchForm.setData("quantity", Number(e.currentTarget.value || 1))}
              required
            />
            <select
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
              value={batchForm.data.serial_state}
              onChange={(e) => batchForm.setData("serial_state", e.currentTarget.value as "pending" | "no_serial")}
              required
            >
              <option value="pending">Pending Serial</option>
              <option value="no_serial">No Serial</option>
            </select>
            <input
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
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
                <thead className="bg-muted">
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
                            className="rounded-md border border-red-500 px-2 py-1 text-xs text-red-600 hover:bg-red-500 hover:text-white"
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
              label: "View",
              onClick: (row) => {
                window.location.href = `/assets/${row.id}`;
              },
            },
          ]}
        />

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
                className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                placeholder="Batch name"
                value={editBatchForm.data.name}
                onChange={(e) => editBatchForm.setData("name", e.currentTarget.value)}
                required
              />
              <select
                className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
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
                className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                placeholder="Type"
                value={editBatchForm.data.type}
                onChange={(e) => editBatchForm.setData("type", e.currentTarget.value)}
                required
              />
              <input
                className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                placeholder="Model"
                value={editBatchForm.data.model_name}
                onChange={(e) => editBatchForm.setData("model_name", e.currentTarget.value)}
                required
              />
              <select
                className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                value={editBatchForm.data.serial_state}
                onChange={(e) => editBatchForm.setData("serial_state", e.currentTarget.value as "pending" | "no_serial")}
                required
              >
                <option value="pending">Pending Serial</option>
                <option value="no_serial">No Serial</option>
              </select>
              <input
                className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
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

        {batchToDelete ? (
          <ConfirmDeleteModal
            open={deleteBatchOpen}
            onOpenChange={setDeleteBatchOpen}
            title={`Delete Batch: ${batchToDelete.name}`}
            submitRoute={(id) => ({ url: `/assets/batches/${id}`, method: "delete" })}
            routeParams={batchToDelete.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
