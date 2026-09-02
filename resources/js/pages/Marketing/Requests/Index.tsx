import { Head, Link, router, useForm } from "@inertiajs/react";
import { CalendarDays, Download, Eye, FileStack, Filter, MoreHorizontal, Pencil, Plus, Search, Star, Workflow } from "lucide-react";
import { type ComponentType } from "react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Requests", href: "/marketing/requests" },
];

type RequestRow = {
  id: number;
  title: string;
  objective: string | null;
  status: string;
  priority: string;
  due_date: string | null;
  requester_name: string | null;
  approver_name: string | null;
  event_name: string | null;
  project_name: string | null;
  source_marketing_job_id: number | null;
  work_task_id: number | null;
  work_task_title: string | null;
  work_task_status: string | null;
  deliverables?: Array<{ title: string; status: string; assigned_unit: string }>;
};

type MetricCard = {
  label: string;
  value: number;
  caption: string;
  Icon: ComponentType<{ className?: string }>;
  tone: string;
};

const statusTone = (status: string) => {
  if (status.includes("progress") || status.includes("production")) return "bg-violet-50 text-violet-700 ring-violet-200";
  if (status.includes("approval") || status.includes("review")) return "bg-amber-50 text-amber-700 ring-amber-200";
  if (status.includes("complete")) return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  return "bg-blue-50 text-blue-700 ring-blue-200";
};

const priorityTone = (priority: string) => {
  if (priority === "high" || priority === "urgent") return "bg-rose-50 text-rose-700 ring-rose-200";
  if (priority === "medium") return "bg-orange-50 text-orange-700 ring-orange-200";
  return "bg-emerald-50 text-emerald-700 ring-emerald-200";
};

const typeFor = (request: RequestRow) => {
  const unit = request.deliverables?.[0]?.assigned_unit ?? request.objective ?? "Campaign";
  if (/graphic|poster|design/i.test(unit)) return "Graphics";
  if (/digital|web|site/i.test(unit)) return "Digital";
  if (/print|brochure/i.test(unit)) return "Print";
  return "Campaign";
};

const requestCode = (request: RequestRow) => `REQ-2026-${String(request.id).padStart(4, "0")}`;

export default function MarketingRequestsIndex({
  requests,
  filters,
  can,
}: {
  requests: { data: RequestRow[] };
  filters: Record<string, string>;
  can: { create: boolean };
}) {
  const rows = requests.data;
  const form = useForm({
    search: filters.search ?? "",
    status: filters.status ?? "",
    priority: filters.priority ?? "",
  });

  const submitted = rows.filter((row) => row.status === "submitted").length;
  const inProgress = rows.filter((row) => ["planned", "in_production", "in_review"].includes(row.status)).length;
  const pending = rows.filter((row) => row.status.includes("review") || row.status.includes("approval")).length;
  const completed = rows.filter((row) => row.status === "completed").length;
  const metrics: MetricCard[] = [
    { label: "Total Requests", value: rows.length, caption: "All time", Icon: FileStack, tone: "bg-rose-50 text-rose-600" },
    { label: "Submitted", value: submitted, caption: "Awaiting review", Icon: Workflow, tone: "bg-orange-50 text-orange-600" },
    { label: "In Progress", value: inProgress, caption: "In workflow", Icon: FileStack, tone: "bg-violet-50 text-violet-600" },
    { label: "Pending Approval", value: pending, caption: "Manager review", Icon: Star, tone: "bg-amber-50 text-amber-600" },
    { label: "Completed", value: completed, caption: "This month", Icon: FileStack, tone: "bg-emerald-50 text-emerald-600" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Marketing Requests" />

      <div className="space-y-5 p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-slate-950">Marketing Requests</h1>
            <p className="mt-1 text-sm text-slate-500">Track and manage all marketing requests from intake to completion.</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            {can.create ? (
              <Link href="/marketing/requests/create" className="inline-flex h-10 items-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <Plus className="h-4 w-4" />
                New Request
              </Link>
            ) : null}
            <button className="inline-flex h-10 items-center gap-2 rounded-lg border px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"><Download className="h-4 w-4" />Export</button>
            <button className="inline-flex h-10 items-center gap-2 rounded-lg border px-4 text-sm font-medium text-slate-700 hover:bg-slate-50"><Filter className="h-4 w-4" />Filters</button>
          </div>
        </div>

        <DomainNav items={marketingNavItems} />

        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
          {metrics.map(({ label, value, caption, Icon, tone }) => (
            <div key={label} className="rounded-xl border bg-white p-5 shadow-sm">
              <div className="flex items-center gap-4">
                <span className={`flex size-12 items-center justify-center rounded-xl ${tone}`}>
                  <Icon className="h-6 w-6" />
                </span>
                <div>
                  <div className="text-sm text-slate-600">{label}</div>
                  <div className="text-2xl font-semibold text-slate-950">{value}</div>
                  <div className="text-xs text-slate-500">{caption}</div>
                </div>
              </div>
            </div>
          ))}
        </section>

        <section className="rounded-xl border bg-white shadow-sm">
          <form
            className="grid gap-3 border-b p-4 lg:grid-cols-[minmax(0,1fr)_190px_190px_230px]"
            onSubmit={(event) => {
              event.preventDefault();
              router.get("/marketing/requests", form.data, { preserveState: true, preserveScroll: true });
            }}
          >
            <label className="flex h-11 items-center gap-2 rounded-lg border px-3">
              <input className="min-w-0 flex-1 text-sm outline-none" value={form.data.search} onChange={(event) => form.setData("search", event.currentTarget.value)} placeholder="Search requests by title, requester, or ID..." />
              <Search className="h-4 w-4 text-slate-400" />
            </label>
            <select className="h-11 rounded-lg border px-3 text-sm" value={form.data.status} onChange={(event) => form.setData("status", event.currentTarget.value)}>
              <option value="">All Statuses</option>
              <option value="submitted">Submitted</option>
              <option value="planned">Planned</option>
              <option value="in_production">In Progress</option>
              <option value="in_review">Pending Approval</option>
              <option value="completed">Completed</option>
            </select>
            <select className="h-11 rounded-lg border px-3 text-sm" value={form.data.priority} onChange={(event) => form.setData("priority", event.currentTarget.value)}>
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <button type="submit" className="inline-flex h-11 items-center justify-between rounded-lg border px-3 text-sm text-slate-500">
              Select date range <CalendarDays className="h-4 w-4" />
            </button>
          </form>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[1050px] text-sm">
              <thead className="border-b bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  {["Request", "Requester", "Type", "Priority", "Status", "Due Date", "Updated", "Actions"].map((heading) => (
                    <th key={heading} className="px-4 py-3 font-semibold">{heading}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y">
                {rows.length === 0 ? (
                  <tr><td colSpan={8} className="px-4 py-8 text-center text-slate-500">No marketing requests have been registered yet.</td></tr>
                ) : rows.map((request) => (
                  <tr key={request.id} className="hover:bg-slate-50">
                    <td className="px-4 py-4">
                      <div className="flex items-center gap-3">
                        <span className="flex size-10 items-center justify-center rounded-lg bg-red-50 text-red-600"><FileStack className="h-4 w-4" /></span>
                        <div>
                          <div className="font-semibold text-slate-950">{request.title}</div>
                          <div className="text-xs text-slate-500">{requestCode(request)}</div>
                          {request.work_task_id ? <div className="mt-1 text-xs text-blue-600">Task #{request.work_task_id}</div> : null}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4">
                      <div className="font-medium text-slate-900">{request.requester_name ?? "-"}</div>
                      <div className="text-xs text-slate-500">{request.project_name ?? request.event_name ?? "Marketing"}</div>
                    </td>
                    <td className="px-4 py-4"><span className="rounded-md bg-violet-50 px-2 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-200">{typeFor(request)}</span></td>
                    <td className="px-4 py-4"><span className={`rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ${priorityTone(request.priority)}`}>{request.priority}</span></td>
                    <td className="px-4 py-4"><span className={`rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ${statusTone(request.status)}`}>{request.status.replaceAll("_", " ")}</span></td>
                    <td className="px-4 py-4 font-medium text-red-600">{request.due_date ?? "-"}</td>
                    <td className="px-4 py-4 text-slate-500">1h ago</td>
                    <td className="px-4 py-4">
                      <div className="flex gap-2">
                        <Link href={`/marketing/requests/${request.id}`} className="inline-flex size-9 items-center justify-center rounded-lg border text-slate-600 hover:bg-slate-50"><Eye className="h-4 w-4" /></Link>
                        <Link href={`/marketing/requests/${request.id}`} className="inline-flex size-9 items-center justify-center rounded-lg border text-slate-600 hover:bg-slate-50"><Pencil className="h-4 w-4" /></Link>
                        <button className="inline-flex size-9 items-center justify-center rounded-lg border text-slate-600 hover:bg-slate-50"><MoreHorizontal className="h-4 w-4" /></button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-slate-500">
            <span>Showing 1 to {rows.length} of {rows.length} requests</span>
            <div className="flex items-center gap-2"><button className="size-9 rounded-lg border">&lt;</button><button className="size-9 rounded-lg bg-red-600 text-white">1</button><button className="size-9 rounded-lg border">&gt;</button></div>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
