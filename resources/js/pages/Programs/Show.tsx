import { Head, Link } from "@inertiajs/react";

import {
  HorizontalBarChart,
} from "@/components/charts/dashboard-charts";
import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { programNavItems } from "@/config/domain-nav/programs";
import AppLayout from "@/layouts/app-layout";
import programs from "@/routes/programs";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Programs", href: programs.index().url },
  { title: "Program Overview", href: "#" },
];

type ProgramPayload = {
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
  responsible_committee_name?: string | null;
  programme_manager_name?: string | null;
  slug?: string | null;
};

type ProgramProject = {
  id: number;
  name: string;
  status: string;
  year: string;
  period_label: string;
  start_date?: string | null;
  end_date?: string | null;
  description?: string | null;
  project_manager_name?: string | null;
  sponsor_name?: string | null;
  total_locations: number;
  total_beneficiaries: number;
  active_beneficiaries: number;
  completed_beneficiaries: number;
  dropped_beneficiaries: number;
  milestone_completion_rate: number;
  beneficiary_completion_rate: number;
  attendance_rate: number;
  blocked_locations: number;
  registers_captured: number;
  blockers: string[];
};

type YearlyImpact = {
  year: string;
  projects: number;
  beneficiaries: number;
  active_beneficiaries: number;
  locations: number;
  completed_projects: number;
};

export default function ProgramShow({
  program,
  stats,
  yearlyImpact,
  projects,
}: {
  program: { data: ProgramPayload } | ProgramPayload;
  stats: Record<string, number>;
  yearlyImpact: YearlyImpact[];
  projects: ProgramProject[];
}) {
  const programData = (program as { data?: ProgramPayload }).data ?? (program as ProgramPayload);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`${programData.title} Overview`} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{programData.title}</h1>
            <p className="text-sm text-muted-foreground">
              Program overview and iteration selector. Open a specific iteration to see beneficiaries, locations,
              attendance, milestones, and delivery performance for that cohort.
            </p>
          </div>
          <div className="flex items-center gap-3">
            <Link
              href={programs.index().url}
              className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Back to Programs
            </Link>
            <DomainNav items={programNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Iterations</CardTitle>
              <CardDescription>Associated executions</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total_projects ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Unique Beneficiaries</CardTitle>
              <CardDescription>People reached</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.unique_beneficiaries ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Tracked Beneficiaries</CardTitle>
              <CardDescription>Enrollments across iterations</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.tracked_beneficiaries ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Delivery sites</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total_locations ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Years</CardTitle>
              <CardDescription>Program iterations</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.active_years ?? 0}</CardContent>
          </Card>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Active Iterations</CardTitle>
              <CardDescription>Currently running</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.active_projects ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Completed Iterations</CardTitle>
              <CardDescription>Closed iterations</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.completed_projects ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Milestone Delivery</CardTitle>
              <CardDescription>Average completion rate</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.average_milestone_completion_rate ?? 0}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Attendance Health</CardTitle>
              <CardDescription>Average attendance rate</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.average_attendance_rate ?? 0}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Blocked Locations</CardTitle>
              <CardDescription>Need intervention</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.blocked_locations ?? 0}</CardContent>
          </Card>
        </div>

        <div className="grid gap-6 xl:grid-cols-[1.2fr,1fr]">
          <HorizontalBarChart
            title="Iteration Reach"
            description="Ranks program iterations by total tracked beneficiaries."
            items={projects
              .map((projectRow) => ({
                label: projectRow.name,
                value: projectRow.total_beneficiaries,
                hint: `${projectRow.year} | ${projectRow.total_locations} location${projectRow.total_locations === 1 ? "" : "s"}`,
                colorClass: projectRow.blocked_locations > 0 ? "bg-amber-500" : "bg-emerald-500",
              }))
              .sort((a, b) => b.value - a.value)}
            emptyMessage="No associated iteration reach data is available yet."
          />
          <Card>
            <CardHeader>
              <CardTitle>Program Overview</CardTitle>
              <CardDescription>Core definition and execution footprint</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Programme Code</div>
                <div className="mt-1 font-medium text-slate-900">{programData.code ?? "-"}</div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</div>
                <div className="mt-1 font-medium capitalize text-slate-900">{programData.status ?? "-"}</div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Slug</div>
                <div className="mt-1 font-medium text-slate-900">{programData.slug ?? "-"}</div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Description</div>
                <div className="mt-1 whitespace-pre-wrap text-slate-700">
                  {programData.description ?? "No program description has been recorded yet."}
                </div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Strategic Objective</div>
                <div className="mt-1 whitespace-pre-wrap text-slate-700">
                  {programData.strategic_objective ?? "No strategic objective has been recorded yet."}
                </div>
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Programme manager
                  </div>
                  <div className="mt-1 font-medium text-slate-900">{programData.programme_manager_name ?? "-"}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Responsible committee
                  </div>
                  <div className="mt-1 font-medium text-slate-900">{programData.responsible_committee_name ?? "-"}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Programme period
                  </div>
                  <div className="mt-1 font-medium text-slate-900">
                    {programData.start_date ?? "-"} to {programData.end_date ?? "-"}
                  </div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Budget
                  </div>
                  <div className="mt-1 font-medium text-slate-900">
                    {programData.budget !== null && programData.budget !== undefined ? Number(programData.budget).toLocaleString() : "-"}
                  </div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Funding source
                  </div>
                  <div className="mt-1 font-medium text-slate-900">{programData.funding_source ?? "-"}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Milestone templates
                  </div>
                  <div className="mt-1 font-medium text-slate-900">{stats.milestone_templates_count ?? 0}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Average beneficiary completion
                  </div>
                  <div className="mt-1 font-medium text-slate-900">
                    {stats.average_beneficiary_completion_rate ?? 0}%
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Program Iterations</CardTitle>
            <CardDescription>Click an iteration to open its dashboard with beneficiaries, locations, attendance, milestones, and all related delivery detail.</CardDescription>
          </CardHeader>
          <CardContent>
            {projects.length === 0 ? (
              <p className="text-sm text-muted-foreground">No iterations have been linked to this program yet.</p>
            ) : (
              <div className="overflow-hidden rounded-lg border">
                <table className="min-w-full divide-y divide-slate-200">
                  <thead className="bg-slate-50">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Iteration</th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Year</th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Beneficiaries</th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Locations</th>
                      <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Open</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white">
                    {projects.map((projectRow) => (
                      <tr key={projectRow.id} className="hover:bg-slate-50">
                        <td className="px-4 py-3">
                          <div className="font-medium text-slate-900">{projectRow.name}</div>
                          <div className="text-xs text-muted-foreground">{projectRow.period_label}</div>
                        </td>
                        <td className="px-4 py-3 text-sm text-slate-700">{projectRow.year}</td>
                        <td className="px-4 py-3 text-sm text-slate-700">{projectRow.status}</td>
                        <td className="px-4 py-3 text-sm text-slate-700">{projectRow.total_beneficiaries}</td>
                        <td className="px-4 py-3 text-sm text-slate-700">{projectRow.total_locations}</td>
                        <td className="px-4 py-3">
                          <Link
                            href={`/projects/${projectRow.id}`}
                            className="text-sm font-semibold text-red-700 hover:text-red-800"
                          >
                            Open iteration
                          </Link>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
