import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { ProgramModelFormConfig } from "@/config/forms/program-model-form";
import { ProgramTableConfig } from "@/config/tables/program-table";

import programs from "@/routes/programs";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Programs", href: programs.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function ProgramIndex({
  programs: programPagination,
}: {
  programs: { data: any[] };
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedProgram, setSelectedProgram] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [programToDelete, setProgramToDelete] = useState<any | null>(null);

  const mappedProgramData = selectedProgram
    ? {
        title: selectedProgram.title ?? "",
        description: selectedProgram.description ?? "",
        slug: selectedProgram.slug ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Programs" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Programs</h1>

          <CustomModelForm
            addButton={ProgramModelFormConfig.addButton}
            title="Add Program"
            description={ProgramModelFormConfig.description}
            fields={ProgramModelFormConfig.fields}
            submitRoute={programs.store}
          />
        </div>

        <CustomTable
          columns={ProgramTableConfig.columns}
          data={programPagination.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedProgram(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedProgram(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setProgramToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedProgram && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Program Details" : "Edit Program"}
            fields={ProgramModelFormConfig.fields}
            mode={mode}
            initialData={mappedProgramData}
            submitRoute={programs.update}
            routeParams={selectedProgram.id}
          />
        )}

        {programToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Program"
            submitRoute={programs.destroy}
            routeParams={programToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}