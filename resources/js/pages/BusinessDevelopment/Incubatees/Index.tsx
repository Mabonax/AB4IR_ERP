import { Head } from "@inertiajs/react";
import { useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import { BdsIncubateeModelFormConfig } from "@/config/forms/bds-incubatee-model-form";
import { BdsIncubateeTableConfig } from "@/config/tables/bds-incubatee-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Incubatees", href: "/business-development/incubatees" },
];

type IncubateeRow = {
  id: number;
  full_name: string;
  id_number: string;
  gender: string;
  mobile_number: string;
  email: string;
  company_name: string;
  company_registration_number: string;
  position_in_company: string | null;
  majority_shareholding: string | null;
  current_number_of_employees: number;
  physical_address: string | null;
  website_address: string | null;
  years_in_operation: number;
  province_id: number | null;
  has_business_plan: boolean;
  relevant_skill_set: string;
  technology_product_service: string;
  technology_stage_of_development: string;
  status: "active" | "inactive";
};

export default function BdsIncubateesIndex({
  incubatees,
  provinces,
}: {
  incubatees: { data: IncubateeRow[] };
  provinces: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedIncubatee, setSelectedIncubatee] = useState<IncubateeRow | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [incubateeToDelete, setIncubateeToDelete] = useState<IncubateeRow | null>(null);

  const mappedData = selectedIncubatee
    ? {
        full_name: selectedIncubatee.full_name ?? "",
        id_number: selectedIncubatee.id_number ?? "",
        gender: selectedIncubatee.gender ?? "",
        mobile_number: selectedIncubatee.mobile_number ?? "",
        email: selectedIncubatee.email ?? "",
        company_name: selectedIncubatee.company_name ?? "",
        company_registration_number: selectedIncubatee.company_registration_number ?? "",
        position_in_company: selectedIncubatee.position_in_company ?? "",
        majority_shareholding: selectedIncubatee.majority_shareholding ?? "",
        current_number_of_employees: selectedIncubatee.current_number_of_employees ?? "",
        physical_address: selectedIncubatee.physical_address ?? "",
        website_address: selectedIncubatee.website_address ?? "",
        years_in_operation: selectedIncubatee.years_in_operation ?? "",
        province_id: selectedIncubatee.province_id ? String(selectedIncubatee.province_id) : "",
        has_business_plan: selectedIncubatee.has_business_plan ? "1" : "0",
        relevant_skill_set: selectedIncubatee.relevant_skill_set ?? "",
        technology_product_service: selectedIncubatee.technology_product_service ?? "",
        technology_stage_of_development: selectedIncubatee.technology_stage_of_development ?? "",
        status: selectedIncubatee.status ?? "active",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="BDS Incubatees" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Incubatees</h1>
          <DomainNav items={businessDevelopmentNavItems} />

          <CustomModelForm
            addButton={BdsIncubateeModelFormConfig.addButton}
            title="Add Incubatee"
            description={BdsIncubateeModelFormConfig.description}
            fields={BdsIncubateeModelFormConfig.fields}
            submitRoute={() => ({ url: "/business-development/incubatees", method: "post" })}
            options={{ provinces }}
          />
        </div>

        <CustomTable
          columns={BdsIncubateeTableConfig.columns}
          data={incubatees.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedIncubatee(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedIncubatee(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setIncubateeToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedIncubatee && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Incubatee Details" : "Edit Incubatee"}
            fields={BdsIncubateeModelFormConfig.fields}
            mode={mode}
            initialData={mappedData}
            submitRoute={(id) => ({ url: `/business-development/incubatees/${id}`, method: "put" })}
            routeParams={selectedIncubatee.id}
            options={{ provinces }}
          />
        )}

        {incubateeToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Incubatee"
            submitRoute={(id) => ({ url: `/business-development/incubatees/${id}`, method: "delete" })}
            routeParams={incubateeToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
