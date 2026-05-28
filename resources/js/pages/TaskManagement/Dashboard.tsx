import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { taskManagementNavItems } from "@/config/domain-nav/task-management";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type TaskDashboard = {
  summary: { total: number; open: number; in_progress: number; pending_review: number; changes_requested: number; completed: number; overdue: number };
  overdue_tasks: Array<{ id: number; title: string; status: string; priority: string; due_date: string | null; assignee_name: string | null; department_name: string | null; context_name: string }>;
  unassigned_queue: Array<{ id: number; title: string; status: string; priority: string; department_name: string | null; context_name: string }>;
  pending_review_tasks: Array<{ id: number; title: string; status: string; priority: string; due_date: string | null; assignee_name: string | null; department_name: string | null; context_name: string; submitted_for_review_at: string | null }>;
  workload_by_assignee: Array<{ assignee_name: string; open_count: number; in_progress_count: number; blocked_count: number; total_active: number }>;
  department_queues: Array<{ department_name: string; open_count: number; blocked_count: number; active_count: number }>;
};

type TicketDashboard = {
  summary: { total: number; open: number; in_progress: number; resolved: number; closed: number; overdue: number };
  overdue_tickets: Array<{ id: number; title: string; priority: string; status: string; requester_name: string | null; responder_name: string | null; age_hours: number; sla_target_hours: number }>;
  unassigned_queue: Array<{ id: number; title: string; priority: string; status: string; requester_name: string | null; age_hours: number }>;
  workload_by_responder: Array<{ responder_name: string; open_count: number; assigned_count: number; in_progress_count: number; active_count: number }>;
  project_pressure: Array<{ project_name: string; active_count: number; overdue_count: number }>;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Task Management", href: "/task-management" },
];

function MetricCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-xl border bg-card p-4 shadow-sm">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-2 text-2xl font-semibold">{value}</div>
    </div>
  );
}

function EmptyState({ message }: { message: string }) {
  return <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">{message}</div>;
}

export default function TaskManagementDashboard({
  dashboard,
}: {
  dashboard: { persona: string; can_create_task: boolean; can_respond: boolean; tasks: TaskDashboard; tickets: TicketDashboard };
}) {
  const managerView = dashboard.persona === "manager";
  const responderView = dashboard.can_respond && dashboard.persona === "technical_responder";

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Task Management Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Task Management Dashboard</h1>
            <p className="text-sm text-muted-foreground">
              {managerView
                ? "Manager view of team workload, queue pressure, overdue work, and task governance."
                : responderView
                  ? "Responder view of incident backlog, SLA pressure, and assigned response work."
                  : "Personal workflow view of assigned tasks, deadlines, and tickets you raised or carry."}
            </p>
          </div>
          <DomainNav items={taskManagementNavItems} />
        </div>

        <section className="space-y-3">
          <div>
            <h2 className="text-base font-semibold">Task Operations</h2>
            <p className="text-sm text-muted-foreground">
              {managerView ? "Current delivery load across direct assignments and department queues." : "Current work assigned to you, with only governance context relevant to your workflow."}
            </p>
          </div>
          <div className="grid gap-3 md:grid-cols-6 xl:grid-cols-7">
            <MetricCard label="Visible Tasks" value={dashboard.tasks.summary.total} />
            <MetricCard label="Open" value={dashboard.tasks.summary.open} />
            <MetricCard label="In Progress" value={dashboard.tasks.summary.in_progress} />
            <MetricCard label="Awaiting Review" value={dashboard.tasks.summary.pending_review} />
            <MetricCard label="Returned" value={dashboard.tasks.summary.changes_requested} />
            <MetricCard label="Completed" value={dashboard.tasks.summary.completed} />
            <MetricCard label="Overdue" value={dashboard.tasks.summary.overdue} />
          </div>
          <div className={`grid gap-4 ${managerView ? "xl:grid-cols-2" : ""}`}>
            <section className="rounded-xl border bg-card p-4 shadow-sm">
              <h3 className="text-sm font-semibold">Overdue Tasks</h3>
              <div className="mt-3 space-y-3">
                {dashboard.tasks.overdue_tasks.length === 0 ? <EmptyState message="No overdue tasks in your current visibility scope." /> : dashboard.tasks.overdue_tasks.map((task) => (
                  <div key={task.id} className="rounded-lg border p-3">
                    <div className="font-medium">{task.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {task.priority.toUpperCase()} | {task.status.replaceAll("_", " ")} | Due {task.due_date ?? "-"}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {task.assignee_name ?? task.department_name ?? "Queue"} | {task.context_name}
                    </div>
                    <a href={`/task-management/tasks/${task.id}`} className="mt-2 inline-block text-xs text-blue-700 underline">
                      Open task
                    </a>
                  </div>
                ))}
              </div>
            </section>
            {managerView ? (
              <section className="rounded-xl border bg-card p-4 shadow-sm">
                <h3 className="text-sm font-semibold">Department Queue Intake</h3>
                <div className="mt-3 space-y-3">
                  {dashboard.tasks.unassigned_queue.length === 0 ? <EmptyState message="No unassigned department-queue work is waiting." /> : dashboard.tasks.unassigned_queue.map((task) => (
                    <div key={task.id} className="rounded-lg border p-3">
                      <div className="font-medium">{task.title}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {task.priority.toUpperCase()} | {task.status.replaceAll("_", " ")} | {task.department_name ?? "No department"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">{task.context_name}</div>
                      <a href={`/task-management/tasks/${task.id}`} className="mt-2 inline-block text-xs text-blue-700 underline">
                        Open task
                      </a>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}
          </div>
          {managerView ? (
            <div className="grid gap-4 xl:grid-cols-2">
              <section className="rounded-xl border bg-card p-4 shadow-sm">
                <h3 className="text-sm font-semibold">Pending Manager Review</h3>
                <div className="mt-3 space-y-3">
                  {dashboard.tasks.pending_review_tasks.length === 0 ? <EmptyState message="No submitted task work is waiting for signoff." /> : dashboard.tasks.pending_review_tasks.map((task) => (
                    <div key={task.id} className="rounded-lg border p-3">
                      <div className="font-medium">{task.title}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {task.priority.toUpperCase()} | {task.status.replaceAll("_", " ")} | Submitted {task.submitted_for_review_at ?? "-"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {task.assignee_name ?? task.department_name ?? "Queue"} | {task.context_name}
                      </div>
                      <a href={`/task-management/tasks/${task.id}`} className="mt-2 inline-block text-xs text-blue-700 underline">
                        Open task
                      </a>
                    </div>
                  ))}
                </div>
              </section>
              <section className="rounded-xl border bg-card p-4 shadow-sm">
                <h3 className="text-sm font-semibold">Workload By Assignee</h3>
                <div className="mt-3 space-y-3">
                  {dashboard.tasks.workload_by_assignee.length === 0 ? <EmptyState message="No active assignee workload found." /> : dashboard.tasks.workload_by_assignee.map((row) => (
                    <div key={row.assignee_name} className="rounded-lg border p-3">
                      <div className="font-medium">{row.assignee_name}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Active {row.total_active} | Open {row.open_count} | In Progress {row.in_progress_count} | Blocked {row.blocked_count}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
              <section className="rounded-xl border bg-card p-4 shadow-sm">
                <h3 className="text-sm font-semibold">Department Queue Pressure</h3>
                <div className="mt-3 space-y-3">
                  {dashboard.tasks.department_queues.length === 0 ? <EmptyState message="No department queues are carrying active work." /> : dashboard.tasks.department_queues.map((row) => (
                    <div key={row.department_name} className="rounded-lg border p-3">
                      <div className="font-medium">{row.department_name}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Active {row.active_count} | Open {row.open_count} | Blocked {row.blocked_count}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            </div>
          ) : null}
        </section>

        <section className="space-y-3">
          <div>
            <h2 className="text-base font-semibold">Technical Support Operations</h2>
            <p className="text-sm text-muted-foreground">
              {dashboard.can_respond ? "Current SLA pressure, responder workloads, and project-linked support load." : "Support issues relevant to your requester or assignee workflow."}
            </p>
          </div>
          <div className="grid gap-3 md:grid-cols-6">
            <MetricCard label="Visible Tickets" value={dashboard.tickets.summary.total} />
            <MetricCard label="Open" value={dashboard.tickets.summary.open} />
            <MetricCard label="In Progress" value={dashboard.tickets.summary.in_progress} />
            <MetricCard label="Resolved" value={dashboard.tickets.summary.resolved} />
            <MetricCard label="Closed" value={dashboard.tickets.summary.closed} />
            <MetricCard label="SLA Overdue" value={dashboard.tickets.summary.overdue} />
          </div>
          <div className="grid gap-4 xl:grid-cols-2">
            <section className="rounded-xl border bg-card p-4 shadow-sm">
              <h3 className="text-sm font-semibold">Overdue Tickets</h3>
              <div className="mt-3 space-y-3">
                {dashboard.tickets.overdue_tickets.length === 0 ? <EmptyState message="No tickets are currently outside SLA." /> : dashboard.tickets.overdue_tickets.map((ticket) => (
                  <div key={ticket.id} className="rounded-lg border p-3">
                    <div className="font-medium">{ticket.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {ticket.priority.toUpperCase()} | {ticket.status.replaceAll("_", " ")} | Age {ticket.age_hours}h of {ticket.sla_target_hours}h
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      Requester {ticket.requester_name ?? "-"} | Responder {ticket.responder_name ?? "Unassigned"}
                    </div>
                  </div>
                ))}
              </div>
            </section>
            <section className="rounded-xl border bg-card p-4 shadow-sm">
              <h3 className="text-sm font-semibold">Unassigned Technical Queue</h3>
              <div className="mt-3 space-y-3">
                {dashboard.tickets.unassigned_queue.length === 0 ? <EmptyState message="No unassigned tickets are waiting in the queue." /> : dashboard.tickets.unassigned_queue.map((ticket) => (
                  <div key={ticket.id} className="rounded-lg border p-3">
                    <div className="font-medium">{ticket.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {ticket.priority.toUpperCase()} | {ticket.status.replaceAll("_", " ")} | Age {ticket.age_hours}h
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">Requester {ticket.requester_name ?? "-"}</div>
                  </div>
                ))}
              </div>
            </section>
          </div>
          {dashboard.can_respond ? (
            <div className="grid gap-4 xl:grid-cols-2">
              <section className="rounded-xl border bg-card p-4 shadow-sm">
                <h3 className="text-sm font-semibold">Responder Workload</h3>
                <div className="mt-3 space-y-3">
                  {dashboard.tickets.workload_by_responder.length === 0 ? <EmptyState message="No technical responders currently hold active tickets." /> : dashboard.tickets.workload_by_responder.map((row) => (
                    <div key={row.responder_name} className="rounded-lg border p-3">
                      <div className="font-medium">{row.responder_name}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Active {row.active_count} | Open {row.open_count} | Assigned {row.assigned_count} | In Progress {row.in_progress_count}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
              <section className="rounded-xl border bg-card p-4 shadow-sm">
                <h3 className="text-sm font-semibold">Project Support Pressure</h3>
                <div className="mt-3 space-y-3">
                  {dashboard.tickets.project_pressure.length === 0 ? <EmptyState message="No active project-linked tickets are in scope." /> : dashboard.tickets.project_pressure.map((row) => (
                    <div key={row.project_name} className="rounded-lg border p-3">
                      <div className="font-medium">{row.project_name}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Active {row.active_count} | Overdue {row.overdue_count}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            </div>
          ) : null}
        </section>
      </div>
    </AppLayout>
  );
}
