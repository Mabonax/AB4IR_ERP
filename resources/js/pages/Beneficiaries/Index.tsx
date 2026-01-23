import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { BeneficiaryModelFormConfig } from "@/config/forms/beneficiary-model-form";
import { BeneficiaryTableConfig } from "@/config/tables/beneficiary-table";

import beneficiaries from "@/routes/beneficiaries";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Beneficiaries", href: beneficiaries.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function BeneficiaryIndex({
  beneficiary,
  provinces,
}: {
  beneficiary: { data: any[] };
  provinces: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedBeneficiary, setSelectedBeneficiary] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [beneficiaryToDelete, setBeneficiaryToDelete] = useState<any | null>(null);

  /* ------------------------------
   | MAP NEXT OF KIN (FIX)
  ------------------------------ */
const mappedBeneficiaryData = selectedBeneficiary
  ? {
      name: selectedBeneficiary.name ?? "",
      surname: selectedBeneficiary.surname ?? "",
      dob: selectedBeneficiary.dob ?? "",
      age: selectedBeneficiary.age ?? "",
      id_number: selectedBeneficiary.id_number ?? "",
      email: selectedBeneficiary.email ?? "",
      phone: selectedBeneficiary.phone ?? "",
      gender: selectedBeneficiary.gender ?? "",
      street_address: selectedBeneficiary.street_address ?? "",
      address_line_2: selectedBeneficiary.address_line_2 ?? "",
      city: selectedBeneficiary.city ?? "",
      province_id:
        selectedBeneficiary.province_id !== null &&
        selectedBeneficiary.province_id !== undefined
          ? String(selectedBeneficiary.province_id)
          : "",
      postal_code: selectedBeneficiary.postal_code ?? "",
      highest_qualification: selectedBeneficiary.highest_qualification ?? "",
      nok_name: selectedBeneficiary.next_of_kin?.name ?? "",
      nok_surname: selectedBeneficiary.next_of_kin?.surname ?? "",
      nok_relationship:
        selectedBeneficiary.next_of_kin?.relationship ?? "",
      nok_phone: selectedBeneficiary.next_of_kin?.phone ?? "",
      nok_email: selectedBeneficiary.next_of_kin?.email ?? "",
    }
  : {};


  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Beneficiaries" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Beneficiaries</h1>

          <CustomModelForm
            addButton={BeneficiaryModelFormConfig.addButton}
            title="Add Beneficiary"
            description={BeneficiaryModelFormConfig.description}
            fields={BeneficiaryModelFormConfig.fields}
            submitRoute={beneficiaries.store}
            options={{ provinces }}
          />
        </div>

        <CustomTable
          columns={BeneficiaryTableConfig.columns}
          data={beneficiary.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedBeneficiary(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedBeneficiary(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setBeneficiaryToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedBeneficiary && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Beneficiary Details" : "Edit Beneficiary"}
            fields={BeneficiaryModelFormConfig.fields}
            mode={mode}
            initialData={mappedBeneficiaryData}
            submitRoute={beneficiaries.update}
            routeParams={selectedBeneficiary.id}
            options={{ provinces }}
          />
        )}

        {beneficiaryToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Beneficiary"
            submitRoute={beneficiaries.destroy}
            routeParams={beneficiaryToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
