import { useMemo, useState } from "react";
import { Head, Link, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { BeneficiaryModelFormConfig } from "@/config/forms/beneficiary-model-form";

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
  programs,
  selectedProgramId,
  selectedProjectId,
  filterProjects,
  selectedProjectSummary,
  provinces,
  projects,
  projectLocations,
}: {
  beneficiary: { data: any[] };
  programs: { id: number; title: string }[];
  selectedProgramId: number | null;
  selectedProjectId: number | null;
  filterProjects: { id: number; name: string; program_id: number; start_date: string | null; end_date: string | null; status: string | null }[];
  selectedProjectSummary: { id: number; name: string; start_date: string | null; end_date: string | null; status: string | null } | null;
  provinces: { id: number; name: string }[];
  projects: { id: number; name: string }[];
  projectLocations: { id: number; project_id: number; name: string }[];
}) {
  const [selectedBeneficiary, setSelectedBeneficiary] = useState<any | null>(null);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [beneficiaryToDelete, setBeneficiaryToDelete] = useState<any | null>(null);
  const [selectedProgram, setSelectedProgram] = useState<string>(selectedProgramId ? String(selectedProgramId) : "");
  const [selectedProject, setSelectedProject] = useState<string>(selectedProjectId ? String(selectedProjectId) : "");

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
        project_id:
          selectedBeneficiary.project_id !== null &&
          selectedBeneficiary.project_id !== undefined
            ? String(selectedBeneficiary.project_id)
            : "",
        project_location_id:
          selectedBeneficiary.project_location_id !== null &&
          selectedBeneficiary.project_location_id !== undefined
            ? String(selectedBeneficiary.project_location_id)
            : "",
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
        attendance_status: selectedBeneficiary.attendance_status ?? "active",
        nok_name: selectedBeneficiary.next_of_kin?.name ?? "",
        nok_surname: selectedBeneficiary.next_of_kin?.surname ?? "",
        nok_relationship: selectedBeneficiary.next_of_kin?.relationship ?? "",
        nok_phone: selectedBeneficiary.next_of_kin?.phone ?? "",
        nok_email: selectedBeneficiary.next_of_kin?.email ?? "",
      }
    : {};

  const columns = useMemo(
    () => [
      {
        label: "Beneficiary",
        key: "full_name",
        className: "px-4 py-2 text-left",
        render: (row: any) => (
          <Link href={`/beneficiaries/${row.id}`} className="font-medium text-red-600 hover:underline">
            {row.full_name ?? (`${row.name ?? ""} ${row.surname ?? ""}`.trim() || "-")}
          </Link>
        ),
      },
      { label: "Email", key: "email", className: "px-4 py-2 text-left" },
      { label: "Program", key: "program_title", className: "px-4 py-2 text-left" },
      { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
      { label: "Location", key: "project_location", className: "px-4 py-2 text-left" },
      { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
    ],
    []
  );

  const applyFilters = (programId: string, projectId: string) => {
    const query: Record<string, string> = {};
    if (programId) {
      query.program_id = programId;
    }
    if (projectId) {
      query.project_id = projectId;
    }

    router.get("/beneficiaries", query, {
      preserveScroll: true,
      preserveState: true,
    });
  };


  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Beneficiaries" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Beneficiaries</h1>
            <p className="text-sm text-muted-foreground">
              Drill down by program and project to open the right beneficiary cohort.
            </p>
          </div>
        </div>

        <div className="grid gap-4 rounded-xl border bg-card p-4 shadow-sm lg:grid-cols-[1fr_1fr_auto]">
          <div>
            <label className="mb-1 block text-sm font-medium">Program</label>
            <select
              className="w-full rounded-md border bg-card px-3 py-2 text-sm"
              value={selectedProgram}
              onChange={(e) => {
                const nextProgram = e.target.value;
                setSelectedProgram(nextProgram);
                setSelectedProject("");
                applyFilters(nextProgram, "");
              }}
            >
              <option value="">Select program</option>
              {programs.map((program) => (
                <option key={program.id} value={program.id}>
                  {program.title}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">Project Iteration</label>
            <select
              className="w-full rounded-md border bg-card px-3 py-2 text-sm"
              value={selectedProject}
              disabled={!selectedProgram}
              onChange={(e) => {
                const nextProject = e.target.value;
                setSelectedProject(nextProject);
                applyFilters(selectedProgram, nextProject);
              }}
            >
              <option value="">{selectedProgram ? "Select project" : "Choose a program first"}</option>
              {filterProjects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name} ({project.start_date ?? "-"} to {project.end_date ?? "ongoing"})
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-end">
            <button
              type="button"
              className="rounded-md border px-3 py-2 text-sm hover:bg-accent"
              onClick={() => {
                setSelectedProgram("");
                setSelectedProject("");
                router.get("/beneficiaries", {}, { preserveScroll: true, preserveState: true });
              }}
            >
              Reset
            </button>
          </div>
        </div>

        {selectedProjectSummary ? (
          <div className="grid gap-4 sm:grid-cols-3">
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <div className="text-sm text-muted-foreground">Selected Project</div>
              <div className="mt-1 text-lg font-semibold">{selectedProjectSummary.name}</div>
            </div>
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <div className="text-sm text-muted-foreground">Project Window</div>
              <div className="mt-1 text-lg font-semibold">
                {selectedProjectSummary.start_date ?? "-"} to {selectedProjectSummary.end_date ?? "ongoing"}
              </div>
            </div>
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <div className="text-sm text-muted-foreground">Status</div>
              <div className="mt-1 text-lg font-semibold capitalize">{selectedProjectSummary.status ?? "-"}</div>
            </div>
          </div>
        ) : (
          <div className="rounded-xl border border-dashed bg-card p-6 text-sm text-muted-foreground">
            Select a program, then choose a project iteration to see that project&apos;s beneficiaries.
          </div>
        )}

        <div className="flex justify-between">

          <CustomModelForm
            addButton={BeneficiaryModelFormConfig.addButton}
            title="Add Beneficiary"
            description={BeneficiaryModelFormConfig.description}
            fields={BeneficiaryModelFormConfig.fields}
            submitRoute={beneficiaries.store}
            options={{ provinces, projects, projectLocations }}
          />
        </div>

        <CustomTable
          columns={columns}
          data={beneficiary.data}
          actions={[
            {
              icon: "Eye",
              label: "View beneficiary file",
              href: (row) => `/beneficiaries/${row.id}`,
            },
            {
              icon: "PencilIcon",
              label: "Edit beneficiary",
              onClick: (row) => {
                setSelectedBeneficiary(row);
              },
            },
            {
              icon: "Trash2",
              label: "Delete beneficiary",
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
            open={!!selectedBeneficiary}
            onOpenChange={(nextOpen) => {
              if (!nextOpen) {
                setSelectedBeneficiary(null);
              }
            }}
            title="Edit Beneficiary"
            fields={BeneficiaryModelFormConfig.fields}
            mode="edit"
            initialData={mappedBeneficiaryData}
            submitRoute={beneficiaries.update}
            routeParams={selectedBeneficiary.id}
            options={{ provinces, projects, projectLocations }}
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
