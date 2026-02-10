import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { ProjectEnrollmentModelFormConfig } from "@/config/forms/project-enrollment-model-form";
import { ProjectEnrollmentTableConfig } from "@/config/tables/project-enrollment-table";

import projectEnrollments from "@/routes/project-enrollments";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Project Enrollments", href: projectEnrollments.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function ProjectEnrollmentIndex({
  enrollments,
  projects,
  beneficiaries,
}: {
  enrollments: { data: any[] };
  projects: { id: number; name: string }[];
  beneficiaries: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedEnrollment, setSelectedEnrollment] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [enrollmentToDelete, setEnrollmentToDelete] = useState<any | null>(null);

  const mappedEnrollmentData = selectedEnrollment
    ? {
        project_id:
          selectedEnrollment.project_id !== null &&
          selectedEnrollment.project_id !== undefined
            ? String(selectedEnrollment.project_id)
            : "",
        beneficiary_id:
          selectedEnrollment.beneficiary_id !== null &&
          selectedEnrollment.beneficiary_id !== undefined
            ? String(selectedEnrollment.beneficiary_id)
            : "",
        status: selectedEnrollment.status ?? "enrolled",
        enrolled_at: selectedEnrollment.enrolled_at
          ? String(selectedEnrollment.enrolled_at).slice(0, 10)
          : "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project Enrollments" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Project Enrollments</h1>

          <CustomModelForm
            addButton={ProjectEnrollmentModelFormConfig.addButton}
            title="Enroll Beneficiary"
            description={ProjectEnrollmentModelFormConfig.description}
            fields={ProjectEnrollmentModelFormConfig.fields}
            submitRoute={projectEnrollments.store}
            options={{ projects, beneficiaries }}
          />
        </div>

        <CustomTable
          columns={ProjectEnrollmentTableConfig.columns}
          data={enrollments.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedEnrollment(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedEnrollment(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setEnrollmentToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedEnrollment && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Enrollment Details" : "Edit Enrollment"}
            fields={ProjectEnrollmentModelFormConfig.fields}
            mode={mode}
            initialData={mappedEnrollmentData}
            submitRoute={projectEnrollments.update}
            routeParams={selectedEnrollment.id}
            options={{ projects, beneficiaries }}
          />
        )}

        {enrollmentToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Enrollment"
            submitRoute={projectEnrollments.destroy}
            routeParams={enrollmentToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
