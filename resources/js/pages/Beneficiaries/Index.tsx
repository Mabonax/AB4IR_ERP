import { useMemo, useRef, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { Upload } from "lucide-react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

import beneficiaries from "@/routes/beneficiaries";
import { type BreadcrumbItem, type SharedData } from "@/types";

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
  selectedProjectLocations,
  selectedProjectSummary,
}: {
  beneficiary: { data: any[] };
  programs: { id: number; title: string }[];
  selectedProgramId: number | null;
  selectedProjectId: number | null;
  filterProjects: { id: number; name: string; program_id: number; start_date: string | null; end_date: string | null; status: string | null }[];
  selectedProjectLocations: { id: number; name: string }[];
  selectedProjectSummary: { id: number; name: string; start_date: string | null; end_date: string | null; status: string | null } | null;
}) {
  const { flash } = usePage<SharedData>().props;
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [beneficiaryToDelete, setBeneficiaryToDelete] = useState<any | null>(null);
  const [selectedProgram, setSelectedProgram] = useState<string>(selectedProgramId ? String(selectedProgramId) : "");
  const [selectedProject, setSelectedProject] = useState<string>(selectedProjectId ? String(selectedProjectId) : "");
  const [importDialogOpen, setImportDialogOpen] = useState(false);
  const [importLocationId, setImportLocationId] = useState<string>(selectedProjectLocations[0] ? String(selectedProjectLocations[0].id) : "");
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const importErrors = Array.isArray(flash?.import_errors) ? (flash.import_errors as string[]) : [];

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

  const submitImport = () => {
    const file = fileInputRef.current?.files?.[0];

    if (!selectedProject || !importLocationId || !file) {
      return;
    }

    router.post("/beneficiaries/import", {
      file,
      project_id: selectedProject,
      project_location_id: importLocationId,
    }, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        setImportDialogOpen(false);
        if (fileInputRef.current) {
          fileInputRef.current.value = "";
        }
      },
    });
  };


  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Beneficiaries" />

      <div className="p-4 space-y-4">
        {importErrors.length > 0 ? (
          <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <div className="font-semibold">Import errors</div>
            <ul className="mt-2 list-disc space-y-1 pl-5">
              {importErrors.map((error) => (
                <li key={error}>{error}</li>
              ))}
            </ul>
          </div>
        ) : null}

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

        <div className="flex flex-wrap justify-between gap-3">
          <Link
            href={beneficiaries.create().url}
            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
          >
            Add Beneficiary
          </Link>

          <Button
            type="button"
            variant="outline"
            disabled={!selectedProject || selectedProjectLocations.length === 0}
            onClick={() => {
              setImportLocationId(selectedProjectLocations[0] ? String(selectedProjectLocations[0].id) : "");
              setImportDialogOpen(true);
            }}
          >
            <Upload className="mr-2 h-4 w-4" />
            Import Beneficiaries
          </Button>
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
              href: (row) => beneficiaries.edit(row.id).url,
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

        {beneficiaryToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Beneficiary"
            submitRoute={beneficiaries.destroy}
            routeParams={beneficiaryToDelete.id}
          />
        )}

        <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Import Beneficiaries</DialogTitle>
              <DialogDescription>
                Import into the selected project iteration. Required spreadsheet headers: <code>name</code> and <code>surname</code>.
                Use optional identity fields like <code>dob</code>, <code>id_number</code>, and <code>email</code> to improve matching.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4">
              <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                <div className="font-medium">Target project</div>
                <div className="text-muted-foreground">{selectedProjectSummary?.name ?? "No project selected"}</div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="beneficiary-import-location">Project location</Label>
                <select
                  id="beneficiary-import-location"
                  className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  value={importLocationId}
                  onChange={(e) => setImportLocationId(e.target.value)}
                >
                  {selectedProjectLocations.map((location) => (
                    <option key={location.id} value={location.id}>
                      {location.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="beneficiary-import-file">Spreadsheet file</Label>
                <Input id="beneficiary-import-file" ref={fileInputRef} type="file" accept=".csv,.txt,.xlsx" />
                <p className="text-xs text-muted-foreground">
                  Optional headers supported: dob, age, id_number, email, phone, gender, street_address, address_line_2, city, province,
                  postal_code, highest_qualification, attendance_status, nok_name, nok_surname, nok_relationship, nok_phone, nok_email.
                </p>
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setImportDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="button" onClick={submitImport} disabled={!selectedProject || !importLocationId}>
                Import Spreadsheet
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AppLayout>
  );
}
