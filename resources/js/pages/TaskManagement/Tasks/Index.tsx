import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { taskManagementNavItems } from "@/config/domain-nav/task-management";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type TaskRow = {
  id: number;
  title: string;
  description: string | null;
  status: "open" | "in_progress" | "blocked" | "completed" | "cancelled";
  priority: "low" | "medium" | "high" | "urgent";
  due_date: string | null;
  context_type: string;
  project_name: string | null;
  program_title: string | null;
  creator_name: string | null;
  assignee_name: string | null;
  assigned_to_user_id: number | null;
  assigned_department_id: number | null;
  assigned_department_name: string | null;
  completion_notes: string | null;
  comments: Array<{ id: number; user_name: string | null; message: string; created_at: string | null }> | { data?: Array<{ id: number; user_name: string | null; message: string; created_at: string | null }> };
  history: Array<{ id: number; actor_name: string | null; action: string; summary: string; created_at: string | null }> | { data?: Array<{ id: number; actor_name: string | null; action: string; summary: string; created_at: string | null }> };
  can: { update_status: boolean; comment: boolean; reassign: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Task Management", href: "/task-management/tasks" },
  { title: "Tasks", href: "/task-management/tasks" },
];

export default function TaskManagementTasksIndex({
  tasks,
  assignees,
  departments,
  projects,
  programs,
  filters,
  summary,
  can,
}: {
  tasks: { data: TaskRow[] };
  assignees: Array<{ id: number; name: string; email: string }>;
  departments: Array<{ id: number; name: string }>;
  projects: Array<{ id: number; name: string }>;
  programs: Array<{ id: number; title: string }>;
  filters: Record<string, string>;
  summary: { total: number; open: number; in_progress: number; completed: number; overdue: number };
  can: { create: boolean };
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [openTaskId, setOpenTaskId] = useState<number | null>(null);
  const createForm = useForm({
    title: "",
    description: "",
    priority: "medium",
    due_date: "",
    project_id: "",
    program_id: "",
    assigned_to_user_id: "",
    assigned_department_id: "",
  });
  const filterForm = useForm({
    search: filters.search ?? "",
    status: filters.status ?? "",
    priority: filters.priority ?? "",
    department_id: filters.department_id ?? "",
    project_id: filters.project_id ?? "",
    program_id: filters.program_id ?? "",
    assignee_user_id: filters.assignee_user_id ?? "",
    overdue: filters.overdue ?? "",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Task Management Tasks" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Task Management</h1>
          <DomainNav items={taskManagementNavItems} />
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {flash.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        {can.create ? (
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Create Task</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Managers can distribute department work. Project managers can assign project-linked tasks across departments.
            </p>
            <form
              className="mt-4 grid gap-3 md:grid-cols-2"
              onSubmit={(e) => {
                e.preventDefault();
                createForm.post("/task-management/tasks", {
                  preserveScroll: true,
                  onSuccess: () => createForm.reset(),
                });
              }}
            >
              <div className="md:col-span-2">
                <label className="mb-1 block text-sm font-medium">Title</label>
                <input
                  value={createForm.data.title}
                  onChange={(e) => createForm.setData("title", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
                {createForm.errors.title ? <p className="mt-1 text-sm text-red-600">{createForm.errors.title}</p> : null}
              </div>
              <div className="md:col-span-2">
                <label className="mb-1 block text-sm font-medium">Description</label>
                <textarea
                  value={createForm.data.description}
                  onChange={(e) => createForm.setData("description", e.currentTarget.value)}
                  rows={3}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Priority</label>
                <select
                  value={createForm.data.priority}
                  onChange={(e) => createForm.setData("priority", e.currentTarget.value as typeof createForm.data.priority)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Due Date</label>
                <input
                  type="date"
                  value={createForm.data.due_date}
                  onChange={(e) => createForm.setData("due_date", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Assign To User</label>
                <select
                  value={String(createForm.data.assigned_to_user_id)}
                  onChange={(e) => createForm.setData("assigned_to_user_id", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                  <option value="">Select user</option>
                  {assignees.map((assignee) => (
                    <option key={assignee.id} value={assignee.id}>
                      {assignee.name} | {assignee.email}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Assign To Department</label>
                <select
                  value={String(createForm.data.assigned_department_id)}
                  onChange={(e) => createForm.setData("assigned_department_id", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                  <option value="">Select department</option>
                  {departments.map((department) => (
                    <option key={department.id} value={department.id}>
                      {department.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Related Project</label>
                <select
                  value={String(createForm.data.project_id)}
                  onChange={(e) => createForm.setData("project_id", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                  <option value="">No project</option>
                  {projects.map((project) => (
                    <option key={project.id} value={project.id}>
                      {project.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Related Program</label>
                <select
                  value={String(createForm.data.program_id)}
                  onChange={(e) => createForm.setData("program_id", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                  <option value="">No program</option>
                  {programs.map((program) => (
                    <option key={program.id} value={program.id}>
                      {program.title}
                    </option>
                  ))}
                </select>
              </div>
              {createForm.errors.assigned_to_user_id ? (
                <div className="md:col-span-2 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
                  {createForm.errors.assigned_to_user_id}
                </div>
              ) : null}
              <div className="md:col-span-2">
                <button
                  type="submit"
                  disabled={createForm.processing}
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                >
                  {createForm.processing ? "Creating..." : "Create Task"}
                </button>
              </div>
            </form>
          </section>
        ) : null}

        <section className="grid gap-3 md:grid-cols-5">
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Visible Tasks</div><div className="mt-2 text-2xl font-semibold">{summary.total}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Open</div><div className="mt-2 text-2xl font-semibold">{summary.open}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">In Progress</div><div className="mt-2 text-2xl font-semibold">{summary.in_progress}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Completed</div><div className="mt-2 text-2xl font-semibold">{summary.completed}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Overdue</div><div className="mt-2 text-2xl font-semibold">{summary.overdue}</div></div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Task Filters</h2>
          <form
            className="mt-4 grid gap-3 md:grid-cols-4"
            onSubmit={(e) => {
              e.preventDefault();
              router.get("/task-management/tasks", filterForm.data, { preserveState: true, preserveScroll: true });
            }}
          >
            <input value={filterForm.data.search} onChange={(e) => filterForm.setData("search", e.currentTarget.value)} placeholder="Search title or description" className="rounded-md border bg-background px-3 py-2 text-sm" />
            <select value={filterForm.data.status} onChange={(e) => filterForm.setData("status", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All statuses</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="blocked">Blocked</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <select value={filterForm.data.priority} onChange={(e) => filterForm.setData("priority", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <select value={filterForm.data.assignee_user_id} onChange={(e) => filterForm.setData("assignee_user_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All assignees</option>
              {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name}</option>)}
            </select>
            <select value={filterForm.data.department_id} onChange={(e) => filterForm.setData("department_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All departments</option>
              {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
            </select>
            <select value={filterForm.data.project_id} onChange={(e) => filterForm.setData("project_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All projects</option>
              {projects.map((project) => <option key={project.id} value={project.id}>{project.name}</option>)}
            </select>
            <select value={filterForm.data.program_id} onChange={(e) => filterForm.setData("program_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All programs</option>
              {programs.map((program) => <option key={program.id} value={program.id}>{program.title}</option>)}
            </select>
            <select value={filterForm.data.overdue} onChange={(e) => filterForm.setData("overdue", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All due states</option>
              <option value="1">Overdue only</option>
            </select>
            <div className="md:col-span-4 flex gap-2">
              <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Apply Filters</button>
              <button type="button" className="rounded-md border px-4 py-2 text-sm" onClick={() => router.get("/task-management/tasks", {}, { preserveState: false, preserveScroll: true })}>Reset</button>
            </div>
          </form>
        </section>

        <div className="space-y-4">
          {tasks.data.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">No tasks available.</section>
          ) : (
            tasks.data.map((task) => {
              const comments = Array.isArray(task.comments) ? task.comments : (task.comments.data ?? []);
              const history = Array.isArray(task.history) ? task.history : (task.history.data ?? []);

              return (
                <section key={task.id} className="rounded-xl border bg-card p-4 shadow-sm">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h2 className="text-base font-semibold">{task.title}</h2>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {task.priority.toUpperCase()} | {task.status.replaceAll("_", " ")} | Created by {task.creator_name ?? "-"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {task.context_type} | {task.project_name ?? task.program_title ?? "General operational task"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Assigned to {task.assignee_name ?? "Department queue"} {task.assigned_department_name ? `| ${task.assigned_department_name}` : ""}
                      </div>
                      {task.description ? <p className="mt-2 text-sm text-muted-foreground">{task.description}</p> : null}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <div className="text-sm">{task.due_date ? `Due ${task.due_date}` : "No due date"}</div>
                      <button type="button" onClick={() => setOpenTaskId(openTaskId === task.id ? null : task.id)} className="rounded-md border border-orange-500 px-3 py-1.5 text-sm text-orange-600 hover:bg-orange-500 hover:text-white">
                        {openTaskId === task.id ? "Hide Workflow" : "Open Workflow"}
                      </button>
                    </div>
                  </div>

                  {openTaskId === task.id ? (
                    <div className="mt-4 grid gap-4 lg:grid-cols-2">
                      <div className="space-y-4">
                        {task.can.update_status ? (
                          <form
                            className="rounded-lg border p-3"
                            onSubmit={(e) => {
                              e.preventDefault();
                              const formData = new FormData(e.currentTarget);
                              router.post(`/task-management/tasks/${task.id}/status`, {
                                status: formData.get("status"),
                                completion_notes: formData.get("completion_notes"),
                              }, { preserveScroll: true });
                            }}
                          >
                            <h3 className="text-sm font-semibold">Update Status</h3>
                            <div className="mt-3 grid gap-3">
                              <select name="status" defaultValue={task.status} className="rounded-md border bg-background px-3 py-2 text-sm">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="blocked">Blocked</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                              </select>
                              <textarea name="completion_notes" rows={3} defaultValue={task.completion_notes ?? ""} placeholder="Status notes or completion notes" className="rounded-md border bg-background px-3 py-2 text-sm" />
                              <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Save Status</button>
                            </div>
                          </form>
                        ) : null}

                        {task.can.reassign ? (
                          <form
                            className="rounded-lg border p-3"
                            onSubmit={(e) => {
                              e.preventDefault();
                              const formData = new FormData(e.currentTarget);
                              router.post(`/task-management/tasks/${task.id}/reassign`, {
                                assigned_to_user_id: formData.get("assigned_to_user_id"),
                                assigned_department_id: formData.get("assigned_department_id"),
                                reason: formData.get("reason"),
                              }, { preserveScroll: true });
                            }}
                          >
                            <h3 className="text-sm font-semibold">Reassign Task</h3>
                            <div className="mt-3 grid gap-3">
                              <select name="assigned_to_user_id" defaultValue={String(task.assigned_to_user_id ?? "")} className="rounded-md border bg-background px-3 py-2 text-sm">
                                <option value="">Department queue / no direct assignee</option>
                                {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name} | {assignee.email}</option>)}
                              </select>
                              <select name="assigned_department_id" defaultValue={String(task.assigned_department_id ?? "")} className="rounded-md border bg-background px-3 py-2 text-sm">
                                <option value="">No department queue</option>
                                {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
                              </select>
                              <textarea name="reason" rows={2} placeholder="Reason for reassignment" className="rounded-md border bg-background px-3 py-2 text-sm" />
                              <button type="submit" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Reassign</button>
                            </div>
                          </form>
                        ) : null}

                        {task.can.comment ? (
                          <form
                            className="rounded-lg border p-3"
                            onSubmit={(e) => {
                              e.preventDefault();
                              const formData = new FormData(e.currentTarget);
                              router.post(`/task-management/tasks/${task.id}/comment`, {
                                message: formData.get("message"),
                              }, { preserveScroll: true });
                              e.currentTarget.reset();
                            }}
                          >
                            <h3 className="text-sm font-semibold">Add Comment</h3>
                            <textarea name="message" rows={3} className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Post a workflow note or blocker update" />
                            <button type="submit" className="mt-3 rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Post Comment</button>
                          </form>
                        ) : null}
                      </div>

                      <div className="space-y-4">
                        <div className="rounded-lg border p-3">
                          <h3 className="text-sm font-semibold">Comments</h3>
                          <div className="mt-3 space-y-3">
                            {comments.length === 0 ? <p className="text-sm text-muted-foreground">No comments yet.</p> : comments.map((comment) => (
                              <div key={comment.id} className="rounded-md border p-3">
                                <div className="text-xs text-muted-foreground">{comment.user_name ?? "-"} | {comment.created_at ?? "-"}</div>
                                <div className="mt-1 text-sm">{comment.message}</div>
                              </div>
                            ))}
                          </div>
                        </div>
                        <div className="rounded-lg border p-3">
                          <h3 className="text-sm font-semibold">Task History</h3>
                          <div className="mt-3 space-y-3">
                            {history.length === 0 ? <p className="text-sm text-muted-foreground">No history recorded yet.</p> : history.map((item) => (
                              <div key={item.id} className="rounded-md border p-3">
                                <div className="text-xs text-muted-foreground">{item.actor_name ?? "System"} | {item.created_at ?? "-"}</div>
                                <div className="mt-1 text-sm">{item.summary}</div>
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>
                    </div>
                  ) : null}
                </section>
              );
            })
          )}
        </div>
      </div>
    </AppLayout>
  );
}
