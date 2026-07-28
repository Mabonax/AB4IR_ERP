import { Head } from "@inertiajs/react";
import { useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { StakeholderModelFormConfig } from "@/config/forms/stakeholder-model-form";
import { StakeholderTableConfig } from "@/config/tables/stakeholder-table";
import AppLayout from "@/layouts/app-layout";
import stakeholders from "@/routes/stakeholders";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Stakeholders", href: stakeholders.index() },
];

export default function StakeholderIndex({
  stakeholders: stakeholderPagination,
}: {
  stakeholders: { data: any[] };
}) {
  const [editOpen, setEditOpen] = useState(false);
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

      <div className="space-y-4 p-4">
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
              label: "View stakeholder",
              href: (row) => stakeholders.show(row.id).url,
            },
            {
              icon: "Pencil",
              label: "Edit stakeholder",
              onClick: (row) => {
                setSelectedStakeholder(row);
                setEditOpen(true);
              },
            },
            {
              icon: "Trash2",
              label: "Delete stakeholder",
              variant: "danger",
              onClick: (row) => {
                setStakeholderToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedStakeholder ? (
          <CustomModelForm
            hideTrigger
            open={editOpen}
            onOpenChange={setEditOpen}
            title="Edit Stakeholder"
            fields={StakeholderModelFormConfig.fields}
            mode="edit"
            initialData={mappedStakeholderData}
            submitRoute={stakeholders.update}
            routeParams={selectedStakeholder.id}
          />
        ) : null}

        {stakeholderToDelete ? (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Stakeholder"
            submitRoute={stakeholders.destroy}
            routeParams={stakeholderToDelete.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
