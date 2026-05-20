import { Head, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem } from "@/types";

const taskStatusOptions = [
  { value: "pending", label: "Pending" },
  { value: "in_progress", label: "In Progress" },
  { value: "completed", label: "Completed" },
  { value: "on_going", label: "On Going" },
  { value: "blocked", label: "Blocked" },
  { value: "cancelled", label: "Cancelled" },
];

function statusBadgeClass(status: string): string {
  switch (status) {
    case "completed":
      return "border-green-200 bg-green-50 text-green-700";
    case "in_progress":
    case "on_going":
      return "border-blue-200 bg-blue-50 text-blue-700";
    case "blocked":
    case "cancelled":
      return "border-rose-200 bg-rose-50 text-rose-700";
    default:
      return "border-amber-200 bg-amber-50 text-amber-700";
  }
}

export default function EventEventDay({
  event,
}: {
  event: any;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: "Event Day", href: `/events/${event.id}/event-day` },
  ];

  const eventDayWorkstreams = (event.workstreams ?? [])
    .map((workstream: any) => ({
      ...workstream,
      eventDayTasks: (workstream.tasks ?? []).filter((task: any) => task.phase === "event_day"),
      postEventTasks: (workstream.tasks ?? []).filter((task: any) => task.phase === "post_event"),
    }))
    .filter((workstream: any) => workstream.eventDayTasks.length > 0 || workstream.postEventTasks.length > 0);

  const updateTaskStatus = (workstreamId: number, task: any, status: string) => {
    router.put(`/events/${event.id}/tasks/${task.id}`, {
      event_workstream_id: workstreamId,
      phase: task.phase,
      duty: task.duty,
      due_date: task.due_date,
      responsible_person: task.responsible_person,
      outcome: task.outcome,
      status,
      comment: task.comment,
      sort_order: task.sort_order,
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`${event.title} Event Day`} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{event.title} Event Day</h1>
            <p className="text-sm text-muted-foreground">
              Monitor live attendance posture, execution tasks, and post-event operational close-out from one page.
            </p>
          </div>
          <DomainNav items={eventWorkflowNav(event.id)} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
          {[
            ["Register", event.event_day_summary?.total_register ?? 0],
            ["Confirmed", event.event_day_summary?.confirmed ?? 0],
            ["Checked In", event.event_day_summary?.checked_in ?? 0],
            ["Attended", event.event_day_summary?.attended ?? 0],
            ["Outstanding Arrivals", event.event_day_summary?.outstanding_arrivals ?? 0],
            ["Event-Day Open Tasks", event.event_day_summary?.event_day_tasks_open ?? 0],
            ["Post-Event Completed", `${event.event_day_summary?.post_event_tasks_completed ?? 0}/${event.event_day_summary?.post_event_tasks_total ?? 0}`],
          ].map(([label, value]) => (
            <Card key={String(label)} className="border-slate-200 shadow-sm">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm">{label}</CardTitle>
              </CardHeader>
              <CardContent className="text-3xl font-semibold text-slate-950">{String(value)}</CardContent>
            </Card>
          ))}
        </div>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle>Execution Board</CardTitle>
            <CardDescription>
              Track the live operational duties for event day and the immediate post-event follow-through.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-5">
            {eventDayWorkstreams.length === 0 ? (
              <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                No event-day or post-event workstream tasks are currently recorded.
              </div>
            ) : (
              eventDayWorkstreams.map((workstream: any) => (
                <div key={workstream.id} className="rounded-2xl border border-slate-200 bg-white p-5">
                  <div className="space-y-1">
                    <div className="text-lg font-semibold text-slate-950">{workstream.name}</div>
                    <div className="text-sm text-slate-500">{workstream.description ?? "Operational workstream"}</div>
                  </div>

                  <div className="mt-5 grid gap-5 xl:grid-cols-2">
                    <div className="space-y-3">
                      <div className="text-sm font-semibold text-slate-900">Event Day Tasks</div>
                      {workstream.eventDayTasks.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                          No event day tasks in this workstream.
                        </div>
                      ) : (
                        workstream.eventDayTasks.map((task: any) => (
                          <div key={task.id} className="rounded-xl border border-slate-200 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                              <div>
                                <div className="font-semibold text-slate-950">{task.duty}</div>
                                <div className="mt-1 text-xs text-slate-500">
                                  {[task.responsible_person, task.due_date].filter(Boolean).join(" | ") || "No responsible person or due date"}
                                </div>
                              </div>
                              <span className={`rounded-full border px-2.5 py-1 text-[11px] font-medium ${statusBadgeClass(task.status)}`}>
                                {String(task.status).replaceAll("_", " ")}
                              </span>
                            </div>
                            <div className="mt-3 text-sm text-slate-600">
                              Outcome: {task.outcome ?? "No outcome recorded"}
                            </div>
                            <div className="mt-1 text-sm text-slate-500">
                              Update: {task.comment ?? "No status update recorded"}
                            </div>
                            <div className="mt-4">
                              <select
                                value={task.status}
                                onChange={(e) => updateTaskStatus(workstream.id, task, e.target.value)}
                                className="rounded-md border px-3 py-2 text-sm"
                              >
                                {taskStatusOptions.map((option) => (
                                  <option key={option.value} value={option.value}>
                                    {option.label}
                                  </option>
                                ))}
                              </select>
                            </div>
                          </div>
                        ))
                      )}
                    </div>

                    <div className="space-y-3">
                      <div className="text-sm font-semibold text-slate-900">Post-Event Tasks</div>
                      {workstream.postEventTasks.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                          No post-event tasks in this workstream.
                        </div>
                      ) : (
                        workstream.postEventTasks.map((task: any) => (
                          <div key={task.id} className="rounded-xl border border-slate-200 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                              <div>
                                <div className="font-semibold text-slate-950">{task.duty}</div>
                                <div className="mt-1 text-xs text-slate-500">
                                  {[task.responsible_person, task.due_date].filter(Boolean).join(" | ") || "No responsible person or due date"}
                                </div>
                              </div>
                              <span className={`rounded-full border px-2.5 py-1 text-[11px] font-medium ${statusBadgeClass(task.status)}`}>
                                {String(task.status).replaceAll("_", " ")}
                              </span>
                            </div>
                            <div className="mt-3 text-sm text-slate-600">
                              Outcome: {task.outcome ?? "No outcome recorded"}
                            </div>
                            <div className="mt-1 text-sm text-slate-500">
                              Update: {task.comment ?? "No status update recorded"}
                            </div>
                            <div className="mt-4">
                              <select
                                value={task.status}
                                onChange={(e) => updateTaskStatus(workstream.id, task, e.target.value)}
                                className="rounded-md border px-3 py-2 text-sm"
                              >
                                {taskStatusOptions.map((option) => (
                                  <option key={option.value} value={option.value}>
                                    {option.label}
                                  </option>
                                ))}
                              </select>
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
