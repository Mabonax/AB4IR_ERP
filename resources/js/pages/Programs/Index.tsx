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
  committees,
  staffMembers,
}: {
  programs: { data: any[] };
  committees: Array<{ id: number; name: string }>;
  staffMembers: Array<{ id: number; name: string }>;
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit">("create");
  const [selectedProgram, setSelectedProgram] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [programToDelete, setProgramToDelete] = useState<any | null>(null);

  const mappedProgramData = selectedProgram
    ? {
        title: selectedProgram.title ?? "",
        code: selectedProgram.code ?? "",
        description: selectedProgram.description ?? "",
        strategic_objective: selectedProgram.strategic_objective ?? "",
        start_date: selectedProgram.start_date ?? "",
        end_date: selectedProgram.end_date ?? "",
        status: selectedProgram.status ?? "draft",
        budget: selectedProgram.budget ?? "",
        funding_source: selectedProgram.funding_source ?? "",
        responsible_committee_id:
          selectedProgram.responsible_committee_id !== null && selectedProgram.responsible_committee_id !== undefined
            ? String(selectedProgram.responsible_committee_id)
            : "",
        programme_manager_id:
          selectedProgram.programme_manager_id !== null && selectedProgram.programme_manager_id !== undefined
            ? String(selectedProgram.programme_manager_id)
            : "",
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
              options={{ committees, staffMembers }}
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
            options={{ committees, staffMembers }}
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
