import { Head, Link, router } from "@inertiajs/react";

import {
  ComparisonBarsChart,
  HorizontalBarChart,
} from "@/components/charts/dashboard-charts";
import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
];

export default function ProjectsDashboard({
  stats,
  portfolio,
  canManageProjects,
}: {
  stats: {
    totalProjects: number;
    activeProjects: number;
    completedProjects: number;
    totalBeneficiaries: number;
    totalLocations: number;
  };
  portfolio: {
    projects: Array<{
      id: number;
      name: string;
      status: string;
      project_manager_name: string | null;
      total_locations: number;
      active_beneficiaries: number;
      milestone_completion_rate: number;
      beneficiary_completion_rate: number;
      attendance_rate: number;
      blocked_locations: number;
    }>;
    stats: {
      tracked_projects: number;
      average_milestone_completion_rate: number;
      average_beneficiary_completion_rate: number;
      average_attendance_rate: number;
      blocked_locations: number;
    };
  };
  canManageProjects: boolean;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Projects Dashboard" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex flex-wrap items-center gap-3">
            <h1 className="text-xl font-semibold">Projects Dashboard</h1>
            <DomainNav items={projectNavItems} />
          </div>
          {canManageProjects ? (
            <Button
              type="button"
              className="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
              onClick={() => router.visit("/projects/create")}
            >
              Create Project
            </Button>
          ) : null}
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Total Projects</CardTitle>
              <CardDescription>All projects</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalProjects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active</CardTitle>
              <CardDescription>In progress</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.activeProjects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Completed</CardTitle>
              <CardDescription>Finished</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.completedProjects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Beneficiaries</CardTitle>
              <CardDescription>Enrolled</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalBeneficiaries}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Active sites</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalLocations}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Tracked Projects</CardTitle>
              <CardDescription>Portfolio in progress view</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {portfolio.stats.tracked_projects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Avg Milestone Delivery</CardTitle>
              <CardDescription>Across tracked projects</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {portfolio.stats.average_milestone_completion_rate}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Avg Beneficiary Completion</CardTitle>
              <CardDescription>Across tracked projects</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {portfolio.stats.average_beneficiary_completion_rate}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Blocked Locations</CardTitle>
              <CardDescription>Sites needing intervention</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {portfolio.stats.blocked_locations}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6 xl:grid-cols-[1.65fr,1fr]">
          <ComparisonBarsChart
            title="Portfolio Delivery Comparison"
            description="Visual comparison of milestone delivery, beneficiary completion, and attendance rate across tracked projects."
            rows={portfolio.projects}
            rowLabel={(project) => project.name}
            metrics={[
              {
                label: "Milestones",
                colorClass: "bg-red-500",
                value: (project) => project.milestone_completion_rate,
              },
              {
                label: "Completion",
                colorClass: "bg-amber-500",
                value: (project) => project.beneficiary_completion_rate,
              },
              {
                label: "Attendance",
                colorClass: "bg-sky-500",
                value: (project) => project.attendance_rate,
              },
            ]}
            emptyMessage="No project comparison data is available yet."
            maxRows={8}
          />

          <HorizontalBarChart
            title="Blocked Locations by Project"
            description="Projects with the highest number of blocked delivery sites should usually be reviewed first."
            items={portfolio.projects
              .filter((project) => project.blocked_locations > 0)
              .sort((a, b) => b.blocked_locations - a.blocked_locations)
              .slice(0, 8)
              .map((project) => ({
                label: project.name,
                value: project.blocked_locations,
                hint: `${project.project_manager_name ?? "No PM"} • ${project.attendance_rate}% attendance`,
                colorClass: "bg-amber-500",
              }))}
            emptyMessage="No blocked project locations are currently flagged."
          />
        </div>

        <div className="grid gap-6 xl:grid-cols-[2fr,1fr]">
          <Card>
            <CardHeader>
              <CardTitle>Project Portfolio</CardTitle>
              <CardDescription>
                Cross-site project progress and delivery health
              </CardDescription>
            </CardHeader>
            <CardContent>
              {portfolio.projects.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No projects are available for portfolio tracking yet.
                </p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="min-w-full text-sm">
                    <thead className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                      <tr>
                        <th className="px-3 py-2 font-medium">Project</th>
                        <th className="px-3 py-2 font-medium">Manager</th>
                        <th className="px-3 py-2 font-medium">Locations</th>
                        <th className="px-3 py-2 font-medium">Active beneficiaries</th>
                        <th className="px-3 py-2 font-medium">Milestones</th>
                        <th className="px-3 py-2 font-medium">Completion</th>
                        <th className="px-3 py-2 font-medium">Attendance</th>
                        <th className="px-3 py-2 font-medium">Blocked sites</th>
                        <th className="px-3 py-2 font-medium">View</th>
                      </tr>
                    </thead>
                    <tbody>
                      {portfolio.projects.map((project) => (
                        <tr key={project.id} className="border-b last:border-b-0">
                          <td className="px-3 py-3">
                            <div className="font-medium text-slate-900">{project.name}</div>
                            <div className="text-xs capitalize text-muted-foreground">
                              {project.status}
                            </div>
                          </td>
                          <td className="px-3 py-3">{project.project_manager_name ?? "-"}</td>
                          <td className="px-3 py-3">{project.total_locations}</td>
                          <td className="px-3 py-3">{project.active_beneficiaries}</td>
                          <td className="px-3 py-3">{project.milestone_completion_rate}%</td>
                          <td className="px-3 py-3">{project.beneficiary_completion_rate}%</td>
                          <td className="px-3 py-3">{project.attendance_rate}%</td>
                          <td className="px-3 py-3">
                            <span
                              className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                                project.blocked_locations > 0
                                  ? "bg-amber-100 text-amber-800"
                                  : "bg-emerald-100 text-emerald-800"
                              }`}
                            >
                              {project.blocked_locations}
                            </span>
                          </td>
                          <td className="px-3 py-3">
                            <Link
                              href={`/projects/${project.id}`}
                              className="text-sm font-medium text-red-700 hover:text-red-800"
                            >
                              View
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

          <Card>
            <CardHeader>
              <CardTitle>Intervention Focus</CardTitle>
              <CardDescription>Where project managers should intervene first</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              {portfolio.projects.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  Portfolio insights will appear once projects have active delivery data.
                </p>
              ) : (
                portfolio.projects
                  .filter((project) => project.blocked_locations > 0 || project.attendance_rate < 100)
                  .sort((a, b) => b.blocked_locations - a.blocked_locations || a.attendance_rate - b.attendance_rate)
                  .slice(0, 5)
                  .map((project) => (
                    <div key={project.id} className="rounded-lg border p-3">
                      <div className="font-medium text-slate-900">{project.name}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        PM: {project.project_manager_name ?? "-"}
                      </div>
                      <div className="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <div>
                          <div className="text-muted-foreground">Blocked sites</div>
                          <div className="font-semibold">{project.blocked_locations}</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Attendance</div>
                          <div className="font-semibold">{project.attendance_rate}%</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Milestones</div>
                          <div className="font-semibold">{project.milestone_completion_rate}%</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Completion</div>
                          <div className="font-semibold">{project.beneficiary_completion_rate}%</div>
                        </div>
                      </div>
                    </div>
                  ))
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
