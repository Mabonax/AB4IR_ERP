import { Head } from "@inertiajs/react";
import { useState } from "react";

import {
} from "@/components/charts/dashboard-charts";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { programNavItems } from "@/config/domain-nav/programs";
import { ProgramModelFormConfig } from "@/config/forms/program-model-form";
import { ProgramTableConfig } from "@/config/tables/program-table";
import AppLayout from "@/layouts/app-layout";
import programsRoutes from "@/routes/programs";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Programs", href: "/programs" },
];

type ProgramPortfolioRow = {
  id: number;
  title: string;
  code?: string | null;
  description?: string | null;
  strategic_objective?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  status?: string | null;
  budget?: number | null;
  funding_source?: string | null;
  responsible_committee_id?: number | null;
  responsible_committee_name?: string | null;
  programme_manager_id?: number | null;
  programme_manager_name?: string | null;
  slug?: string | null;
  projects_count: number;
  active_projects: number;
  completed_projects: number;
  total_locations: number;
  unique_beneficiaries: number;
  tracked_beneficiaries: number;
  active_years: number;
  average_milestone_completion_rate: number;
  average_beneficiary_completion_rate: number;
  average_attendance_rate: number;
  blocked_locations: number;
};

export default function ProgramsDashboard({
  stats,
  programs,
  committees,
  staffMembers,
}: {
  stats: Record<string, number>;
  programs: ProgramPortfolioRow[];
  committees: Array<{ id: number; name: string }>;
  staffMembers: Array<{ id: number; name: string }>;
}) {
  const [open, setOpen] = useState(false);
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
      <Head title="Programs Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Programs Dashboard</h1>
            <p className="text-sm text-muted-foreground">
              High-level program inventory. Choose a program first, then drill into its iterations, locations,
              beneficiaries, attendance, and delivery performance.
            </p>
          </div>
          <div className="flex items-center gap-3">
            <CustomModelForm
              addButton={ProgramModelFormConfig.addButton}
              title="Add Program"
              description={ProgramModelFormConfig.description}
              fields={ProgramModelFormConfig.fields}
              submitRoute={programsRoutes.store}
              options={{ committees, staffMembers }}
            />
            <DomainNav items={programNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Programs</CardTitle>
              <CardDescription>Total registered programs</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.tracked_programs ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Programs with Projects</CardTitle>
              <CardDescription>Ready for program review</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {programs.filter((programRow) => programRow.projects_count > 0).length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Programs Without Projects</CardTitle>
              <CardDescription>Need setup or assignment</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {programs.filter((programRow) => programRow.projects_count === 0).length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Linked Projects</CardTitle>
              <CardDescription>Total iterations across programs</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {(stats.active_projects ?? 0) + (stats.completed_projects ?? 0)}
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Programs</CardTitle>
            <CardDescription>
              Manage programs here, then open a specific program to see its yearly iterations and delivery details.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={ProgramTableConfig.columns}
              data={programs}
              actions={[
                {
                  icon: "Eye",
                  href: (row) => programsRoutes.show(row.id).url,
                },
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedProgram(row);
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
          </CardContent>
        </Card>

        {selectedProgram && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Program"
            fields={ProgramModelFormConfig.fields}
            mode="edit"
            initialData={mappedProgramData}
            submitRoute={programsRoutes.update}
            routeParams={selectedProgram.id}
            options={{ committees, staffMembers }}
          />
        )}

        {programToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Program"
            submitRoute={programsRoutes.destroy}
            routeParams={programToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
