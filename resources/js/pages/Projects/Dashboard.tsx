import { Head, Link, router } from "@inertiajs/react";
import {
  AlertTriangle,
  CalendarDays,
  CheckCircle2,
  ChevronRight,
  CircleAlert,
  Download,
  Folder,
  Gauge,
  MapPin,
  MoreVertical,
  Plus,
  Search,
  ShieldCheck,
  Target,
  Users,
  Zap,
} from "lucide-react";
import { type ReactNode, useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Projects", href: "/projects" }];

type PortfolioProject = {
  id: number;
  name: string;
  status: string;
  status_label?: string;
  program_title?: string | null;
  project_manager_name: string | null;
  total_locations: number;
  active_beneficiaries: number;
  total_milestones?: number;
  milestone_completion_rate: number;
  beneficiary_completion_rate: number;
  attendance_rate: number;
  blocked_locations: number;
};

type DashboardProps = {
  stats: {
    totalProjects: number;
    activeProjects: number;
    completedProjects: number;
    totalBeneficiaries: number;
    totalLocations: number;
  };
  portfolio: {
    projects: PortfolioProject[];
    stats: {
      tracked_projects: number;
      average_milestone_completion_rate: number;
      average_beneficiary_completion_rate: number;
      average_attendance_rate: number;
      blocked_locations: number;
    };
  };
  canManageProjects: boolean;
};

const statusOrder = ["active", "planned", "completed", "at_risk", "blocked"];
const statusLabels: Record<string, string> = {
  active: "Active",
  planned: "Planned",
  completed: "Completed",
  on_hold: "At Risk",
  at_risk: "At Risk",
  blocked: "Blocked",
};
const statusColors: Record<string, string> = {
  active: "#10b981",
  planned: "#2f80ed",
  completed: "#34d399",
  on_hold: "#f97316",
  at_risk: "#f97316",
  blocked: "#ef233c",
};

function pct(value: number | undefined | null): string {
  return `${Math.round(Number(value ?? 0))}%`;
}

function projectHealth(project: PortfolioProject): "Attention" | "Good" {
  return project.blocked_locations > 0 ||
    project.attendance_rate < 75 ||
    project.milestone_completion_rate < 50
    ? "Attention"
    : "Good";
}

function KpiCard({
  icon,
  label,
  value,
  hint,
  tone,
}: {
  icon: ReactNode;
  label: string;
  value: number;
  hint: string;
  tone: string;
}) {
  return (
    <section className="flex min-h-[112px] items-center gap-5 rounded-lg border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-full ${tone}`}>
        {icon}
      </div>
      <div>
        <div className="text-sm font-semibold text-slate-950">{label}</div>
        <div className="mt-1 text-3xl font-bold text-slate-950">{value}</div>
        <div className="mt-2 text-sm text-slate-500">{hint}</div>
      </div>
    </section>
  );
}

function ProgressRow({ icon, label, value, tone }: { icon: ReactNode; label: string; value: number; tone: string }) {
  const width = Math.min(Math.max(value, 0), 100);

  return (
    <div className="grid grid-cols-[28px_1fr_44px] items-center gap-3">
      <div className={`flex h-7 w-7 items-center justify-center rounded-full ${tone}`}>{icon}</div>
      <div>
        <div className="text-sm font-semibold text-slate-950">{label}</div>
        <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
          <div className="h-full rounded-full bg-red-600" style={{ width: `${width}%` }} />
        </div>
      </div>
      <div className="text-right text-sm font-bold text-slate-950">{pct(value)}</div>
    </div>
  );
}

function SectionCard({ title, subtitle, children }: { title: string; subtitle: string; children: ReactNode }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div className="px-5 pb-2 pt-5">
        <h2 className="text-lg font-bold text-slate-950">{title}</h2>
        <p className="mt-1 text-sm text-slate-500">{subtitle}</p>
      </div>
      {children}
    </section>
  );
}

function DeliveryChart({ projects }: { projects: PortfolioProject[] }) {
  const rows = projects.slice(0, 4);
  const chartRows = rows.length ? rows : [{ id: 0, name: "No projects", milestone_completion_rate: 0, beneficiary_completion_rate: 0, attendance_rate: 0 } as PortfolioProject];

  return (
    <SectionCard title="Delivery Performance" subtitle="Portfolio performance comparison">
      <div className="px-6 pb-6 pt-3">
        <div className="mb-5 flex flex-wrap items-center gap-6 text-xs text-slate-600">
          <span className="inline-flex items-center gap-2"><span className="h-3 w-3 rounded bg-orange-600" /> Milestones</span>
          <span className="inline-flex items-center gap-2"><span className="h-3 w-3 rounded bg-emerald-500" /> Completion</span>
          <span className="inline-flex items-center gap-2"><span className="h-3 w-3 rounded bg-blue-500" /> Attendance</span>
        </div>
        <div className="relative h-44 border-b border-l border-slate-200 pl-1">
          {[100, 75, 50, 25, 0].map((tick) => (
            <div key={tick} className="absolute left-0 right-0 border-t border-dashed border-slate-200" style={{ top: `${100 - tick}%` }}>
              <span className="-ml-1 -translate-x-full -translate-y-2.5 text-xs text-slate-500">{tick}%</span>
            </div>
          ))}
          <div className="relative z-10 grid h-full items-end gap-6 px-14" style={{ gridTemplateColumns: `repeat(${Math.max(chartRows.length, 1)}, minmax(90px, 1fr))` }}>
            {chartRows.map((project) => (
              <div key={project.id} className="flex h-full flex-col justify-end gap-2">
                <div className="flex h-36 items-end justify-center gap-3">
                  {[
                    ["bg-orange-600", project.milestone_completion_rate],
                    ["bg-emerald-500", project.beneficiary_completion_rate],
                    ["bg-blue-500", project.attendance_rate],
                  ].map(([color, value], index) => (
                    <div key={index} className="flex w-8 flex-col items-center justify-end">
                      <span className="mb-1 text-xs font-semibold text-slate-700">{pct(Number(value))}</span>
                      <div className={`w-full rounded-t ${color}`} style={{ height: `${Math.max(Number(value), 1)}%` }} />
                    </div>
                  ))}
                </div>
                <div className="truncate text-center text-xs text-slate-600">{project.name}</div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </SectionCard>
  );
}

function StatusDonut({ projects }: { projects: PortfolioProject[] }) {
  const counts = statusOrder.map((status) => ({
    status,
    count: status === "at_risk"
      ? projects.filter((project) => projectHealth(project) === "Attention").length
      : projects.filter((project) => project.status === status).length,
  }));
  const total = Math.max(projects.length, 1);
  let cursor = 0;
  const slices = counts
    .filter((item) => item.count > 0)
    .map((item) => {
      const start = cursor;
      const end = cursor + (item.count / total) * 360;
      cursor = end;
      return `${statusColors[item.status]} ${start}deg ${end}deg`;
    });
  const background = slices.length ? `conic-gradient(${slices.join(", ")})` : "conic-gradient(#e5e7eb 0deg 360deg)";

  return (
    <SectionCard title="Project Status Overview" subtitle="Current status of all projects">
      <div className="grid gap-6 px-8 pb-7 pt-5 md:grid-cols-[240px_1fr]">
        <div className="flex justify-center">
          <div className="grid h-44 w-44 place-items-center rounded-full" style={{ background }}>
            <div className="grid h-24 w-24 place-items-center rounded-full bg-white text-center shadow-sm">
              <div>
                <div className="text-2xl font-bold text-slate-950">{projects.length}</div>
                <div className="text-xs text-slate-500">Total</div>
              </div>
            </div>
          </div>
        </div>
        <div className="space-y-4 self-center">
          {statusOrder.map((status) => {
            const count = counts.find((item) => item.status === status)?.count ?? 0;
            const percentage = projects.length ? Math.round((count / projects.length) * 100) : 0;

            return (
              <div key={status} className="flex items-center justify-between gap-4 text-sm">
                <span className="inline-flex items-center gap-3 text-slate-700">
                  <span className="h-3 w-3 rounded-full" style={{ backgroundColor: statusColors[status] }} />
                  {statusLabels[status]}
                </span>
                <span className="font-semibold text-slate-950">{count} ({percentage}%)</span>
              </div>
            );
          })}
        </div>
      </div>
    </SectionCard>
  );
}

export default function ProjectsDashboard({ stats, portfolio, canManageProjects }: DashboardProps) {
  const [search, setSearch] = useState("");
  const [program, setProgram] = useState("");
  const [status, setStatus] = useState("");
  const [manager, setManager] = useState("");
  const [health, setHealth] = useState("");

  const projects = portfolio.projects;
  const filteredProjects = useMemo(() => projects.filter((project) => {
    const matchesSearch = !search || project.name.toLowerCase().includes(search.toLowerCase());
    const matchesProgram = !program || (project.program_title ?? "Unassigned") === program;
    const matchesStatus = !status || project.status === status;
    const matchesManager = !manager || (project.project_manager_name ?? "Unassigned") === manager;
    const matchesHealth = !health || projectHealth(project) === health;

    return matchesSearch && matchesProgram && matchesStatus && matchesManager && matchesHealth;
  }), [health, manager, program, projects, search, status]);

  const programOptions = Array.from(new Set(projects.map((project) => project.program_title ?? "Unassigned"))).sort();
  const statusOptions = Array.from(new Set(projects.map((project) => project.status))).sort();
  const managerOptions = Array.from(new Set(projects.map((project) => project.project_manager_name ?? "Unassigned"))).sort();
  const projectsAtRisk = projects.filter((project) => projectHealth(project) === "Attention").length;
  const overdueMilestones = projects.filter((project) => (project.total_milestones ?? 0) > 0 && project.milestone_completion_rate < 100).length;
  const topPriorityProjects = projects
    .filter((project) => project.blocked_locations > 0 || project.attendance_rate < 80 || project.milestone_completion_rate < 80)
    .sort((a, b) => b.blocked_locations - a.blocked_locations || a.attendance_rate - b.attendance_rate)
    .slice(0, 3);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Projects Dashboard" />

      <div className="min-h-screen bg-white px-5 py-6 text-slate-950 lg:px-6">
        <header className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold">Projects Dashboard</h1>
            <p className="mt-2 text-base text-slate-600">Portfolio overview, delivery performance and project governance.</p>
          </div>
          <div className="flex items-center gap-3">
            {canManageProjects ? (
              <button
                type="button"
                onClick={() => router.visit("/projects/create")}
                className="inline-flex h-11 items-center gap-2 rounded-lg bg-red-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
              >
                <Plus className="h-4 w-4" />
                Create Project
              </button>
            ) : null}
            <button type="button" className="grid h-11 w-11 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm" aria-label="Project actions">
              <MoreVertical className="h-5 w-5" />
            </button>
          </div>
        </header>

        <div className="mt-7 border-b border-slate-200">
          <DomainNav items={projectNavItems} />
        </div>

        <section className="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
          <KpiCard icon={<Folder className="h-6 w-6 text-red-600" />} label="Total Projects" value={stats.totalProjects} hint="All projects" tone="bg-red-50" />
          <KpiCard icon={<Zap className="h-6 w-6 text-emerald-600" />} label="Active Projects" value={stats.activeProjects} hint="In progress" tone="bg-emerald-50" />
          <KpiCard icon={<ShieldCheck className="h-6 w-6 text-blue-600" />} label="Completed" value={stats.completedProjects} hint="Finished" tone="bg-blue-50" />
          <KpiCard icon={<Users className="h-6 w-6 text-orange-600" />} label="Beneficiaries" value={stats.totalBeneficiaries} hint="Enrolled" tone="bg-orange-50" />
          <KpiCard icon={<MapPin className="h-6 w-6 text-violet-600" />} label="Locations" value={stats.totalLocations} hint="Active sites" tone="bg-violet-50" />
        </section>

        <section className="mt-6 grid gap-5 xl:grid-cols-[1fr_1.06fr]">
          <SectionCard title="Portfolio Health" subtitle="Performance across your project portfolio">
            <div className="space-y-6 px-6 py-7">
              <ProgressRow icon={<Target className="h-4 w-4 text-orange-600" />} label="Milestone Delivery" value={portfolio.stats.average_milestone_completion_rate} tone="bg-orange-50" />
              <ProgressRow icon={<Users className="h-4 w-4 text-emerald-600" />} label="Beneficiary Completion" value={portfolio.stats.average_beneficiary_completion_rate} tone="bg-emerald-50" />
              <ProgressRow icon={<Gauge className="h-4 w-4 text-blue-600" />} label="Attendance Rate" value={portfolio.stats.average_attendance_rate} tone="bg-blue-50" />
              <ProgressRow icon={<MapPin className="h-4 w-4 text-red-600" />} label="Blocked Locations" value={portfolio.stats.blocked_locations} tone="bg-red-50" />
            </div>
            <div className="border-t border-slate-200 py-4 text-center">
              <Link href="/projects/attendance-summary" className="inline-flex items-center gap-2 text-sm font-semibold text-red-600">
                View full analytics <ChevronRight className="h-4 w-4" />
              </Link>
            </div>
          </SectionCard>

          <SectionCard title="Attention Required" subtitle="Areas that need your immediate attention">
            <div className="divide-y divide-slate-200 px-6">
              {[
                { label: "Blocked Locations", hint: `${portfolio.stats.blocked_locations} location needs intervention`, value: portfolio.stats.blocked_locations, Icon: CircleAlert },
                { label: "Projects At Risk", hint: "Projects below delivery threshold", value: projectsAtRisk, Icon: AlertTriangle },
                { label: "Overdue Milestones", hint: "Milestones past due date", value: overdueMilestones, Icon: CalendarDays },
              ].map(({ label, hint, value, Icon }) => (
                <div key={label} className="flex items-center gap-4 py-6">
                  <div className="grid h-10 w-10 place-items-center rounded-full bg-orange-50">
                    <Icon className="h-5 w-5 text-orange-600" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="font-semibold text-slate-950">{label}</div>
                    <div className="mt-1 text-sm text-slate-500">{hint}</div>
                  </div>
                  <span className="rounded-lg bg-red-50 px-3 py-2 text-sm font-bold text-red-600">{value}</span>
                </div>
              ))}
            </div>
            <div className="border-t border-slate-200 py-4 text-center">
              <a href="#interventions" className="inline-flex items-center gap-2 text-sm font-semibold text-red-600">
                View all interventions <ChevronRight className="h-4 w-4" />
              </a>
            </div>
          </SectionCard>
        </section>

        <section className="mt-6 grid gap-5 xl:grid-cols-[1fr_1.06fr]">
          <DeliveryChart projects={projects} />
          <StatusDonut projects={projects} />
        </section>

        <div className="mt-6">
          <SectionCard title="Project Portfolio" subtitle="Cross-site project progress and delivery health">
            <div className="px-5 pb-6 pt-4">
              <div className="mb-5 grid gap-3 lg:grid-cols-[minmax(220px,1fr)_140px_140px_150px_140px_auto]">
                <label className="relative">
                  <Search className="absolute left-3 top-3 h-4 w-4 text-slate-500" />
                  <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search projects..." className="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none focus:border-red-300" />
                </label>
                <select value={program} onChange={(event) => setProgram(event.target.value)} className="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                  <option value="">Program</option>
                  {programOptions.map((option) => <option key={option} value={option}>{option}</option>)}
                </select>
                <select value={status} onChange={(event) => setStatus(event.target.value)} className="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                  <option value="">Status</option>
                  {statusOptions.map((option) => <option key={option} value={option}>{statusLabels[option] ?? option}</option>)}
                </select>
                <select value={manager} onChange={(event) => setManager(event.target.value)} className="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                  <option value="">Manager</option>
                  {managerOptions.map((option) => <option key={option} value={option}>{option}</option>)}
                </select>
                <select value={health} onChange={(event) => setHealth(event.target.value)} className="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                  <option value="">Health</option>
                  <option value="Attention">Attention</option>
                  <option value="Good">Good</option>
                </select>
                <button type="button" className="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700">
                  <Download className="h-4 w-4" />
                  Export
                </button>
              </div>

              <div className="overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                  <thead className="border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                      <th className="px-2 py-3">Project</th>
                      <th className="px-2 py-3">Program</th>
                      <th className="px-2 py-3">Manager</th>
                      <th className="px-2 py-3">Locations</th>
                      <th className="px-2 py-3">Beneficiaries</th>
                      <th className="px-2 py-3">Milestones</th>
                      <th className="px-2 py-3">Attendance</th>
                      <th className="px-2 py-3">Health</th>
                      <th className="px-2 py-3">Status</th>
                      <th className="px-2 py-3">Action</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredProjects.map((project) => {
                      const healthLabel = projectHealth(project);

                      return (
                        <tr key={project.id}>
                          <td className="px-2 py-4">
                            <div className="font-semibold text-slate-950">{project.name}</div>
                            <div className="mt-1 inline-flex rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600">{statusLabels[project.status] ?? project.status}</div>
                          </td>
                          <td className="px-2 py-4">{project.program_title ?? "-"}</td>
                          <td className="px-2 py-4">{project.project_manager_name ?? "-"}</td>
                          <td className="px-2 py-4 font-semibold">{project.total_locations}</td>
                          <td className="px-2 py-4 font-semibold">{project.active_beneficiaries}</td>
                          <td className="px-2 py-4">
                            <div className="font-semibold">{pct(project.milestone_completion_rate)}</div>
                            <div className="mt-2 h-2 w-20 rounded-full bg-slate-100">
                              <div className="h-full rounded-full bg-slate-300" style={{ width: pct(project.milestone_completion_rate) }} />
                            </div>
                          </td>
                          <td className="px-2 py-4">
                            <div className="font-semibold">{pct(project.attendance_rate)}</div>
                            <div className="mt-2 h-2 w-20 rounded-full bg-slate-100">
                              <div className="h-full rounded-full bg-slate-300" style={{ width: pct(project.attendance_rate) }} />
                            </div>
                          </td>
                          <td className="px-2 py-4">
                            <span className={`inline-flex items-center gap-1.5 font-semibold ${healthLabel === "Attention" ? "text-orange-600" : "text-emerald-600"}`}>
                              {healthLabel === "Attention" ? <AlertTriangle className="h-4 w-4" /> : <CheckCircle2 className="h-4 w-4" />}
                              {healthLabel}
                            </span>
                          </td>
                          <td className="px-2 py-4">
                            <span className="rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600">{statusLabels[project.status] ?? project.status}</span>
                          </td>
                          <td className="px-2 py-4">
                            <Link href={`/projects/${project.id}`} className="inline-flex items-center gap-1 font-semibold text-red-600">
                              View <ChevronRight className="h-4 w-4" />
                            </Link>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              <div className="mt-5 flex items-center justify-between text-sm text-slate-500">
                <span>Showing {filteredProjects.length === 0 ? 0 : 1} to {filteredProjects.length} of {filteredProjects.length} projects</span>
                <div className="flex items-center gap-2">
                  <button className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200" type="button" aria-label="Previous page">{"<"}</button>
                  <button className="grid h-9 w-9 place-items-center rounded-lg bg-red-600 text-sm font-bold text-white" type="button">1</button>
                  <button className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200" type="button" aria-label="Next page">{">"}</button>
                </div>
              </div>
            </div>
          </SectionCard>
        </div>

        <section id="interventions" className="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
          <div className="px-5 pt-5">
            <h2 className="text-lg font-bold text-slate-950">Intervention Priorities</h2>
            <p className="mt-1 text-sm text-slate-500">Projects requiring immediate management attention</p>
          </div>
          <div className="space-y-3 px-5 py-6">
            {(topPriorityProjects.length ? topPriorityProjects : projects.slice(0, 1)).map((project) => (
              <div key={project.id} className="grid gap-4 rounded-lg border border-l-2 border-slate-200 border-l-red-600 bg-white p-5 shadow-sm md:grid-cols-[1.4fr_repeat(3,120px)_150px]">
                <div className="flex items-center gap-4">
                  <div className="grid h-10 w-10 place-items-center rounded-full bg-orange-50">
                    <CircleAlert className="h-5 w-5 text-orange-600" />
                  </div>
                  <div>
                    <div className="font-bold text-slate-950">{project.name}</div>
                    <div className="mt-1 text-sm text-slate-500">PM: {project.project_manager_name ?? "-"}</div>
                    <div className="mt-1 text-sm text-slate-500">Blocked location • {pct(project.attendance_rate)} attendance • {pct(project.milestone_completion_rate)} milestone delivery</div>
                  </div>
                </div>
                <div>
                  <div className="text-sm text-slate-500">Blocked Locations</div>
                  <div className="mt-2 text-xl font-bold text-red-600">{project.blocked_locations}</div>
                </div>
                <div>
                  <div className="text-sm text-slate-500">Attendance</div>
                  <div className="mt-2 text-xl font-bold text-red-600">{pct(project.attendance_rate)}</div>
                </div>
                <div>
                  <div className="text-sm text-slate-500">Milestones</div>
                  <div className="mt-2 text-xl font-bold text-red-600">{pct(project.milestone_completion_rate)}</div>
                </div>
                <div className="flex items-center md:justify-end">
                  <Link href={`/projects/${project.id}`} className="inline-flex h-11 items-center justify-center rounded-lg border border-red-200 px-4 text-sm font-semibold text-red-600">
                    Review Project
                  </Link>
                </div>
              </div>
            ))}
          </div>
          <div className="border-t border-slate-100 pb-5 text-center">
            <a href="#interventions" className="inline-flex items-center gap-2 pt-4 text-sm font-semibold text-red-600">
              View all priorities <ChevronRight className="h-4 w-4" />
            </a>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
