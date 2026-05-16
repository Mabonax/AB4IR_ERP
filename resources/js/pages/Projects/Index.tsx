import { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import { Button } from "@/components/ui/button";

import { ProjectModelFormConfig } from "@/config/forms/project-model-form";
import { ProjectTableConfig } from "@/config/tables/project-table";

import projects from "@/routes/projects";
import { type BreadcrumbItem } from "@/types";

type StatusTransition = {
  status: string;
  label: string;
  ready: boolean;
  blockers: string[];
};

type StatusSummary = {
  current: string;
  current_label: string;
  allowed_transitions: StatusTransition[];
  readiness: {
    active: { ready: boolean; blockers: string[] };
    completed: { ready: boolean; blockers: string[] };
  };
};

const statusTone = (ready: boolean) =>
  ready
    ? "border-emerald-200 bg-emerald-50 text-emerald-700"
    : "border-amber-200 bg-amber-50 text-amber-700";

function ProjectStatusPanel({ summary }: { summary?: StatusSummary | null }) {
  if (!summary) return null;

  return (
    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
      <div className="flex flex-wrap items-center gap-2">
        <span className="font-semibold text-slate-900">Current Status</span>
        <span className="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700">
          {summary.current_label}
        </span>
      </div>

      <div className="mt-4 space-y-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Allowed transitions
          </p>
          {summary.allowed_transitions.length === 0 ? (
            <p className="mt-2 text-slate-600">No further transitions are allowed.</p>
          ) : (
            <div className="mt-2 flex flex-wrap gap-2">
              {summary.allowed_transitions.map((transition) => (
                <span
                  key={transition.status}
                  className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusTone(transition.ready)}`}
                >
                  {transition.label}
                  {!transition.ready ? ` (${transition.blockers.length} blocker${transition.blockers.length === 1 ? "" : "s"})` : ""}
                </span>
              ))}
            </div>
          )}
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          {(["active", "completed"] as const).map((statusKey) => {
            const readiness = summary.readiness[statusKey];

            return (
              <div
                key={statusKey}
                className={`rounded-lg border p-3 ${statusTone(readiness.ready)}`}
              >
                <p className="text-xs font-semibold uppercase tracking-wide">
                  {statusKey === "active" ? "Activation readiness" : "Completion readiness"}
                </p>
                <p className="mt-1 text-xs font-medium">
                  {readiness.ready ? "Ready" : `${readiness.blockers.length} blocker${readiness.blockers.length === 1 ? "" : "s"}`}
                </p>
                {!readiness.ready && (
                  <ul className="mt-2 space-y-1 text-xs">
                    {readiness.blockers.map((blocker) => (
                      <li key={blocker}>{blocker}</li>
                    ))}
                  </ul>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "List", href: "/projects/list" },
];

/* =========================================================
| PAGE
========================================================= */

export default function ProjectIndex({
  projects: projectPagination,
  programs,
  stakeholders,
  staffMembers,
}: {
  projects: { data: any[] };
  programs: { id: number; title: string }[];
  stakeholders: { id: number; name: string }[];
  staffMembers: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedProject, setSelectedProject] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [projectToDelete, setProjectToDelete] = useState<any | null>(null);

  const mappedProjectData = selectedProject
    ? {
        name: selectedProject.name ?? "",
        description: selectedProject.description ?? "",
        start_date: selectedProject.start_date ?? "",
        end_date: selectedProject.end_date ?? "",
        status: selectedProject.status ?? "planned",
        program_id:
          selectedProject.program_id !== null &&
          selectedProject.program_id !== undefined
            ? String(selectedProject.program_id)
            : "",
        sponsor_stakeholder_id:
          selectedProject.sponsor_stakeholder_id !== null &&
          selectedProject.sponsor_stakeholder_id !== undefined
            ? String(selectedProject.sponsor_stakeholder_id)
            : "",
        project_manager_id:
          selectedProject.project_manager_id !== null &&
          selectedProject.project_manager_id !== undefined
            ? String(selectedProject.project_manager_id)
            : "",
      }
    : {};

  const columns = ProjectTableConfig.columns.map((column) =>
    column.key === "status"
      ? {
          ...column,
          render: (row: any) => {
            const blockers = row.status_summary?.allowed_transitions?.filter(
              (transition: StatusTransition) => !transition.ready
            ).length ?? 0;

            return (
              <div className="space-y-1">
                <div className="font-medium text-slate-900">
                  {row.status_label ?? row.status ?? "-"}
                </div>
                {blockers > 0 && (
                  <div className="text-xs text-amber-700">
                    {blockers} blocked transition{blockers === 1 ? "" : "s"}
                  </div>
                )}
              </div>
            );
          },
        }
      : column
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Projects" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Projects</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-wrap items-center gap-2">
            <Button
              asChild
              variant="outline"
              className="border-orange-500 text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              <Link href="/project-locations">Project Locations</Link>
            </Button>
            <Button
              asChild
              variant="outline"
              className="border-orange-500 text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              <Link href="/project-enrollments">Project Enrollments</Link>
            </Button>
            <Button
              asChild
              variant="outline"
              className="border-orange-500 text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              <Link href="/milestone-templates">Milestone Templates</Link>
            </Button>
          </div>

          <CustomModelForm
            addButton={ProjectModelFormConfig.addButton}
            title="Add Project"
            description={ProjectModelFormConfig.description}
            fields={ProjectModelFormConfig.fields}
            submitRoute={projects.store}
            options={{ programs, stakeholders, staffMembers }}
          />
        </div>

        <CustomTable
          columns={columns}
          data={projectPagination.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                router.visit(`/projects/${row.id}`);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedProject(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setProjectToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedProject && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Project Details" : "Edit Project"}
            fields={ProjectModelFormConfig.fields}
            mode={mode}
            initialData={mappedProjectData}
            submitRoute={projects.update}
            routeParams={selectedProject.id}
            options={{ programs, stakeholders, staffMembers }}
          >
            <ProjectStatusPanel summary={selectedProject.status_summary} />
          </CustomModelForm>
        )}

        {projectToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Project"
            submitRoute={projects.destroy}
            routeParams={projectToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
