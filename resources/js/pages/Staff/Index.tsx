import { useState } from "react";
import { Head, Link } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { DomainNav } from "@/components/domain-nav";
import { staffNavItems } from "@/config/domain-nav/staff";
import { Button } from "@/components/ui/button";

import { StaffModelFormConfig } from "@/config/forms/staff-model-form";
import { StaffTableConfig } from "@/config/tables/staff-table";

import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff", href: "/staff" },
  { title: "List", href: "/staff/list" },
];

/* =========================================================
| PAGE
========================================================= */

export default function StaffIndex({
  staffMembers,
  departments,
}: {
  staffMembers: { data: any[] };
  departments: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedStaff, setSelectedStaff] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [staffToDelete, setStaffToDelete] = useState<any | null>(null);

  const mappedStaffData = selectedStaff
    ? {
        "staff.first_name": selectedStaff.first_name ?? "",
        "staff.last_name": selectedStaff.last_name ?? "",
        "staff.email": selectedStaff.email ?? "",
        "staff.phone": selectedStaff.phone ?? "",
        "staff.employee_number": selectedStaff.employee_number ?? "",
        "staff.department_id":
          selectedStaff.department_id !== null &&
          selectedStaff.department_id !== undefined
            ? String(selectedStaff.department_id)
            : "",
        "staff.status": selectedStaff.status ?? "active",
        "next_of_kin.full_name": selectedStaff.next_of_kin?.full_name ?? "",
        "next_of_kin.relationship": selectedStaff.next_of_kin?.relationship ?? "",
        "next_of_kin.phone": selectedStaff.next_of_kin?.phone ?? "",
        "next_of_kin.email": selectedStaff.next_of_kin?.email ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Staff" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Staff</h1>
          <DomainNav items={staffNavItems} />
        

          <CustomModelForm
            addButton={StaffModelFormConfig.addButton}
            title="Add Staff Member"
            description={StaffModelFormConfig.description}
            fields={StaffModelFormConfig.fields}
            submitRoute={staff.store}
            options={{ departments }}
          />
        </div>

        <CustomTable
          columns={StaffTableConfig.columns}
          data={staffMembers.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedStaff(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedStaff(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setStaffToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedStaff && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Staff Details" : "Edit Staff Member"}
            fields={StaffModelFormConfig.fields}
            mode={mode}
            initialData={mappedStaffData}
            submitRoute={staff.update}
            routeParams={selectedStaff.id}
            options={{ departments }}
          />
        )}

        {staffToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Staff Member"
            submitRoute={staff.destroy}
            routeParams={staffToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
