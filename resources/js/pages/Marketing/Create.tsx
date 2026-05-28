import { Head, useForm, usePage } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Jobs", href: "/marketing/jobs" },
  { title: "Create", href: "/marketing/jobs/create" },
];

export default function MarketingCreate({
  events,
  assignees,
  departments,
}: {
  events: Array<{ id: number; title: string }>;
  assignees: Array<{ id: number; name: string; email: string }>;
  departments: Array<{ id: number; name: string }>;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const createForm = useForm({
    title: "",
    brief: "",
    job_type: "graphic_design",
    priority: "medium",
    due_date: "",
    event_id: "",
    assigned_to_user_id: "",
    assigned_department_id: "",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Create Marketing Job" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Create Marketing Work Item</h1>
            <p className="text-sm text-muted-foreground">
              Open a dedicated marketing transaction here for design, social content, content planning, letters, signatures, and communications.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        {flash.success ? <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">{String(flash.success)}</div> : null}
        {flash.error ? <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">{String(flash.error)}</div> : null}

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <form
            className="grid gap-3 md:grid-cols-2"
            onSubmit={(e) => {
              e.preventDefault();
              createForm.post("/marketing/jobs", {
                preserveScroll: true,
              });
            }}
          >
            <div className="md:col-span-2">
              <label className="mb-1 block text-sm font-medium">Title</label>
              <input value={createForm.data.title} onChange={(e) => createForm.setData("title", e.currentTarget.value)} className="w-full rounded-md border bg-background px-3 py-2 text-sm" />
              {createForm.errors.title ? <p className="mt-1 text-sm text-red-600">{createForm.errors.title}</p> : null}
            </div>
            <div className="md:col-span-2">
              <label className="mb-1 block text-sm font-medium">Brief</label>
              <textarea value={createForm.data.brief} onChange={(e) => createForm.setData("brief", e.currentTarget.value)} rows={5} className="w-full rounded-md border bg-background px-3 py-2 text-sm" />
              {createForm.errors.brief ? <p className="mt-1 text-sm text-red-600">{createForm.errors.brief}</p> : null}
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Work Type</label>
              <select value={createForm.data.job_type} onChange={(e) => createForm.setData("job_type", e.currentTarget.value)} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                <option value="graphic_design">Graphic design</option>
                <option value="social_media">Social media</option>
                <option value="content_plan">Content plan</option>
                <option value="letter_communication">Letter / communication</option>
                <option value="email_signature">Email signature</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Priority</label>
              <select value={createForm.data.priority} onChange={(e) => createForm.setData("priority", e.currentTarget.value as "low" | "medium" | "high" | "urgent")} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Due Date</label>
              <input type="date" value={createForm.data.due_date} onChange={(e) => createForm.setData("due_date", e.currentTarget.value)} className="w-full rounded-md border bg-background px-3 py-2 text-sm" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Related Event</label>
              <select value={createForm.data.event_id} onChange={(e) => createForm.setData("event_id", e.currentTarget.value)} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No event</option>
                {events.map((event) => <option key={event.id} value={event.id}>{event.title}</option>)}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Assign To User</label>
              <select value={createForm.data.assigned_to_user_id} onChange={(e) => createForm.setData("assigned_to_user_id", e.currentTarget.value)} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">Marketing queue / no direct assignee</option>
                {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name} | {assignee.email}</option>)}
              </select>
              {createForm.errors.assigned_to_user_id ? <p className="mt-1 text-sm text-red-600">{createForm.errors.assigned_to_user_id}</p> : null}
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Assigned Department</label>
              <select value={createForm.data.assigned_department_id} onChange={(e) => createForm.setData("assigned_department_id", e.currentTarget.value)} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No department queue</option>
                {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
              </select>
              {createForm.errors.assigned_department_id ? <p className="mt-1 text-sm text-red-600">{createForm.errors.assigned_department_id}</p> : null}
            </div>
            <div className="md:col-span-2">
              <button type="submit" disabled={createForm.processing} className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">
                {createForm.processing ? "Creating..." : "Create Marketing Work"}
              </button>
            </div>
          </form>
        </section>
      </div>
    </AppLayout>
  );
}
