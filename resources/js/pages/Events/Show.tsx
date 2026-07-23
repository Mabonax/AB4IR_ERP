import { Head, Link, router, usePage } from "@inertiajs/react";
import { CircleHelp, ClipboardSignature, FileText, Pencil, Plus, RadioTower, Users } from "lucide-react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Events", href: "/events" },
  { title: "Event Detail", href: "#" },
];

const phaseLabels: Record<string, string> = {
  pre_event: "Pre-Event",
  preparations: "Preparations",
  event_day: "Event Day",
  post_event: "Post-Event",
};

const phaseOrder = ["pre_event", "preparations", "event_day", "post_event"];

function statusBadgeClass(status: string): string {
  switch (status) {
    case "completed":
    case "attended":
      return "border-green-200 bg-green-50 text-green-700";
    case "active":
    case "in_progress":
    case "on_going":
    case "confirmed":
    case "checked_in":
      return "border-blue-200 bg-blue-50 text-blue-700";
    case "registration_closed":
      return "border-indigo-200 bg-indigo-50 text-indigo-700";
    case "blocked":
    case "cancelled":
      return "border-rose-200 bg-rose-50 text-rose-700";
    case "postponed":
      return "border-violet-200 bg-violet-50 text-violet-700";
    case "archived":
      return "border-slate-300 bg-slate-100 text-slate-600";
    default:
      return "border-amber-200 bg-amber-50 text-amber-700";
  }
}

function phaseLabel(phase: string): string {
  return phaseLabels[phase] ?? phase.replaceAll("_", " ");
}

function isComplianceTask(task: any, workstreamName: string): boolean {
  const haystack = `${workstreamName} ${task.duty ?? ""} ${task.outcome ?? ""} ${task.comment ?? ""}`.toLowerCase();
  return haystack.includes("joc")
    || haystack.includes("voc")
    || haystack.includes("public officer")
    || haystack.includes("safety officer")
    || haystack.includes("insurance")
    || haystack.includes("ems")
    || haystack.includes("characterisation");
}

export default function EventShow({
  event,
}: {
  event: any;
}) {
  const { auth, flash } = usePage<SharedData>().props as SharedData & {
    flash?: Record<string, unknown>;
  };
  const canManage = (auth?.user?.permissions ?? []).includes("domain.events.manage");
  const importErrors = Array.isArray(flash?.import_errors) ? (flash?.import_errors as string[]) : [];
  const [activeDepartmentId, setActiveDepartmentId] = useState<number | null>(event.workstreams?.[0]?.id ?? null);
  const [activePhase, setActivePhase] = useState<string>("pre_event");
  const [lifecycleReason, setLifecycleReason] = useState("");
  const [closureForm, setClosureForm] = useState({
    reason: "",
    budget_summary: "",
    outcomes_achieved: event.closure_report?.outcomes_achieved ?? "",
    lessons_learned: event.closure_report?.lessons_learned ?? "",
    risks_encountered: event.closure_report?.risks_encountered ?? "",
    recommendations: event.closure_report?.recommendations ?? "",
  });
  const [closureAssetCategory, setClosureAssetCategory] = useState<"supporting_document" | "photo">("supporting_document");
  const [closureAssetDescription, setClosureAssetDescription] = useState("");
  const [closureAssetFile, setClosureAssetFile] = useState<File | null>(null);
  const lifecycle = event.lifecycle ?? {};
  const closureReport = event.closure_report;
  const history = Array.isArray(event.history) ? event.history : [];
  const statusReason = event.status_reason ?? "No lifecycle reason has been recorded yet.";

  const workstreams = useMemo(() => {
    return (event.workstreams ?? []).map((workstream: any) => {
      const tasks = [...(workstream.tasks ?? [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
      const completedTasks = tasks.filter((task) => task.status === "completed").length;
      const activeTasks = tasks.filter((task) => !["completed", "cancelled"].includes(task.status)).length;
      const activeTaskCount = tasks.filter((task) => task.status !== "cancelled").length;
      const overdueTasks = tasks.filter((task) => task.due_date && !["completed", "cancelled"].includes(task.status) && task.due_date < new Date().toISOString().slice(0, 10)).length;
      const enrichedTasks = tasks.map((task) => ({
        ...task,
        workstream_id: workstream.id,
        workstream_name: workstream.name,
        phase_label: phaseLabel(task.phase),
        isCompliance: isComplianceTask(task, workstream.name),
      }));
      const groupedTasks = (workstream.task_group_options ?? []).map((groupName: string) => {
        const groupTasks = enrichedTasks.filter((task) => task.task_group === groupName);
        const activeGroupTasks = groupTasks.filter((task) => task.status !== "cancelled");
        const completedGroupTasks = activeGroupTasks.filter((task) => task.status === "completed").length;

        return {
          name: groupName,
          tasks: groupTasks,
          total: groupTasks.length,
          completed: completedGroupTasks,
          open: groupTasks.filter((task) => !["completed", "cancelled"].includes(task.status)).length,
          completionPercentage: activeGroupTasks.length > 0 ? Math.round((completedGroupTasks / activeGroupTasks.length) * 100) : 0,
        };
      }).filter((group: { total: number }) => group.total > 0);

      return {
        ...workstream,
        tasks: enrichedTasks,
        taskCount: tasks.length,
        completedTasks,
        activeTasks,
        overdueTasks,
        groupedTasks,
        completionPercentage: activeTaskCount > 0 ? Math.round((completedTasks / activeTaskCount) * 100) : 0,
      };
    });
  }, [event.workstreams]);

  const departmentSummaries = event.planning_summary?.department_summaries ?? [];
  const activeDepartment = useMemo(
    () => workstreams.find((workstream: any) => workstream.id === activeDepartmentId) ?? workstreams[0] ?? null,
    [workstreams, activeDepartmentId],
  );
  const activeDepartmentSummary = useMemo(
    () => departmentSummaries.find((department: any) => department.id === activeDepartment?.id) ?? null,
    [departmentSummaries, activeDepartment],
  );
  const activePhaseGroups = useMemo(() => {
    if (!activeDepartment) return [];

    return activeDepartment.groupedTasks
      .map((group: any) => {
        const tasks = group.tasks.filter((task: any) => task.phase === activePhase);
        const activeTasks = tasks.filter((task: any) => task.status !== "cancelled");
        const completed = activeTasks.filter((task: any) => task.status === "completed").length;

        return {
          ...group,
          tasks,
          total: tasks.length,
          open: tasks.filter((task: any) => !["completed", "cancelled"].includes(task.status)).length,
          completionPercentage: activeTasks.length > 0 ? Math.round((completed / activeTasks.length) * 100) : 0,
        };
      })
      .filter((group: any) => group.total > 0);
  }, [activeDepartment, activePhase]);

  const submitLifecycle = (action: string, payload: Record<string, unknown>) => {
    router.post(`/events/${event.id}/${action}`, payload, {
      preserveScroll: true,
    });
  };

  const resetClosureAssetForm = () => {
    setClosureAssetCategory("supporting_document");
    setClosureAssetDescription("");
    setClosureAssetFile(null);
  };

  const submitClosureCompletion = () => {
    if (
      !closureForm.reason.trim()
      || !closureForm.outcomes_achieved.trim()
      || !closureForm.lessons_learned.trim()
      || !closureForm.risks_encountered.trim()
      || !closureForm.recommendations.trim()
    ) {
      return;
    }

    router.post(`/events/${event.id}/complete`, closureForm, {
      preserveScroll: true,
    });
  };

  const submitClosureAsset = () => {
    if (!closureAssetFile) {
      return;
    }

    router.post(`/events/${event.id}/closure-assets`, {
      category: closureAssetCategory,
      description: closureAssetDescription || null,
      file: closureAssetFile,
    }, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => resetClosureAssetForm(),
    });
  };

  const lifecycleActions = [
    { label: "Open Registration", action: "open-registration", enabled: ["planned", "postponed"].includes(event.status) },
    { label: "Close Registration", action: "close-registration", enabled: event.status === "open_for_registration" },
    { label: "Start Event", action: "start", enabled: ["open_for_registration", "registration_closed"].includes(event.status) },
    { label: "Postpone", action: "postpone", enabled: ["planned", "open_for_registration", "registration_closed", "active"].includes(event.status), danger: true },
    { label: "Cancel", action: "cancel", enabled: ["planned", "open_for_registration", "registration_closed", "active", "postponed"].includes(event.status), danger: true },
    { label: "Archive", action: "archive", enabled: ["completed", "cancelled", "postponed"].includes(event.status) },
  ];

  const lifecycleMoments = [
    ["Registration opened", lifecycle.registration_opened_at],
    ["Registration closed", lifecycle.registration_closed_at],
    ["Event started", lifecycle.started_at],
    ["Event completed", lifecycle.completed_at],
    ["Event cancelled", lifecycle.cancelled_at],
    ["Event postponed", lifecycle.postponed_at],
    ["Event archived", lifecycle.archived_at],
  ].filter(([, value]) => Boolean(value));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={event.title} />

      <div className="space-y-6 p-4">
        {flash?.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {flash?.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        {importErrors.length > 0 ? (
          <div className="rounded-md border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-800">
            <div className="font-semibold">Import errors</div>
            <ul className="mt-2 list-disc space-y-1 pl-5">
              {importErrors.map((error) => (
                <li key={error}>{error}</li>
              ))}
            </ul>
          </div>
        ) : null}

        <div className="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
          <Card className="border-slate-200 shadow-sm">
            <CardHeader className="space-y-4">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusBadgeClass(event.status ?? "planned")}`}>
                      {String(event.status ?? "planned").replaceAll("_", " ")}
                    </span>
                    <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                      {event.event_type ?? "Institutional Event"}
                    </span>
                    {event.event_year ? (
                      <span className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                        {event.event_year}
                      </span>
                    ) : null}
                  </div>
                  <div>
                    <h1 className="text-2xl font-semibold text-slate-950">{event.title}</h1>
                    <p className="mt-1 max-w-3xl text-sm text-slate-600">
                      Use this page for event context and planning control. Participants, registers, and event-day execution now live on their
                      own dedicated pages.
                    </p>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <a
                    href={`/events/${event.id}/report/pdf`}
                    className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                  >
                    Download Event Report
                  </a>
                  {canManage ? (
                    <Link
                      href={`/events/${event.id}/edit`}
                      className="rounded-md border border-orange-500 px-3 py-2 text-sm font-medium text-orange-600 hover:bg-orange-500 hover:text-white"
                    >
                      Edit Event
                    </Link>
                  ) : null}
                </div>
              </div>

              <DomainNav items={eventWorkflowNav(event.id)} />
            </CardHeader>
            <CardContent className="space-y-5">
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {[
                  ["Format", event.event_format ?? "-"],
                  ["Track", event.track_name ?? "-"],
                  ["Venue", event.venue_name ?? "-"],
                  ["Owner", event.owner_name ?? "-"],
                  ["Location", event.location ?? "-"],
                  ["Expected Attendees", event.expected_attendees ?? "-"],
                  ["Series Key", event.annual_series_key ?? "-"],
                  ["Annual Event", event.is_annual ? "Yes" : "No"],
                ].map(([label, value]) => (
                  <div key={String(label)} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{label}</div>
                    <div className="mt-2 text-base font-semibold text-slate-950">{String(value)}</div>
                  </div>
                ))}
              </div>

              <div className="grid gap-4 lg:grid-cols-3">
                <div className="rounded-xl border border-slate-200 p-4">
                  <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Description</div>
                  <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{event.description ?? "No description recorded."}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                  <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Objectives</div>
                  <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{event.objectives ?? "No objectives recorded."}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                  <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Technical Requirements</div>
                  <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">
                    {event.technical_requirements ?? "No technical requirements recorded."}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <div className="space-y-6">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Operational Tabs</CardTitle>
                <CardDescription>Use the dedicated pages for the heavier event workflows.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-3">
                {[
                  {
                    title: "Participants",
                    description: "Manage attendees, speakers, VIPs, media houses, exhibitors, and imports.",
                    href: `/events/${event.id}/participants`,
                    icon: <Users className="h-4 w-4" />,
                  },
                  {
                    title: "Registers",
                    description: "Focus only on attendance registers, printable packs, and export outputs.",
                    href: `/events/${event.id}/registers`,
                    icon: <ClipboardSignature className="h-4 w-4" />,
                  },
                  {
                    title: "Event Day",
                    description: "Monitor live execution, attendance posture, and post-event follow-through.",
                    href: `/events/${event.id}/event-day`,
                    icon: <RadioTower className="h-4 w-4" />,
                  },
                ].map((item) => (
                  <Link
                    key={item.title}
                    href={item.href}
                    className="rounded-xl border border-slate-200 p-4 transition hover:border-red-200 hover:bg-red-50/40"
                  >
                    <div className="flex items-start gap-3">
                      <div className="rounded-lg bg-slate-100 p-2 text-slate-700">{item.icon}</div>
                      <div>
                        <div className="font-semibold text-slate-950">{item.title}</div>
                        <div className="mt-1 text-sm text-slate-600">{item.description}</div>
                      </div>
                    </div>
                  </Link>
                ))}
              </CardContent>
            </Card>

            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Annual Series Intelligence</CardTitle>
                <CardDescription>Use the event line, then year, when reviewing historical performance.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm">
                <div className="grid gap-3 sm:grid-cols-2">
                  <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Years Run</div>
                    <div className="mt-2 text-xl font-semibold text-slate-950">{event.annual_series_summary?.years_run ?? 0}</div>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Completed Events</div>
                    <div className="mt-2 text-xl font-semibold text-slate-950">{event.annual_series_summary?.completed_events ?? 0}</div>
                  </div>
                </div>

                {event.annual_series_key ? (
                  <Link
                    href={`/events/series/${event.annual_series_key}`}
                    className="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                  >
                    Open Event Line by Year
                  </Link>
                ) : (
                  <p className="text-slate-500">This event is not yet linked to a broader annual series.</p>
                )}
              </CardContent>
            </Card>

            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>How Workstreams Operate</CardTitle>
                <CardDescription>Each workstream is a responsibility lane inside one event, not a separate event.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-sm text-slate-700">
                <div className="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <CircleHelp className="mt-0.5 h-4 w-4 shrink-0 text-slate-500" />
                  <div>
                    <div className="font-medium text-slate-900">What it means</div>
                    <div className="mt-1">
                      Administration, Marketing, Technical, and Impact and Reporting are the event departments. Each one owns a focused task
                      library and completion rollup inside the same event.
                    </div>
                  </div>
                </div>
                <div className="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <CircleHelp className="mt-0.5 h-4 w-4 shrink-0 text-slate-500" />
                  <div>
                    <div className="font-medium text-slate-900">How tasks flow</div>
                    <div className="mt-1">
                      Every workstream carries tasks across the four phases: Pre-Event, Preparations, Event Day, and Post-Event. That is how the
                      event plan moves from setup to execution to close-out.
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <CardTitle>Department Task Console</CardTitle>
              <CardDescription>Open one department at a time, then manage its tasks phase by phase with status updates and evidence.</CardDescription>
            </div>
            {canManage ? (
              <div className="flex flex-wrap gap-2">
                <Link href={`/events/${event.id}/workstreams/create`}>
                  <Button variant="outline">
                    <Plus className="h-4 w-4" />
                    Add Department Lane
                  </Button>
                </Link>
                <Link href={`/events/${event.id}/tasks/create`}>
                  <Button>
                    <Plus className="h-4 w-4" />
                    Add Task
                  </Button>
                </Link>
              </div>
            ) : null}
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
              {[
                ["Departments", event.planning_summary?.total_workstreams ?? 0],
                ["Total Tasks", event.planning_summary?.total_tasks ?? 0],
                ["Completed", event.planning_summary?.completed_tasks ?? 0],
                ["Open", event.planning_summary?.open_tasks ?? 0],
                ["Overall Completion", `${event.planning_summary?.completion_percentage ?? 0}%`],
              ].map(([label, value]) => (
                <div key={String(label)} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{label}</div>
                  <div className="mt-2 text-2xl font-semibold text-slate-950">{String(value)}</div>
                </div>
              ))}
            </div>

            <div className="flex flex-wrap gap-3">
              {departmentSummaries.map((department: any) => {
                const isActive = department.id === activeDepartment?.id;

                return (
                  <button
                    key={department.id}
                    type="button"
                    onClick={() => {
                      setActiveDepartmentId(department.id);
                      setActivePhase("pre_event");
                    }}
                    className={`min-w-52 rounded-xl border p-4 text-left transition ${
                      isActive
                        ? "border-red-300 bg-red-50 shadow-sm"
                        : "border-slate-200 bg-white hover:border-slate-300"
                    }`}
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <div className="text-sm font-semibold text-slate-950">{department.name}</div>
                        <div className="mt-1 text-xs text-slate-500">
                          {department.completed}/{department.total} completed
                        </div>
                      </div>
                      <div className="text-lg font-semibold text-slate-950">{department.completion_percentage}%</div>
                    </div>
                    <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                      <div
                        className={`h-full rounded-full transition-[width] ${isActive ? "bg-red-500" : "bg-slate-400"}`}
                        style={{ width: `${department.completion_percentage}%` }}
                      />
                    </div>
                    <div className="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                      <span className="rounded-full bg-slate-100 px-2.5 py-1">{department.open} open</span>
                      <span className="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700">{department.overdue} overdue</span>
                    </div>
                  </button>
                );
              })}
            </div>

            {workstreams.length === 0 || !activeDepartment ? (
              <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                No department lanes have been set up yet. Start by creating one, then add event tasks under it.
              </div>
            ) : (
              <div className="space-y-4">
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                  <div className="border-b border-slate-200 px-5 py-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                      <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                          <div className="text-lg font-semibold text-slate-950">{activeDepartment.name}</div>
                          <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                            {activeDepartment.taskCount} tasks
                          </span>
                        </div>
                        <div className="text-sm text-slate-600">
                          {activeDepartment.description ?? "No description recorded for this department lane."}
                        </div>
                        <div className="flex flex-wrap gap-2 text-xs text-slate-500">
                          <span className="rounded-full bg-slate-100 px-2.5 py-1">{activeDepartmentSummary?.completed ?? activeDepartment.completedTasks} completed</span>
                          <span className="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">{activeDepartmentSummary?.open ?? activeDepartment.activeTasks} open</span>
                          <span className="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700">{activeDepartmentSummary?.overdue ?? activeDepartment.overdueTasks} overdue</span>
                          <span className="rounded-full bg-green-50 px-2.5 py-1 text-green-700">{activeDepartmentSummary?.completion_percentage ?? activeDepartment.completionPercentage}% complete</span>
                        </div>
                      </div>
                      {canManage ? (
                        <div className="flex flex-wrap gap-2">
                          <Link href={`/events/${event.id}/workstreams/${activeDepartment.id}/edit`}>
                            <Button variant="outline" size="sm">
                              <Pencil className="h-4 w-4" />
                              Edit Department
                            </Button>
                          </Link>
                          <Link href={`/events/${event.id}/tasks/create?event_workstream_id=${activeDepartment.id}&phase=${activePhase}`}>
                            <Button size="sm">
                              <Plus className="h-4 w-4" />
                              Add Custom Task
                            </Button>
                          </Link>
                        </div>
                      ) : null}
                    </div>
                  </div>

                  <div className="border-b border-slate-200 px-5 py-4">
                    <div className="flex flex-wrap gap-2">
                      {phaseOrder.map((phaseKey) => (
                        <button
                          key={phaseKey}
                          type="button"
                          onClick={() => setActivePhase(phaseKey)}
                          className={`rounded-full border px-3 py-2 text-sm font-medium transition ${
                            activePhase === phaseKey
                              ? "border-red-300 bg-red-50 text-red-700"
                              : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                          }`}
                        >
                          {phaseLabels[phaseKey]}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div className="space-y-5 p-5">
                    {activePhaseGroups.length === 0 ? (
                      <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No tasks recorded for {phaseLabel(activePhase)} in {activeDepartment.name} yet.
                      </div>
                    ) : (
                      activePhaseGroups.map((group: any) => (
                        <div key={`${activeDepartment.id}-${activePhase}-${group.name}`} className="rounded-xl border border-slate-200 bg-slate-50/60">
                          <div className="border-b border-slate-200 px-4 py-3">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                              <div>
                                <div className="text-sm font-semibold text-slate-950">{group.name}</div>
                                <div className="mt-1 text-xs text-slate-500">
                                  {group.completed}/{group.total} completed in {phaseLabel(activePhase)}
                                </div>
                              </div>
                              <div className="flex flex-wrap items-center gap-2 text-xs">
                                <span className="rounded-full bg-white px-2.5 py-1 text-slate-600">{group.open} open</span>
                                <span className="rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-700">
                                  {group.completionPercentage}% complete
                                </span>
                              </div>
                            </div>
                          </div>
                          <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                              <thead className="bg-white text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                  <th className="px-4 py-3 font-medium">Task</th>
                                  <th className="px-4 py-3 font-medium">Due Date</th>
                                  <th className="px-4 py-3 font-medium">Responsible</th>
                                  <th className="px-4 py-3 font-medium">Evidence / Link</th>
                                  <th className="px-4 py-3 font-medium">Status / Update</th>
                                  {canManage ? <th className="px-4 py-3 font-medium">Actions</th> : null}
                                </tr>
                              </thead>
                              <tbody className="divide-y divide-slate-100 bg-white">
                                {group.tasks.map((task: any) => (
                                  <tr key={task.id}>
                                    <td className="px-4 py-3 align-top">
                                      <div className="font-medium text-slate-900">{task.duty}</div>
                                      <div className="mt-1 text-xs text-slate-500">{task.outcome ?? "No expected deliverable recorded."}</div>
                                      <div className="mt-2 flex flex-wrap gap-2">
                                        {task.isCompliance ? (
                                          <span className="inline-flex rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700">
                                            Compliance
                                          </span>
                                        ) : null}
                                        {task.is_custom ? (
                                          <span className="inline-flex rounded-full border border-purple-200 bg-purple-50 px-2 py-0.5 text-[11px] font-medium text-purple-700">
                                            Custom
                                          </span>
                                        ) : null}
                                      </div>
                                    </td>
                                    <td className="px-4 py-3 align-top text-slate-700">{task.due_date ?? "-"}</td>
                                    <td className="px-4 py-3 align-top text-slate-700">{task.responsible_person ?? "-"}</td>
                                    <td className="px-4 py-3 align-top">
                                      <div className="space-y-2">
                                        {task.has_evidence_file ? (
                                          <a
                                            href={`/events/${event.id}/tasks/${task.id}/evidence`}
                                            className="inline-flex items-center rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                          >
                                            {task.evidence_file_name ?? "Download evidence"}
                                          </a>
                                        ) : (
                                          <div className="text-xs text-slate-500">No file uploaded.</div>
                                        )}
                                        {task.evidence_url ? (
                                          <a
                                            href={task.evidence_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="block text-xs font-medium text-blue-700 underline-offset-4 hover:underline"
                                          >
                                            Open linked evidence
                                          </a>
                                        ) : (
                                          <div className="text-xs text-slate-500">No linked URL.</div>
                                        )}
                                      </div>
                                    </td>
                                    <td className="px-4 py-3 align-top">
                                      <div>
                                        <span className={`rounded-full border px-2.5 py-1 text-[11px] font-medium ${statusBadgeClass(task.status)}`}>
                                          {String(task.status).replaceAll("_", " ")}
                                        </span>
                                      </div>
                                      <div className="mt-2 text-slate-600">{task.comment ?? "-"}</div>
                                      {task.completed_at ? (
                                        <div className="mt-2 text-xs text-slate-500">Completed: {task.completed_at}</div>
                                      ) : null}
                                    </td>
                                    {canManage ? (
                                      <td className="px-4 py-3 align-top">
                                        <Link href={`/events/${event.id}/tasks/${task.id}/edit`}>
                                          <Button variant="outline" size="sm">
                                            Update Task
                                          </Button>
                                        </Link>
                                      </td>
                                    ) : null}
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle>Outcome Report Snapshot</CardTitle>
            <CardDescription>Keep the overview focused on delivery signals, not the full reporting workflow.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 lg:grid-cols-[0.9fr,1.1fr]">
            <div className="grid gap-4 sm:grid-cols-2">
              {[
                ["Report Status", String(event.outcome_report?.report_status ?? "draft").replaceAll("_", " ")],
                ["Highlights", event.outcome_report?.highlights ? "Recorded" : "Pending"],
                ["Opportunities", event.outcome_report?.opportunities_created ? "Recorded" : "Pending"],
                ["Thank You Status", event.outcome_report?.thank_you_status ?? "Pending"],
              ].map(([label, value]) => (
                <div key={String(label)} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{label}</div>
                  <div className="mt-2 text-base font-semibold text-slate-950">{String(value)}</div>
                </div>
              ))}
            </div>
            <div className="rounded-xl border border-slate-200 p-4">
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Summary</div>
              <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">
                {event.outcome_report?.summary ?? "No outcome summary has been recorded yet."}
              </p>
              <div className="mt-4 flex flex-wrap gap-2">
                <Link
                  href={`/events/${event.id}/event-day`}
                  className="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                  Open Event Day and Report Workflow
                </Link>
                <a
                  href={`/events/${event.id}/report/pdf`}
                  className="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                  <FileText className="mr-2 h-4 w-4" />
                  Download PDF
                </a>
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="grid gap-6 xl:grid-cols-[1.05fr,0.95fr]">
          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <CardTitle>Lifecycle and Closure</CardTitle>
              <CardDescription>Move the event through explicit lifecycle transactions, then lock the close-out with one closure record.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 lg:grid-cols-[0.95fr,1.05fr]">
                <div className="space-y-4">
                  <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current Lifecycle Status</div>
                    <div className={`mt-3 inline-flex rounded-full border px-3 py-1 text-sm font-medium ${statusBadgeClass(event.status ?? "planned")}`}>
                      {String(event.status ?? "planned").replaceAll("_", " ")}
                    </div>
                    <p className="mt-3 text-sm text-slate-600">{statusReason}</p>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-4">
                    <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Lifecycle Timeline</div>
                    {lifecycleMoments.length === 0 ? (
                      <p className="mt-3 text-sm text-slate-500">No lifecycle timestamps have been recorded yet.</p>
                    ) : (
                      <div className="mt-3 space-y-3">
                        {lifecycleMoments.map(([label, value]) => (
                          <div key={String(label)} className="flex items-start justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                            <span className="font-medium text-slate-900">{label}</span>
                            <span className="text-right text-slate-600">{String(value)}</span>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>

                <div className="space-y-4">
                  {canManage ? (
                    <div className="rounded-xl border border-slate-200 p-4">
                      <div className="text-sm font-semibold text-slate-950">Lifecycle Actions</div>
                      <p className="mt-1 text-sm text-slate-600">
                        Each transition requires a reason so the event file keeps an auditable operational trail.
                      </p>
                      <textarea
                        className="mt-4 min-h-28 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                        placeholder="Capture the operational reason for the selected lifecycle action."
                        value={lifecycleReason}
                        onChange={(current) => setLifecycleReason(current.target.value)}
                      />
                      <div className="mt-4 flex flex-wrap gap-2">
                        {lifecycleActions.map((item) => (
                          <button
                            key={item.action}
                            type="button"
                            disabled={!item.enabled || !lifecycleReason.trim()}
                            onClick={() => submitLifecycle(item.action, { reason: lifecycleReason })}
                            className={`rounded-md border px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50 ${
                              item.danger
                                ? "border-rose-300 text-rose-700 hover:bg-rose-50"
                                : "border-slate-300 text-slate-700 hover:bg-slate-50"
                            }`}
                          >
                            {item.label}
                          </button>
                        ))}
                      </div>
                    </div>
                  ) : null}

                  <div className="rounded-xl border border-slate-200 p-4">
                    <div className="text-sm font-semibold text-slate-950">Closure Summary</div>
                    {closureReport ? (
                      <div className="mt-4 space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance Rate</div>
                            <div className="mt-2 text-2xl font-semibold text-slate-950">{closureReport.attendance_summary?.attendance_rate ?? 0}%</div>
                            <div className="mt-1 text-xs text-slate-500">
                              {closureReport.attendance_summary?.attendee_count ?? 0} attendees from {closureReport.attendance_summary?.participant_count ?? 0} registered participants
                            </div>
                          </div>
                          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registration Conversion</div>
                            <div className="mt-2 text-2xl font-semibold text-slate-950">{closureReport.registration_summary?.conversion_rate ?? 0}%</div>
                            <div className="mt-1 text-xs text-slate-500">
                              {closureReport.registration_summary?.attended ?? 0} attended from {closureReport.registration_summary?.registered ?? 0} registered
                            </div>
                          </div>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2">
                          {[
                            ["Closure reason", closureReport.closure_reason],
                            ["Budget summary", closureReport.budget_summary],
                            ["Outcomes achieved", closureReport.outcomes_achieved],
                            ["Lessons learned", closureReport.lessons_learned],
                            ["Risks encountered", closureReport.risks_encountered],
                            ["Recommendations", closureReport.recommendations],
                          ].map(([label, value]) => (
                            <div key={String(label)} className="rounded-xl border border-slate-200 p-4">
                              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{label}</div>
                              <p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{value ? String(value) : "Not recorded."}</p>
                            </div>
                          ))}
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                          Closed by {closureReport.closed_by_name ?? "Unknown user"} on {closureReport.closed_at ?? "unknown date"}.
                        </div>
                      </div>
                    ) : (
                      <p className="mt-3 text-sm text-slate-500">No closure report has been recorded for this event yet.</p>
                    )}
                  </div>
                </div>
              </div>

              {canManage ? (
                <div className="grid gap-4 lg:grid-cols-[1fr,0.95fr]">
                  <div className="rounded-xl border border-slate-200 p-4">
                    <div className="text-sm font-semibold text-slate-950">Complete Event and Record Closure</div>
                    <p className="mt-1 text-sm text-slate-600">
                      Completion finalizes the event, updates the outcome report snapshot, and stores the operational close-out narrative.
                    </p>
                    <div className="mt-4 grid gap-4">
                      {[
                        ["reason", "Completion reason", "Capture why the event is ready for completion."],
                        ["budget_summary", "Budget summary", "Optional budget or cost variance summary."],
                        ["outcomes_achieved", "Outcomes achieved", "What was delivered, achieved, or closed out successfully."],
                        ["lessons_learned", "Lessons learned", "Key lessons the next annual or similar event should reuse."],
                        ["risks_encountered", "Risks encountered", "Operational, participation, or delivery risks faced during execution."],
                        ["recommendations", "Recommendations", "What should be improved or actioned next."],
                      ].map(([field, label, placeholder]) => (
                        <div key={String(field)} className="space-y-2">
                          <label className="block text-sm font-medium text-slate-900">{label}</label>
                          <textarea
                            className="min-h-24 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                            placeholder={String(placeholder)}
                            value={closureForm[field as keyof typeof closureForm]}
                            onChange={(current) => setClosureForm((existing) => ({ ...existing, [field]: current.target.value }))}
                          />
                        </div>
                      ))}
                    </div>
                    <div className="mt-4">
                      <Button
                        type="button"
                        disabled={event.status !== "active"}
                        onClick={submitClosureCompletion}
                      >
                        Complete Event
                      </Button>
                    </div>
                  </div>

                  <div className="rounded-xl border border-slate-200 p-4">
                    <div className="text-sm font-semibold text-slate-950">Closure Assets</div>
                    <p className="mt-1 text-sm text-slate-600">
                      Upload proof files after the closure report exists so the event file carries final evidence in one place.
                    </p>

                    {closureReport ? (
                      <>
                        <div className="mt-4 grid gap-4">
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-slate-900">Category</label>
                            <select
                              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                              value={closureAssetCategory}
                              onChange={(current) => setClosureAssetCategory(current.target.value as "supporting_document" | "photo")}
                            >
                              <option value="supporting_document">Supporting document</option>
                              <option value="photo">Photo</option>
                            </select>
                          </div>
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-slate-900">Description</label>
                            <textarea
                              className="min-h-24 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                              placeholder="Optional note about the uploaded closure evidence."
                              value={closureAssetDescription}
                              onChange={(current) => setClosureAssetDescription(current.target.value)}
                            />
                          </div>
                          <div className="space-y-2">
                            <label className="block text-sm font-medium text-slate-900">File</label>
                            <input
                              type="file"
                              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                              onChange={(current) => setClosureAssetFile(current.target.files?.[0] ?? null)}
                            />
                          </div>
                        </div>

                        <div className="mt-4">
                          <Button type="button" disabled={!closureAssetFile} onClick={submitClosureAsset}>
                            Upload Closure Asset
                          </Button>
                        </div>
                      </>
                    ) : (
                      <div className="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                        Complete the event first to unlock closure asset uploads.
                      </div>
                    )}

                    <div className="mt-6 space-y-3">
                      <div className="text-sm font-semibold text-slate-950">Recorded Assets</div>
                      {(closureReport?.assets ?? []).length === 0 ? (
                        <p className="text-sm text-slate-500">No closure assets uploaded yet.</p>
                      ) : (
                        (closureReport.assets ?? []).map((asset: any) => (
                          <div key={asset.id} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                              <div>
                                <div className="font-medium text-slate-900">{asset.file_name}</div>
                                <div className="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">
                                  {String(asset.category ?? "supporting_document").replaceAll("_", " ")}
                                </div>
                                <div className="mt-2 text-sm text-slate-600">{asset.description ?? "No description recorded."}</div>
                                <div className="mt-2 text-xs text-slate-500">
                                  Uploaded by {asset.uploaded_by_name ?? "Unknown user"} on {asset.created_at ?? "-"}
                                </div>
                              </div>
                              <a
                                href={`/events/${event.id}/closure-assets/${asset.id}`}
                                className="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white"
                              >
                                Download
                              </a>
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <CardTitle>Event History</CardTitle>
              <CardDescription>Every lifecycle move and closure upload is recorded here for operational traceability.</CardDescription>
            </CardHeader>
            <CardContent>
              {history.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-sm text-slate-500">
                  No lifecycle history has been recorded yet.
                </div>
              ) : (
                <div className="space-y-4">
                  {history.map((item: any) => (
                    <div key={item.id} className="rounded-xl border border-slate-200 p-4">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-medium text-slate-950">{item.summary}</div>
                          <div className="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">
                            {String(item.action ?? "history").replaceAll("_", " ")}
                          </div>
                        </div>
                        <div className="text-xs text-slate-500">{item.created_at ?? "-"}</div>
                      </div>
                      <div className="mt-3 flex flex-wrap gap-2 text-xs">
                        {item.from_status ? (
                          <span className={`rounded-full border px-2.5 py-1 ${statusBadgeClass(item.from_status)}`}>
                            From {String(item.from_status).replaceAll("_", " ")}
                          </span>
                        ) : null}
                        {item.to_status ? (
                          <span className={`rounded-full border px-2.5 py-1 ${statusBadgeClass(item.to_status)}`}>
                            To {String(item.to_status).replaceAll("_", " ")}
                          </span>
                        ) : null}
                      </div>
                      <p className="mt-3 text-sm text-slate-600">{item.reason ?? "No reason recorded."}</p>
                      <div className="mt-2 text-xs text-slate-500">Actor: {item.actor_name ?? "System"}</div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
