import { Head, router } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Attendance Summary", href: "/projects/attendance-summary" },
];

export default function AttendanceSummary({
  projects,
  selectedProjectId,
  summary,
}: {
  projects: Array<{ id: number; name: string; start_date: string | null; end_date: string | null }>;
  selectedProjectId: number | null;
  summary: null | {
    project: { id: number; name: string; start_date: string | null; end_date: string | null };
    locations: Array<{
      location_id: number;
      location: string | null;
      facilitator: string | null;
      register_days: number;
      holidays: number;
      present: number;
      absent: number;
      excused: number;
      total_entries: number;
      attendance_rate: number;
    }>;
    overall: {
      register_days: number;
      holidays: number;
      present: number;
      absent: number;
      excused: number;
      total_entries: number;
      attendance_rate: number;
    };
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Attendance Summary" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Project Attendance Summary</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="rounded-xl border bg-card p-4 shadow-sm">
          <label className="mb-1 block text-sm font-medium">Project</label>
          <select
            className="w-full rounded-md border bg-card px-3 py-2 text-sm text-foreground"
            value={selectedProjectId ?? ""}
            onChange={(e) => {
              const projectId = e.target.value ? Number(e.target.value) : "";
              router.get(
                "/projects/attendance-summary",
                projectId ? { project_id: projectId } : {},
                { preserveScroll: true }
              );
            }}
          >
            <option value="">Select project</option>
            {projects.map((project) => (
              <option key={project.id} value={project.id}>
                {project.name} ({project.start_date ?? "-"} to {project.end_date ?? "ongoing"})
              </option>
            ))}
          </select>
        </div>

        {summary && (
          <>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div className="rounded-xl border bg-card p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Register Days</div>
                <div className="text-2xl font-semibold">{summary.overall.register_days}</div>
              </div>
              <div className="rounded-xl border bg-card p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Holidays</div>
                <div className="text-2xl font-semibold">{summary.overall.holidays}</div>
              </div>
              <div className="rounded-xl border bg-card p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Total Entries</div>
                <div className="text-2xl font-semibold">{summary.overall.total_entries}</div>
              </div>
              <div className="rounded-xl border bg-card p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Attendance Rate</div>
                <div className="text-2xl font-semibold">{summary.overall.attendance_rate}%</div>
              </div>
            </div>

            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-lg font-semibold">Per Location</h2>
              <div className="mt-4 overflow-x-auto">
                <table className="min-w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="px-3 py-2 text-left">Location</th>
                      <th className="px-3 py-2 text-left">Facilitator</th>
                      <th className="px-3 py-2 text-left">Register Days</th>
                      <th className="px-3 py-2 text-left">Holidays</th>
                      <th className="px-3 py-2 text-left">Present</th>
                      <th className="px-3 py-2 text-left">Absent</th>
                      <th className="px-3 py-2 text-left">Excused</th>
                      <th className="px-3 py-2 text-left">Rate</th>
                      <th className="px-3 py-2 text-left">View</th>
                    </tr>
                  </thead>
                  <tbody>
                    {summary.locations.map((row) => (
                      <tr key={row.location_id} className="border-b">
                        <td className="px-3 py-2">{row.location ?? "-"}</td>
                        <td className="px-3 py-2">{row.facilitator ?? "-"}</td>
                        <td className="px-3 py-2">{row.register_days}</td>
                        <td className="px-3 py-2">{row.holidays}</td>
                        <td className="px-3 py-2">{row.present}</td>
                        <td className="px-3 py-2">{row.absent}</td>
                        <td className="px-3 py-2">{row.excused}</td>
                        <td className="px-3 py-2">{row.attendance_rate}%</td>
                        <td className="px-3 py-2">
                          <button
                            type="button"
                            className="rounded-md border px-2 py-1 text-sm hover:bg-accent"
                            onClick={() => {
                              router.visit(`/project-locations/${row.location_id}/attendance`);
                            }}
                          >
                            Open Register
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </>
        )}
      </div>
    </AppLayout>
  );
}
