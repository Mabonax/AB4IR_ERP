import { Head, Link, useForm } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Requests", href: "/marketing/requests" },
  { title: "Create", href: "/marketing/requests/create" },
];

export default function MarketingRequestCreate({
  events,
  projects,
  programs,
  departments,
  approvers,
  workTasks,
  assignees,
  deliverableTypes,
  units,
  selectedWorkTaskId,
}: {
  events: Array<{ id: number; title: string }>;
  projects: Array<{ id: number; name: string }>;
  programs: Array<{ id: number; title: string }>;
  departments: Array<{ id: number; name: string }>;
  approvers: Array<{ id: number; name: string; email: string }>;
  workTasks: Array<{ id: number; title: string; status: string; assignee?: { name: string | null } | null; assigned_department?: { name: string | null } | null }>;
  assignees: Array<{ id: number; name: string; email: string }>;
  deliverableTypes: string[];
  units: string[];
  selectedWorkTaskId: number | null;
}) {
  const initialWorkTaskId = selectedWorkTaskId ? String(selectedWorkTaskId) : "";
  const form = useForm({
    title: "",
    objective: "",
    description: "",
    target_audience: "",
    campaign_goal: "",
    approver_user_id: "",
    project_id: "",
    program_id: "",
    event_id: "",
    owner_department_id: "",
    priority: "medium",
    due_date: "",
    status: "submitted",
    work_task_id: initialWorkTaskId,
    work_package: {
      assigned_unit: units[0] ?? "graphics",
      operational_owner_user_id: "",
      planned_start_date: "",
      planned_end_date: "",
    },
    deliverables: [
      {
        title: "",
        deliverable_type: deliverableTypes[0] ?? "poster",
        assigned_to_user_id: "",
        assigned_unit: units[0] ?? "graphics",
        due_date: "",
        review_notes: "",
        work_task_id: initialWorkTaskId,
      },
    ],
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Register Marketing Operation" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Register Marketing Operation</h1>
            <p className="text-sm text-muted-foreground">
              Register the marketing brief and attach it to the Task Management work item that owns assignment, proof, review, and closure.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        <form
          className="space-y-5"
          onSubmit={(event) => {
            event.preventDefault();
            form.post("/marketing/requests");
          }}
        >
          <section className="grid gap-4 xl:grid-cols-2">
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Business Request</h2>
              <div className="mt-4 grid gap-3">
                <input className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.title} onChange={(event) => form.setData("title", event.currentTarget.value)} placeholder="Request title" />
                <input className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.objective} onChange={(event) => form.setData("objective", event.currentTarget.value)} placeholder="Objective" />
                <textarea className="rounded-md border bg-background px-3 py-2 text-sm" rows={4} value={form.data.description} onChange={(event) => form.setData("description", event.currentTarget.value)} placeholder="Description / brief" />
                <textarea className="rounded-md border bg-background px-3 py-2 text-sm" rows={3} value={form.data.target_audience} onChange={(event) => form.setData("target_audience", event.currentTarget.value)} placeholder="Target audience" />
                <textarea className="rounded-md border bg-background px-3 py-2 text-sm" rows={3} value={form.data.campaign_goal} onChange={(event) => form.setData("campaign_goal", event.currentTarget.value)} placeholder="Campaign goal" />
              </div>
            </div>

            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Context And Routing</h2>
              <div className="mt-4 grid gap-3 md:grid-cols-2">
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.approver_user_id} onChange={(event) => form.setData("approver_user_id", event.currentTarget.value)}>
                  <option value="">No approver selected</option>
                  {approvers.map((approver) => <option key={approver.id} value={approver.id}>{approver.name}</option>)}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.work_task_id} onChange={(event) => form.setData("work_task_id", event.currentTarget.value)}>
                  <option value="">No linked task</option>
                  {workTasks.map((task) => (
                    <option key={task.id} value={task.id}>
                      #{task.id} {task.title} ({task.status.replaceAll("_", " ")})
                    </option>
                  ))}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.owner_department_id} onChange={(event) => form.setData("owner_department_id", event.currentTarget.value)}>
                  <option value="">Owner department</option>
                  {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.project_id} onChange={(event) => form.setData("project_id", event.currentTarget.value)}>
                  <option value="">Standalone or no project</option>
                  {projects.map((project) => <option key={project.id} value={project.id}>{project.name}</option>)}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.program_id} onChange={(event) => form.setData("program_id", event.currentTarget.value)}>
                  <option value="">No program</option>
                  {programs.map((program) => <option key={program.id} value={program.id}>{program.title}</option>)}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.event_id} onChange={(event) => form.setData("event_id", event.currentTarget.value)}>
                  <option value="">No event</option>
                  {events.map((eventItem) => <option key={eventItem.id} value={eventItem.id}>{eventItem.title}</option>)}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.priority} onChange={(event) => form.setData("priority", event.currentTarget.value)}>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
                <input type="date" className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.due_date} onChange={(event) => form.setData("due_date", event.currentTarget.value)} />
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.work_package.assigned_unit} onChange={(event) => form.setData("work_package", { ...form.data.work_package, assigned_unit: event.currentTarget.value })}>
                  {units.map((unit) => <option key={unit} value={unit}>{unit.replaceAll("_", " ")}</option>)}
                </select>
                <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.work_package.operational_owner_user_id} onChange={(event) => form.setData("work_package", { ...form.data.work_package, operational_owner_user_id: event.currentTarget.value })}>
                  <option value="">No operational owner</option>
                  {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name}</option>)}
                </select>
                <input type="date" className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.work_package.planned_start_date} onChange={(event) => form.setData("work_package", { ...form.data.work_package, planned_start_date: event.currentTarget.value })} />
                <input type="date" className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.work_package.planned_end_date} onChange={(event) => form.setData("work_package", { ...form.data.work_package, planned_end_date: event.currentTarget.value })} />
              </div>
              <div className="mt-4 rounded-lg border border-dashed border-orange-200 bg-orange-50 p-3 text-xs text-orange-800">
                Use <Link href="/task-management/tasks" className="font-semibold underline underline-offset-2">Task Management</Link> for the actual work request and assignee workflow. This page should only register campaign, content, publication, and asset governance details.
              </div>
            </div>
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="flex items-center justify-between">
              <h2 className="text-base font-semibold">Deliverables</h2>
              <button
                type="button"
                className="rounded-md border px-3 py-2 text-sm"
                onClick={() => form.setData("deliverables", [
                  ...form.data.deliverables,
                  {
                    title: "",
                    deliverable_type: deliverableTypes[0] ?? "poster",
                    assigned_to_user_id: "",
                    assigned_unit: units[0] ?? "graphics",
                    due_date: "",
                    review_notes: "",
                    work_task_id: "",
                  },
                ])}
              >
                Add Deliverable
              </button>
            </div>
            <div className="mt-4 space-y-4">
              {form.data.deliverables.map((deliverable, index) => (
                <div key={index} className="grid gap-3 rounded-lg border p-4 md:grid-cols-2 xl:grid-cols-3">
                  <input className="rounded-md border bg-background px-3 py-2 text-sm" value={deliverable.title} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], title: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }} placeholder="Deliverable title" />
                  <select className="rounded-md border bg-background px-3 py-2 text-sm" value={deliverable.deliverable_type} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], deliverable_type: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }}>
                    {deliverableTypes.map((type) => <option key={type} value={type}>{type.replaceAll("_", " ")}</option>)}
                  </select>
                  <select className="rounded-md border bg-background px-3 py-2 text-sm" value={deliverable.assigned_unit} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], assigned_unit: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }}>
                    {units.map((unit) => <option key={unit} value={unit}>{unit.replaceAll("_", " ")}</option>)}
                  </select>
                  <select className="rounded-md border bg-background px-3 py-2 text-sm" value={deliverable.assigned_to_user_id} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], assigned_to_user_id: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }}>
                    <option value="">No direct assignee</option>
                    {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name}</option>)}
                  </select>
                  <input type="date" className="rounded-md border bg-background px-3 py-2 text-sm" value={deliverable.due_date} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], due_date: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }} />
                  <select className="rounded-md border bg-background px-3 py-2 text-sm" value={deliverable.work_task_id} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], work_task_id: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }}>
                    <option value="">Use request task link</option>
                    {workTasks.map((task) => (
                      <option key={task.id} value={task.id}>
                        #{task.id} {task.title}
                      </option>
                    ))}
                  </select>
                  <textarea className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2 xl:col-span-3" rows={3} value={deliverable.review_notes} onChange={(event) => {
                    const next = [...form.data.deliverables];
                    next[index] = { ...next[index], review_notes: event.currentTarget.value };
                    form.setData("deliverables", next);
                  }} placeholder="Routing or review notes" />
                </div>
              ))}
            </div>
            <div className="mt-4">
              <button type="submit" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                Register Marketing Operation
              </button>
            </div>
          </section>
        </form>
      </div>
    </AppLayout>
  );
}
