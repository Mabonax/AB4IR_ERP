import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { StakeholderModelFormConfig } from "@/config/forms/stakeholder-model-form";
import { StakeholderTableConfig } from "@/config/tables/stakeholder-table";

import stakeholders from "@/routes/stakeholders";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Stakeholders", href: stakeholders.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function StakeholderIndex({
  stakeholders: stakeholderPagination,
}: {
  stakeholders: { data: any[] };
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedStakeholder, setSelectedStakeholder] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [stakeholderToDelete, setStakeholderToDelete] = useState<any | null>(null);

  const mappedStakeholderData = selectedStakeholder
    ? {
        "stakeholder.organization_name": selectedStakeholder.organization_name ?? "",
        "stakeholder.name": selectedStakeholder.name ?? "",
        "stakeholder.email": selectedStakeholder.email ?? "",
        "stakeholder.contact_number": selectedStakeholder.contact_number ?? "",
        "stakeholder.status": selectedStakeholder.status ?? "active",
        "contact.full_name": selectedStakeholder.contact?.full_name ?? "",
        "contact.email": selectedStakeholder.contact?.email ?? "",
        "contact.contact_number": selectedStakeholder.contact?.contact_number ?? "",
        "contact.position": selectedStakeholder.contact?.position ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stakeholders" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Stakeholders</h1>

          <CustomModelForm
            addButton={StakeholderModelFormConfig.addButton}
            title="Add Stakeholder"
            description={StakeholderModelFormConfig.description}
            fields={StakeholderModelFormConfig.fields}
            submitRoute={stakeholders.store}
          />
        </div>

        <CustomTable
          columns={StakeholderTableConfig.columns}
          data={stakeholderPagination.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedStakeholder(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedStakeholder(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setStakeholderToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedStakeholder && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Stakeholder Details" : "Edit Stakeholder"}
            fields={StakeholderModelFormConfig.fields}
            mode={mode}
            initialData={mappedStakeholderData}
            submitRoute={stakeholders.update}
            routeParams={selectedStakeholder.id}
          />
        )}

        {stakeholderToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Stakeholder"
            submitRoute={stakeholders.destroy}
            routeParams={stakeholderToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
