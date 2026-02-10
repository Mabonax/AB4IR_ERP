import { useState } from "react";
import { Head, Link } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { DomainNav } from "@/components/domain-nav";
import { assetNavItems } from "@/config/domain-nav/assets";
import { Button } from "@/components/ui/button";

import { AssetModelFormConfig } from "@/config/forms/asset-model-form";
import { AssetTableConfig } from "@/config/tables/asset-table";

import assets from "@/routes/assets";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Assets", href: "/assets" },
  { title: "List", href: "/assets/list" },
];

/* =========================================================
| PAGE
========================================================= */

export default function AssetIndex({
  assets: assetPagination,
  categories,
  staffMembers,
}: {
  assets: { data: any[] };
  categories: { id: number; name: string }[];
  staffMembers: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedAsset, setSelectedAsset] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [assetToDelete, setAssetToDelete] = useState<any | null>(null);

  const mappedAssetData = selectedAsset
    ? {
        name: selectedAsset.name ?? "",
        asset_category_id:
          selectedAsset.asset_category_id !== null &&
          selectedAsset.asset_category_id !== undefined
            ? String(selectedAsset.asset_category_id)
            : "",
        type: selectedAsset.type ?? "",
        serial_number: selectedAsset.serial_number ?? "",
        status: selectedAsset.status ?? "unassigned",
        staff_member_id:
          selectedAsset.staff_member_id !== null &&
          selectedAsset.staff_member_id !== undefined
            ? String(selectedAsset.staff_member_id)
            : "",
      }
    : {};

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
            options={{ categories, staffMembers }}
          />
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
            options={{ categories, staffMembers }}
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
      </div>
    </AppLayout>
  );
}
