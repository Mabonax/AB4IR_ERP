import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";

import { MilestoneTemplateModelFormConfig } from "@/config/forms/milestone-template-model-form";
import { MilestoneTemplateTableConfig } from "@/config/tables/milestone-template-table";

import milestoneTemplates from "@/routes/milestone-templates";
import { type BreadcrumbItem } from "@/types";

export default function MilestoneTemplatesProgram({
  program,
  templates,
}: {
  program: { id: number; title: string };
  templates: { data: any[] };
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Projects", href: "/projects" },
    { title: "Milestone Templates", href: "/milestone-templates" },
    { title: program.title, href: "#" },
  ];

  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedTemplate, setSelectedTemplate] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [templateToDelete, setTemplateToDelete] = useState<any | null>(null);

  const mappedTemplateData = selectedTemplate
    ? {
        program_id: String(program.id),
        title: selectedTemplate.title ?? "",
        description: selectedTemplate.description ?? "",
        sort_order: selectedTemplate.sort_order ?? 0,
        max_score: selectedTemplate.max_score ?? "",
      }
    : { program_id: String(program.id) };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Milestone Templates - ${program.title}`} />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <div className="text-sm text-muted-foreground">Program</div>
            <h1 className="text-xl font-semibold">{program.title}</h1>
          </div>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <CustomModelForm
            addButton={MilestoneTemplateModelFormConfig.addButton}
            title="Add Milestone Template"
            description={MilestoneTemplateModelFormConfig.description}
            fields={MilestoneTemplateModelFormConfig.fields}
            submitRoute={milestoneTemplates.store}
            options={{ programs: [program] }}
            initialData={{ program_id: String(program.id) }}
            keepOpenOnSuccess
            preserveOnSuccessFields={["program_id"]}
          />
        </div>

        <CustomTable
          columns={MilestoneTemplateTableConfig.columns}
          data={templates.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedTemplate(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedTemplate(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setTemplateToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedTemplate && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Template Details" : "Edit Template"}
            fields={MilestoneTemplateModelFormConfig.fields}
            mode={mode}
            initialData={mappedTemplateData}
            submitRoute={milestoneTemplates.update}
            routeParams={selectedTemplate.id}
            options={{ programs: [program] }}
          />
        )}

        {templateToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Template"
            submitRoute={milestoneTemplates.destroy}
            routeParams={templateToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
