import { Head } from "@inertiajs/react";
import { useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { programNavItems } from "@/config/domain-nav/programs";
import { ProgramModelFormConfig } from "@/config/forms/program-model-form";
import { ProgramTableConfig } from "@/config/tables/program-table";
import AppLayout from "@/layouts/app-layout";
import programs from "@/routes/programs";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Programs", href: programs.list() },
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
  const [mode, setMode] = useState<"create" | "edit">("create");
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
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-semibold">Programs</h1>
          </div>

          <div className="flex items-center gap-3">
            <CustomModelForm
              addButton={ProgramModelFormConfig.addButton}
              title="Add Program"
              description={ProgramModelFormConfig.description}
              fields={ProgramModelFormConfig.fields}
              submitRoute={programs.store}
            />
            <DomainNav items={programNavItems} />
          </div>
        </div>

        <CustomTable
          columns={ProgramTableConfig.columns}
          data={programPagination.data}
          actions={[
            {
              icon: "Eye",
              href: (row) => programs.show(row.id).url,
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
            title="Edit Program"
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
