import { Head } from "@inertiajs/react";
import { useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { assetNavItems } from "@/config/domain-nav/assets";
import { AssetCategoryModelFormConfig } from "@/config/forms/asset-category-model-form";
import { AssetCategoryTableConfig } from "@/config/tables/asset-category-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

import assetCategories from "@/routes/asset-categories";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Assets", href: "/assets" },
  { title: "Categories", href: "/asset-categories" },
];

/* =========================================================
| PAGE
========================================================= */

export default function AssetCategoryIndex({
  categories,
}: {
  categories: { data: any[] };
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedCategory, setSelectedCategory] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [categoryToDelete, setCategoryToDelete] = useState<any | null>(null);

  const mappedCategoryData = selectedCategory
    ? {
        name: selectedCategory.name ?? "",
        description: selectedCategory.description ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Asset Categories" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Asset Categories</h1>
          <DomainNav items={assetNavItems} />

          <CustomModelForm
            addButton={AssetCategoryModelFormConfig.addButton}
            title="Add Category"
            description={AssetCategoryModelFormConfig.description}
            fields={AssetCategoryModelFormConfig.fields}
            submitRoute={assetCategories.store}
          />
        </div>

        <CustomTable
          columns={AssetCategoryTableConfig.columns}
          data={categories.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedCategory(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedCategory(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setCategoryToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedCategory && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Category Details" : "Edit Category"}
            fields={AssetCategoryModelFormConfig.fields}
            mode={mode}
            initialData={mappedCategoryData}
            submitRoute={assetCategories.update}
            routeParams={selectedCategory.id}
          />
        )}

        {categoryToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Category"
            submitRoute={assetCategories.destroy}
            routeParams={categoryToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
