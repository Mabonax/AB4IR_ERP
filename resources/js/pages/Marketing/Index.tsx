import { Head, Link, router, useForm, usePage } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type JobRow = {
  id: number;
  title: string;
  brief: string | null;
  job_type: string;
  status: string;
  priority: "low" | "medium" | "high" | "urgent";
  due_date: string | null;
  event_name: string | null;
  creator_name: string | null;
  assignee_name: string | null;
  assigned_department_name: string | null;
  approval_notes: string | null;
  transaction_state: "open" | "closed";
  transaction_closed_at: string | null;
  closed_by_name: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Jobs", href: "/marketing/jobs" },
];

const statusBadgeClass = (status: string) => {
  switch (status) {
    case "approved":
      return "border-green-200 bg-green-50 text-green-700";
    case "pending_approval":
      return "border-blue-200 bg-blue-50 text-blue-700";
    case "changes_requested":
      return "border-amber-200 bg-amber-50 text-amber-700";
    case "blocked":
      return "border-rose-200 bg-rose-50 text-rose-700";
    case "cancelled":
      return "border-slate-200 bg-slate-100 text-slate-700";
    case "in_progress":
      return "border-indigo-200 bg-indigo-50 text-indigo-700";
    default:
      return "border-slate-200 bg-slate-50 text-slate-700";
  }
};

export default function MarketingIndex({
  jobs,
  events,
  assignees,
  filters,
  summary,
  can,
}: {
  jobs: { data: JobRow[] };
  events: Array<{ id: number; title: string }>;
  assignees: Array<{ id: number; name: string; email: string }>;
  filters: Record<string, string>;
  summary: { total: number; open: number; in_progress: number; pending_approval: number; changes_requested: number; approved: number };
  can: { create: boolean };
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const filterForm = useForm({
    search: filters.search ?? "",
    status: filters.status ?? "",
    priority: filters.priority ?? "",
    job_type: filters.job_type ?? "",
    event_id: filters.event_id ?? "",
    assignee_user_id: filters.assignee_user_id ?? "",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Marketing Jobs" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Marketing Domain</h1>
            <p className="text-sm text-muted-foreground">
              Review created marketing jobs and open each workflow file for delivery, approval, amendments, documents, and history.
            </p>
          </div>
          {can.create ? (
            <Link href="/marketing/jobs/create" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
              Create Marketing Job
            </Link>
          ) : null}
          <DomainNav items={marketingNavItems} />
        </div>

        {flash.success ? <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">{String(flash.success)}</div> : null}
        {flash.error ? <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">{String(flash.error)}</div> : null}

        <section className="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Visible Jobs</div><div className="mt-2 text-2xl font-semibold">{summary.total}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Open</div><div className="mt-2 text-2xl font-semibold">{summary.open}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">In Progress</div><div className="mt-2 text-2xl font-semibold">{summary.in_progress}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Awaiting Approval</div><div className="mt-2 text-2xl font-semibold">{summary.pending_approval}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Amendments</div><div className="mt-2 text-2xl font-semibold">{summary.changes_requested}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Approved</div><div className="mt-2 text-2xl font-semibold">{summary.approved}</div></div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Filters</h2>
          <form
            className="mt-4 grid gap-3 md:grid-cols-3"
            onSubmit={(e) => {
              e.preventDefault();
              router.get("/marketing/jobs", filterForm.data, { preserveState: true, preserveScroll: true });
            }}
          >
            <input value={filterForm.data.search} onChange={(e) => filterForm.setData("search", e.currentTarget.value)} placeholder="Search title or brief" className="rounded-md border bg-background px-3 py-2 text-sm" />
            <select value={filterForm.data.status} onChange={(e) => filterForm.setData("status", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All statuses</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="blocked">Blocked</option>
              <option value="pending_approval">Awaiting Approval</option>
              <option value="changes_requested">Amendments Requested</option>
              <option value="approved">Approved</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <select value={filterForm.data.priority} onChange={(e) => filterForm.setData("priority", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <select value={filterForm.data.job_type} onChange={(e) => filterForm.setData("job_type", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All work types</option>
              <option value="graphic_design">Graphic design</option>
              <option value="social_media">Social media</option>
              <option value="content_plan">Content plan</option>
              <option value="letter_communication">Letter / communication</option>
              <option value="email_signature">Email signature</option>
              <option value="other">Other</option>
            </select>
            <select value={filterForm.data.event_id} onChange={(e) => filterForm.setData("event_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All events</option>
              {events.map((event) => <option key={event.id} value={event.id}>{event.title}</option>)}
            </select>
            <select value={filterForm.data.assignee_user_id} onChange={(e) => filterForm.setData("assignee_user_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All assignees</option>
              {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name}</option>)}
            </select>
            <div className="md:col-span-3 flex gap-2">
              <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Apply Filters</button>
              <button type="button" className="rounded-md border px-4 py-2 text-sm" onClick={() => router.get("/marketing/jobs", {}, { preserveState: false, preserveScroll: true })}>Reset</button>
            </div>
          </form>
        </section>

        <div className="space-y-4">
          {jobs.data.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">No marketing work items available.</section>
          ) : jobs.data.map((job) => (
            <section key={job.id} className="rounded-xl border bg-card p-4 shadow-sm">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <h2 className="text-lg font-semibold">{job.title}</h2>
                    <span className={`rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${statusBadgeClass(job.status)}`}>
                      {job.status.replaceAll("_", " ")}
                    </span>
                    <span className="rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 text-xs font-medium capitalize text-orange-700">
                      Transaction {job.transaction_state}
                    </span>
                  </div>
                  <div className="text-sm text-muted-foreground">{job.job_type.replaceAll("_", " ")} | Priority {job.priority.toUpperCase()} | Created by {job.creator_name ?? "-"}</div>
                  <div className="text-xs text-muted-foreground">
                    {job.assignee_name ?? job.assigned_department_name ?? "Marketing queue"}{job.event_name ? ` | ${job.event_name}` : ""}{job.due_date ? ` | Due ${job.due_date}` : ""}
                  </div>
                  {job.approval_notes ? (
                    <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                      <span className="font-medium">Latest approval note:</span> {job.approval_notes}
                    </div>
                  ) : null}
                  {job.transaction_closed_at ? (
                    <div className="text-xs text-muted-foreground">Closed at {job.transaction_closed_at} by {job.closed_by_name ?? "manager"}.</div>
                  ) : null}
                </div>
                <button type="button" onClick={() => router.visit(`/marketing/jobs/${job.id}`)} className="rounded-md border border-orange-500 px-3 py-1.5 text-sm text-orange-600 hover:bg-orange-500 hover:text-white">
                  Open Workflow
                </button>
              </div>
            </section>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
