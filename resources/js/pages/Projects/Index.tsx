import { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import { Button } from "@/components/ui/button";
import { ProjectTableConfig } from "@/config/tables/project-table";
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
  partnerStakeholders,
  staffMembers,
  canManageProjects,
}: {
  projects: { data: any[] };
  programs: { id: number; title: string }[];
  stakeholders: { id: number; name: string }[];
  partnerStakeholders: { id: number; name: string }[];
  staffMembers: { id: number; name: string }[];
  canManageProjects: boolean;
}) {
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [projectToDelete, setProjectToDelete] = useState<any | null>(null);

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

          {canManageProjects ? (
            <Button asChild className="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
              <Link href="/projects/create">Add Project</Link>
            </Button>
          ) : null}
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
            ...(canManageProjects
              ? [
                  {
                    icon: "PencilIcon",
                    onClick: (row: any) => {
                      router.visit(`/projects/${row.id}/edit`);
                    },
                  },
                  {
                    icon: "Trash2",
                    variant: "danger" as const,
                    onClick: (row: any) => {
                      setProjectToDelete(row);
                      setDeleteOpen(true);
                    },
                  },
                ]
              : []),
          ]}
        />

        {projectToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Project"
            submitRoute={(id: number | string) => ({ url: `/projects/${id}`, method: "delete" as const })}
            routeParams={projectToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
