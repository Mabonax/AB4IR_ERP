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
          columns={ProjectTableConfig.columns}
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
          />
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
