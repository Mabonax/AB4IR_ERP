import { useMemo, useRef, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { Eye, Pencil, Search, Trash2, Upload, UserCheck, UserRoundPlus, UsersRound } from "lucide-react";

import AppLayout from "@/layouts/app-layout";
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

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Beneficiaries", href: beneficiaries.index() },
];

const pct = (value: number, total: number) => (total > 0 ? Math.round((value / total) * 100) : 0);

function MetricCard({ label, value, note, icon: Icon, tone }: { label: string; value: number | string; note: string; icon: any; tone: string }) {
  return (
    <section className="rounded-lg border bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-semibold text-slate-900">{label}</p>
          <p className="mt-2 text-3xl font-semibold">{value}</p>
          <p className="mt-2 text-xs text-slate-500">{note}</p>
        </div>
        <span className={`inline-flex h-11 w-11 items-center justify-center rounded-full ${tone}`}>
          <Icon className="h-5 w-5" />
        </span>
      </div>
    </section>
  );
}

function LifecycleDonut({ metrics, total }: { metrics: any; total: number }) {
  const graduated = pct(metrics?.graduated_beneficiaries ?? 0, total);
  const exited = pct(metrics?.exited_beneficiaries ?? 0, total);
  const unknown = Math.max(0, 100 - graduated - exited);

  return (
    <section className="rounded-lg border bg-white p-5 shadow-sm">
      <h2 className="text-lg font-semibold">Lifecycle Overview</h2>
      <p className="text-sm text-slate-500">Current outcome state for the selected cohort.</p>
      <div className="mt-6 flex flex-col items-center gap-5 md:flex-row md:justify-center xl:flex-col">
        <div
          className="grid h-48 w-48 place-items-center rounded-full"
          style={{ background: `conic-gradient(#16a34a 0 ${graduated}%, #ef4444 ${graduated}% ${graduated + exited}%, #3b82f6 ${graduated + exited}% ${graduated + exited + unknown}%, #e2e8f0 0 100%)` }}
        >
          <div className="grid h-28 w-28 place-items-center rounded-full bg-white text-center shadow-inner">
            <div>
              <div className="text-3xl font-semibold">{total}</div>
              <div className="text-xs text-slate-500">Total</div>
            </div>
          </div>
        </div>
        <div className="w-full max-w-xs space-y-3 text-sm">
          {[
            ["Graduated", metrics?.graduated_beneficiaries ?? 0, "bg-emerald-500"],
            ["Exited", metrics?.exited_beneficiaries ?? 0, "bg-red-500"],
            ["Employment", metrics?.employment_outcomes ?? 0, "bg-blue-500"],
            ["Further education", metrics?.further_education_outcomes ?? 0, "bg-orange-500"],
            ["Unknown", metrics?.unknown_outcomes ?? 0, "bg-slate-300"],
          ].map(([label, value, color]) => (
            <div key={label} className="flex items-center justify-between gap-4">
              <span className="inline-flex items-center gap-2"><span className={`h-2.5 w-2.5 rounded-full ${color}`} />{label}</span>
              <span className="font-semibold">{value}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export default function BeneficiaryIndex({
  beneficiary,
  programs,
  selectedProgramId,
  selectedProjectId,
  filterProjects,
  selectedProjectLocations,
  selectedProjectSummary,
  lifecycleMetrics,
}: {
  beneficiary: { data: any[] };
  programs: { id: number; title: string }[];
  selectedProgramId: number | null;
  selectedProjectId: number | null;
  filterProjects: { id: number; name: string; program_id: number; start_date: string | null; end_date: string | null; status: string | null }[];
  selectedProjectLocations: { id: number; name: string }[];
  selectedProjectSummary: { id: number; name: string; start_date: string | null; end_date: string | null; status: string | null } | null;
  lifecycleMetrics: {
    graduated_beneficiaries: number;
    exited_beneficiaries: number;
    employment_outcomes: number;
    further_education_outcomes: number;
    unknown_outcomes: number;
  } | null;
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
  const rows = beneficiary.data;

  const locationChartData = useMemo(() => {
    const counts = rows.reduce((carry: Record<string, number>, item: any) => {
      const key = item.project_location ?? "Unassigned location";
      carry[key] = (carry[key] ?? 0) + 1;

      return carry;
    }, {});

    return Object.entries(counts).sort((a, b) => b[1] - a[1]);
  }, [rows]);

  const applyFilters = (programId: string, projectId: string) => {
    const query: Record<string, string> = {};
    if (programId) query.program_id = programId;
    if (projectId) query.project_id = projectId;

    router.get("/beneficiaries", query, { preserveScroll: true, preserveState: true });
  };

  const submitImport = () => {
    const file = fileInputRef.current?.files?.[0];

    if (!selectedProject || !importLocationId || !file) return;

    router.post("/beneficiaries/import", {
      file,
      project_id: selectedProject,
      project_location_id: importLocationId,
    }, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        setImportDialogOpen(false);
        if (fileInputRef.current) fileInputRef.current.value = "";
      },
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Beneficiaries" />

      <div className="space-y-6 bg-white p-4 text-slate-950 md:p-6">
        {importErrors.length > 0 ? (
          <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <div className="font-semibold">Import errors</div>
            <ul className="mt-2 list-disc space-y-1 pl-5">
              {importErrors.map((error) => <li key={error}>{error}</li>)}
            </ul>
          </div>
        ) : null}

        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-3xl font-semibold tracking-normal">Beneficiaries</h1>
            <p className="mt-1 text-sm text-slate-500">Program cohort, placement, and lifecycle overview.</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link href={beneficiaries.create().url} className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
              <UserRoundPlus className="h-4 w-4" />
              Add Beneficiary
            </Link>
            <Button type="button" variant="outline" disabled={!selectedProject || selectedProjectLocations.length === 0} onClick={() => {
              setImportLocationId(selectedProjectLocations[0] ? String(selectedProjectLocations[0].id) : "");
              setImportDialogOpen(true);
            }}>
              <Upload className="mr-2 h-4 w-4" />
              Import
            </Button>
          </div>
        </div>

        <section className="grid gap-4 rounded-lg border bg-white p-4 shadow-sm lg:grid-cols-[1fr_1fr_auto]">
          <div>
            <label className="mb-1 block text-sm font-medium">Program</label>
            <select className="w-full rounded-lg border bg-white px-3 py-2 text-sm" value={selectedProgram} onChange={(e) => {
              const nextProgram = e.target.value;
              setSelectedProgram(nextProgram);
              setSelectedProject("");
              applyFilters(nextProgram, "");
            }}>
              <option value="">Select program</option>
              {programs.map((program) => <option key={program.id} value={program.id}>{program.title}</option>)}
            </select>
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">Project Iteration</label>
            <select className="w-full rounded-lg border bg-white px-3 py-2 text-sm" value={selectedProject} disabled={!selectedProgram} onChange={(e) => {
              const nextProject = e.target.value;
              setSelectedProject(nextProject);
              applyFilters(selectedProgram, nextProject);
            }}>
              <option value="">{selectedProgram ? "Select project" : "Choose a program first"}</option>
              {filterProjects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name} ({project.start_date ?? "-"} to {project.end_date ?? "ongoing"})
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-end">
            <button type="button" className="h-10 rounded-lg border px-4 text-sm font-medium hover:bg-slate-50" onClick={() => {
              setSelectedProgram("");
              setSelectedProject("");
              router.get("/beneficiaries", {}, { preserveScroll: true, preserveState: true });
            }}>
              Reset
            </button>
          </div>
        </section>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <MetricCard label="Tracked Beneficiaries" value={rows.length} note="Current filtered cohort" icon={UsersRound} tone="bg-red-50 text-red-600" />
          <MetricCard label="Graduated" value={lifecycleMetrics?.graduated_beneficiaries ?? 0} note="Completed lifecycle" icon={UserCheck} tone="bg-emerald-50 text-emerald-600" />
          <MetricCard label="Exited" value={lifecycleMetrics?.exited_beneficiaries ?? 0} note="Exited cohort" icon={Trash2} tone="bg-orange-50 text-orange-600" />
          <MetricCard label="Locations" value={locationChartData.length} note="Active delivery sites" icon={Search} tone="bg-blue-50 text-blue-600" />
        </div>

        <div className="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
          <section className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Location Distribution</h2>
            <p className="text-sm text-slate-500">Beneficiaries by selected delivery location.</p>
            <div className="mt-5 space-y-4">
              {locationChartData.length ? locationChartData.map(([label, value]) => (
                <div key={label}>
                  <div className="mb-2 flex items-center justify-between text-sm">
                    <span className="font-medium">{label}</span>
                    <span className="text-slate-500">{value}</span>
                  </div>
                  <div className="h-3 rounded-full bg-slate-100">
                    <div className="h-3 rounded-full bg-red-500" style={{ width: `${Math.max(8, pct(value, rows.length))}%` }} />
                  </div>
                </div>
              )) : <div className="rounded-lg border border-dashed p-6 text-sm text-slate-500">Select a program and project to view cohort distribution.</div>}
            </div>
          </section>

          <LifecycleDonut metrics={lifecycleMetrics} total={rows.length} />
        </div>

        <section className="overflow-hidden rounded-lg border bg-white shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
            <div>
              <h2 className="text-lg font-semibold">Beneficiary Register</h2>
              <p className="text-sm text-slate-500">
                {selectedProjectSummary ? `${selectedProjectSummary.name} | ${selectedProjectSummary.status ?? "status pending"}` : "Select filters to narrow the register."}
              </p>
            </div>
            <div className="relative">
              <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <input className="h-10 rounded-lg border pl-9 pr-3 text-sm" placeholder="Search beneficiaries..." />
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-4 py-3 text-left">Beneficiary</th>
                  <th className="px-4 py-3 text-left">Email</th>
                  <th className="px-4 py-3 text-left">Program</th>
                  <th className="px-4 py-3 text-left">Project</th>
                  <th className="px-4 py-3 text-left">Location</th>
                  <th className="px-4 py-3 text-left">Lifecycle</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {rows.length ? rows.map((row: any) => (
                  <tr key={row.id} className="border-t">
                    <td className="px-4 py-3">
                      <Link href={`/beneficiaries/${row.id}`} className="font-semibold text-slate-900 hover:text-red-600">
                        {row.full_name ?? (`${row.name ?? ""} ${row.surname ?? ""}`.trim() || "-")}
                      </Link>
                    </td>
                    <td className="px-4 py-3 text-slate-600">{row.email ?? "-"}</td>
                    <td className="px-4 py-3">{row.program_title ?? "-"}</td>
                    <td className="px-4 py-3">{row.project_name ?? "-"}</td>
                    <td className="px-4 py-3">{row.project_location ?? "-"}</td>
                    <td className="px-4 py-3"><span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium capitalize text-blue-600">{String(row.status ?? "enrolled").replaceAll("_", " ")}</span></td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-2">
                        <Link href={`/beneficiaries/${row.id}`} className="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-slate-600 hover:bg-slate-50" title="View beneficiary"><Eye className="h-4 w-4" /></Link>
                        <Link href={beneficiaries.edit(row.id).url} className="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-orange-600 hover:bg-orange-50" title="Edit beneficiary"><Pencil className="h-4 w-4" /></Link>
                        <button type="button" className="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-red-600 hover:bg-red-50" title="Delete beneficiary" onClick={() => {
                          setBeneficiaryToDelete(row);
                          setDeleteOpen(true);
                        }}><Trash2 className="h-4 w-4" /></button>
                      </div>
                    </td>
                  </tr>
                )) : (
                  <tr>
                    <td colSpan={7} className="px-4 py-6 text-center text-slate-500">No beneficiaries available.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </section>

        {beneficiaryToDelete && (
          <ConfirmDeleteModal open={deleteOpen} onOpenChange={setDeleteOpen} title="Delete Beneficiary" submitRoute={beneficiaries.destroy} routeParams={beneficiaryToDelete.id} />
        )}

        <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Import Beneficiaries</DialogTitle>
              <DialogDescription>
                Import into the selected project iteration. Required spreadsheet headers: <code>name</code> and <code>surname</code>.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4">
              <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                <div className="font-medium">Target project</div>
                <div className="text-muted-foreground">{selectedProjectSummary?.name ?? "No project selected"}</div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="beneficiary-import-location">Project location</Label>
                <select id="beneficiary-import-location" className="w-full rounded-md border bg-card px-3 py-2 text-sm" value={importLocationId} onChange={(e) => setImportLocationId(e.target.value)}>
                  {selectedProjectLocations.map((location) => <option key={location.id} value={location.id}>{location.name}</option>)}
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="beneficiary-import-file">Spreadsheet file</Label>
                <Input id="beneficiary-import-file" ref={fileInputRef} type="file" accept=".csv,.txt,.xlsx" />
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setImportDialogOpen(false)}>Cancel</Button>
              <Button type="button" onClick={submitImport} disabled={!selectedProject || !importLocationId}>Import Spreadsheet</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AppLayout>
  );
}
