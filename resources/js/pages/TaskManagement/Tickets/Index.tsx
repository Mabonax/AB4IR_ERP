import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { taskManagementNavItems } from "@/config/domain-nav/task-management";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type TicketReply = {
  id: number;
  user_name: string | null;
  message: string;
  is_resolution: boolean;
  created_at: string | null;
};

type TicketRow = {
  id: number;
  title: string;
  description: string;
  status: "open" | "assigned" | "in_progress" | "resolved" | "closed";
  priority: "low" | "medium" | "high" | "urgent";
  requester_name: string | null;
  requester_department_name: string | null;
  assigned_to_user_id: number | null;
  assignee_name: string | null;
  assigned_department_name: string | null;
  project_name: string | null;
  program_title: string | null;
  asset_id?: number | null;
  asset_name?: string | null;
  asset_code?: string | null;
  asset_category_name?: string | null;
  resolution_summary: string | null;
  first_responded_at: string | null;
  closed_at: string | null;
  first_response_hours: number | null;
  age_hours: number;
  sla_target_hours: number;
  is_overdue: boolean;
  sla_status: "within_sla" | "overdue" | "resolved";
  replies: { data?: TicketReply[] } | TicketReply[];
  can: { assign: boolean; reply: boolean; resolve: boolean; close: boolean; reopen: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Task Management", href: "/task-management/tasks" },
  { title: "Support Tickets", href: "/task-management/tickets" },
];

export default function TaskManagementTicketsIndex({
  tickets,
  technicalResponders,
  requesters,
  projects,
  programs,
  reportableAssets,
  filters,
  summary,
  can,
}: {
  tickets: { data: TicketRow[] };
  technicalResponders: Array<{ id: number; name: string; department_name?: string | null }>;
  requesters: Array<{ id: number; name: string }>;
  projects: Array<{ id: number; name: string }>;
  programs: Array<{ id: number; title: string }>;
  reportableAssets: Array<{ id: number; name: string; asset_code: string | null; status: string }>;
  filters: Record<string, string>;
  summary: { total: number; open: number; in_progress: number; resolved: number; closed: number; overdue: number };
  can: { create: boolean };
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [openTicketId, setOpenTicketId] = useState<number | null>(null);

  const createForm = useForm({
    title: "",
    description: "",
    priority: "medium",
    project_id: "",
    program_id: "",
    asset_id: "",
  });
  const filterForm = useForm({
    search: filters.search ?? "",
    status: filters.status ?? "",
    priority: filters.priority ?? "",
    assigned_to_user_id: filters.assigned_to_user_id ?? "",
    requester_user_id: filters.requester_user_id ?? "",
    project_id: filters.project_id ?? "",
    program_id: filters.program_id ?? "",
    asset_id: filters.asset_id ?? "",
    overdue: filters.overdue ?? "",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Support Tickets" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Technical Support Tickets</h1>
          <DomainNav items={taskManagementNavItems} />
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {flash.warning ? (
          <div className="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800">
            {String(flash.warning)}
          </div>
        ) : null}

        {can.create ? (
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Log Technical Ticket</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Any staff member can log an issue. The technical department can assign, respond, and resolve it.
            </p>
            <form
              className="mt-4 grid gap-3 md:grid-cols-2"
              onSubmit={(e) => {
                e.preventDefault();
                createForm.post("/task-management/tickets", {
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
              </div>
              <div className="md:col-span-2">
                <label className="mb-1 block text-sm font-medium">Issue Description</label>
                <textarea
                  value={createForm.data.description}
                  onChange={(e) => createForm.setData("description", e.currentTarget.value)}
                  rows={4}
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
                <label className="mb-1 block text-sm font-medium">Related Asset</label>
                <select
                  value={String(createForm.data.asset_id)}
                  onChange={(e) => createForm.setData("asset_id", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                  <option value="">No asset</option>
                  {reportableAssets.map((asset) => (
                    <option key={asset.id} value={asset.id}>
                      {(asset.asset_code ?? "NO-CODE") + " | " + asset.name}
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
              <div className="md:col-span-2">
                <button
                  type="submit"
                  disabled={createForm.processing}
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                >
                  {createForm.processing ? "Logging..." : "Log Ticket"}
                </button>
              </div>
            </form>
          </section>
        ) : null}

        <section className="grid gap-3 md:grid-cols-6">
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Visible Tickets</div><div className="mt-2 text-2xl font-semibold">{summary.total}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Open</div><div className="mt-2 text-2xl font-semibold">{summary.open}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">In Progress</div><div className="mt-2 text-2xl font-semibold">{summary.in_progress}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Resolved</div><div className="mt-2 text-2xl font-semibold">{summary.resolved}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Closed</div><div className="mt-2 text-2xl font-semibold">{summary.closed}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">SLA Overdue</div><div className="mt-2 text-2xl font-semibold">{summary.overdue}</div></div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Ticket Filters</h2>
          <form
            className="mt-4 grid gap-3 md:grid-cols-4"
            onSubmit={(e) => {
              e.preventDefault();
              router.get("/task-management/tickets", filterForm.data, { preserveState: true, preserveScroll: true });
            }}
          >
            <input value={filterForm.data.search} onChange={(e) => filterForm.setData("search", e.currentTarget.value)} placeholder="Search title or description" className="rounded-md border bg-background px-3 py-2 text-sm" />
            <select value={filterForm.data.status} onChange={(e) => filterForm.setData("status", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All statuses</option>
              <option value="open">Open</option>
              <option value="assigned">Assigned</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
            <select value={filterForm.data.priority} onChange={(e) => filterForm.setData("priority", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <select value={filterForm.data.assigned_to_user_id} onChange={(e) => filterForm.setData("assigned_to_user_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All responders</option>
              {technicalResponders.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}
            </select>
            <select value={filterForm.data.requester_user_id} onChange={(e) => filterForm.setData("requester_user_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All requesters</option>
              {requesters.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}
            </select>
            <select value={filterForm.data.project_id} onChange={(e) => filterForm.setData("project_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All projects</option>
              {projects.map((project) => <option key={project.id} value={project.id}>{project.name}</option>)}
            </select>
            <select value={filterForm.data.program_id} onChange={(e) => filterForm.setData("program_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All programs</option>
              {programs.map((program) => <option key={program.id} value={program.id}>{program.title}</option>)}
            </select>
            <select value={filterForm.data.asset_id} onChange={(e) => filterForm.setData("asset_id", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All assets</option>
              {reportableAssets.map((asset) => <option key={asset.id} value={asset.id}>{(asset.asset_code ?? "NO-CODE") + " | " + asset.name}</option>)}
            </select>
            <select value={filterForm.data.overdue} onChange={(e) => filterForm.setData("overdue", e.currentTarget.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
              <option value="">All SLA states</option>
              <option value="1">Overdue only</option>
            </select>
            <div className="md:col-span-4 flex gap-2">
              <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Apply Filters</button>
              <button type="button" className="rounded-md border px-4 py-2 text-sm" onClick={() => router.get("/task-management/tickets", {}, { preserveState: false, preserveScroll: true })}>Reset</button>
            </div>
          </form>
        </section>

        <div className="space-y-4">
          {tickets.data.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">
              No support tickets available.
            </section>
          ) : (
            tickets.data.map((ticket) => {
              const replies = Array.isArray(ticket.replies)
                ? ticket.replies
                : (ticket.replies.data ?? []);

              return (
                <section key={ticket.id} className="rounded-xl border bg-card p-4 shadow-sm">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h2 className="text-base font-semibold">{ticket.title}</h2>
                      <p className="mt-1 text-sm text-muted-foreground">{ticket.description}</p>
                      <div className="mt-2 text-xs text-muted-foreground">
                        {ticket.priority.toUpperCase()} | {ticket.status.replaceAll("_", " ")} | Requested by {ticket.requester_name ?? "-"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {ticket.requester_department_name ?? "No department"} | Assigned to {ticket.assignee_name ?? ticket.assigned_department_name ?? "Technical queue"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        SLA: {ticket.sla_status.replaceAll("_", " ")} | Age {ticket.age_hours}h of {ticket.sla_target_hours}h target
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        First response: {ticket.first_response_hours !== null ? `${ticket.first_response_hours}h` : "Pending"}
                        {ticket.closed_at ? ` | Closed ${ticket.closed_at}` : ""}
                      </div>
                      {(ticket.project_name || ticket.program_title) ? (
                        <div className="mt-1 text-xs text-muted-foreground">
                          Context: {ticket.project_name ?? ticket.program_title}
                        </div>
                      ) : null}
                      {ticket.asset_id ? (
                        <div className="mt-1 text-xs text-muted-foreground">
                          Asset: {(ticket.asset_code ?? "-") + " | " + (ticket.asset_name ?? "-")}
                          {ticket.asset_category_name ? ` | ${ticket.asset_category_name}` : ""}
                        </div>
                      ) : null}
                    </div>
                    <button
                      type="button"
                      onClick={() => setOpenTicketId(openTicketId === ticket.id ? null : ticket.id)}
                      className="rounded-md border border-orange-500 px-3 py-1.5 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
                    >
                      {openTicketId === ticket.id ? "Hide Workflow" : "Open Workflow"}
                    </button>
                  </div>

                  {openTicketId === ticket.id ? (
                    <div className="mt-4 space-y-4">
                      {ticket.can.assign ? (
                        <form
                          className="flex flex-wrap items-end gap-3 rounded-lg border p-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const data = new FormData(e.currentTarget);
                            router.post(`/task-management/tickets/${ticket.id}/assign`, {
                              assigned_to_user_id: data.get("assigned_to_user_id"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <div className="min-w-[260px] flex-1">
                            <label className="mb-1 block text-sm font-medium">Assign Responder</label>
                            <select
                              name="assigned_to_user_id"
                              defaultValue={String(ticket.assigned_to_user_id ?? technicalResponders[0]?.id ?? "")}
                              className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                              {technicalResponders.map((user) => (
                                <option key={user.id} value={user.id}>
                                  {user.name} {user.department_name ? `| ${user.department_name}` : ""}
                                </option>
                              ))}
                            </select>
                          </div>
                          <button
                            type="submit"
                            className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900"
                          >
                            Assign
                          </button>
                        </form>
                      ) : null}

                      <div className="rounded-lg border p-3">
                        <h3 className="text-sm font-semibold">Ticket Conversation</h3>
                        <div className="mt-3 space-y-3">
                          {replies.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No responses yet.</p>
                          ) : (
                            replies.map((reply) => (
                              <div key={reply.id} className="rounded-md border p-3">
                                <div className="text-xs text-muted-foreground">
                                  {reply.user_name ?? "-"} | {reply.created_at ?? "-"} {reply.is_resolution ? "| Resolution" : ""}
                                </div>
                                <div className="mt-1 text-sm">{reply.message}</div>
                              </div>
                            ))
                          )}
                        </div>
                      </div>

                      {ticket.can.reply ? (
                        <form
                          className="rounded-lg border p-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const data = new FormData(e.currentTarget);
                            router.post(`/task-management/tickets/${ticket.id}/reply`, {
                              message: data.get("message"),
                            }, { preserveScroll: true });
                            e.currentTarget.reset();
                          }}
                        >
                          <label className="mb-1 block text-sm font-medium">Post Response</label>
                          <textarea
                            name="message"
                            rows={3}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          <button
                            type="submit"
                            className="mt-3 rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                          >
                            Reply
                          </button>
                        </form>
                      ) : null}

                      {ticket.can.resolve ? (
                        <form
                          className="rounded-lg border p-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const data = new FormData(e.currentTarget);
                            router.post(`/task-management/tickets/${ticket.id}/resolve`, {
                              resolution_summary: data.get("resolution_summary"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <label className="mb-1 block text-sm font-medium">Resolution Summary</label>
                          <textarea
                            name="resolution_summary"
                            defaultValue={ticket.resolution_summary ?? ""}
                            rows={3}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          <button
                            type="submit"
                            className="mt-3 rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
                          >
                            Resolve Ticket
                          </button>
                        </form>
                      ) : null}

                      {ticket.can.close ? (
                        <form
                          className="rounded-lg border p-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const data = new FormData(e.currentTarget);
                            router.post(`/task-management/tickets/${ticket.id}/close`, {
                              closing_notes: data.get("closing_notes"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <label className="mb-1 block text-sm font-medium">Close Ticket</label>
                          <textarea
                            name="closing_notes"
                            rows={2}
                            placeholder="Optional closure note or requester confirmation"
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          <button
                            type="submit"
                            className="mt-3 rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900"
                          >
                            Close Ticket
                          </button>
                        </form>
                      ) : null}

                      {ticket.can.reopen ? (
                        <form
                          className="rounded-lg border p-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const data = new FormData(e.currentTarget);
                            router.post(`/task-management/tickets/${ticket.id}/reopen`, {
                              reason: data.get("reason"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <label className="mb-1 block text-sm font-medium">Reopen Ticket</label>
                          <textarea
                            name="reason"
                            rows={2}
                            placeholder="Describe why the issue is still open"
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          <button
                            type="submit"
                            className="mt-3 rounded-md bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700"
                          >
                            Reopen Ticket
                          </button>
                        </form>
                      ) : null}
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
