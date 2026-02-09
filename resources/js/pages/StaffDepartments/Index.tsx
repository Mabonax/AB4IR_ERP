import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { StaffDepartmentModelFormConfig } from "@/config/forms/staff-department-model-form";
import { StaffDepartmentTableConfig } from "@/config/tables/staff-department-table";

import staffDepartments from "@/routes/staff-departments";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff Departments", href: staffDepartments.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function StaffDepartmentIndex({
  departments,
}: {
  departments: { data: any[] };
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedDepartment, setSelectedDepartment] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [departmentToDelete, setDepartmentToDelete] = useState<any | null>(null);

  const mappedDepartmentData = selectedDepartment
    ? {
        name: selectedDepartment.name ?? "",
        description: selectedDepartment.description ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Staff Departments" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Staff Departments</h1>

          <CustomModelForm
            addButton={StaffDepartmentModelFormConfig.addButton}
            title="Add Department"
            description={StaffDepartmentModelFormConfig.description}
            fields={StaffDepartmentModelFormConfig.fields}
            submitRoute={staffDepartments.store}
          />
        </div>

        <CustomTable
          columns={StaffDepartmentTableConfig.columns}
          data={departments.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedDepartment(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedDepartment(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setDepartmentToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedDepartment && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Department Details" : "Edit Department"}
            fields={StaffDepartmentModelFormConfig.fields}
            mode={mode}
            initialData={mappedDepartmentData}
            submitRoute={staffDepartments.update}
            routeParams={selectedDepartment.id}
          />
        )}

        {departmentToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Department"
            submitRoute={staffDepartments.destroy}
            routeParams={departmentToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
