import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { FacilitatorModelFormConfig } from "@/config/forms/facilitator-model-form";
import { FacilitatorTableConfig } from "@/config/tables/facilitator-table";

import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Facilitators", href: facilitators.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function FacilitatorIndex({
  facilitators: facilitatorPagination,
  provinces = [],
}: {
  facilitators: { data: any[] };
  provinces?: { id: number; name: string }[] | { data: { id: number; name: string }[] };
}) {
  const provinceOptions = Array.isArray(provinces)
    ? provinces
    : Array.isArray((provinces as any)?.data)
      ? (provinces as any).data
      : [];
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedFacilitator, setSelectedFacilitator] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [facilitatorToDelete, setFacilitatorToDelete] = useState<any | null>(null);

  const mappedFacilitatorData = selectedFacilitator
    ? {
        name: selectedFacilitator.name ?? "",
        surname: selectedFacilitator.surname ?? "",
        dob: selectedFacilitator.dob ?? "",
        id_number: selectedFacilitator.id_number ?? "",
        address: selectedFacilitator.address ?? "",
        email: selectedFacilitator.email ?? "",
        cell: selectedFacilitator.cell ?? "",
        specialization: selectedFacilitator.specialization ?? "",
        province_id:
          selectedFacilitator.province_id !== null &&
          selectedFacilitator.province_id !== undefined
            ? String(selectedFacilitator.province_id)
            : "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Facilitators" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Facilitators</h1>

          <CustomModelForm
            addButton={FacilitatorModelFormConfig.addButton}
            title="Add Facilitator"
            description={FacilitatorModelFormConfig.description}
            fields={FacilitatorModelFormConfig.fields}
            submitRoute={facilitators.store}
            options={{ provinces: provinceOptions }}
          />
        </div>

        <CustomTable
          columns={FacilitatorTableConfig.columns}
          data={facilitatorPagination.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedFacilitator(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedFacilitator(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setFacilitatorToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedFacilitator && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Facilitator Details" : "Edit Facilitator"}
            fields={FacilitatorModelFormConfig.fields}
            mode={mode}
            initialData={mappedFacilitatorData}
            submitRoute={facilitators.update}
            routeParams={selectedFacilitator.id}
            options={{ provinces: provinceOptions }}
          />
        )}

        {facilitatorToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Facilitator"
            submitRoute={facilitators.destroy}
            routeParams={facilitatorToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
