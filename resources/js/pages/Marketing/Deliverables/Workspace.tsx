import { Head, Link } from "@inertiajs/react";
import { CheckCircle2, Clock3, Download, Eye, FileText, Filter, MoreHorizontal, Pencil, Plus, Search, Send, Users } from "lucide-react";
import { type ComponentType } from "react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Deliverables Workspace", href: "/marketing/deliverables/workspace" },
];

type Deliverable = {
  id: number;
  request_id: number;
  title: string;
  request_title: string | null;
  deliverable_type: string;
  assigned_unit: string;
  status: string;
  due_date: string | null;
  assignee_name: string | null;
  work_task_id: number | null;
  work_task_title: string | null;
  work_task_status: string | null;
  version_count: number;
};

type MetricCard = {
  label: string;
  value: number;
  caption: string;
  Icon: ComponentType<{ className?: string }>;
  tone: string;
};

const statusLabel = (status: string) => status.replaceAll("_", " ");

export default function MarketingDeliverablesWorkspace({
  workspace,
}: {
  workspace: {
    summary: { queued: number; in_progress: number; internal_review: number; approved: number };
    deliverables: Deliverable[];
  };
}) {
  const rows = workspace.deliverables;
  const featured = rows[0];
  const published = rows.filter((row) => row.status === "published").length;
  const metrics: MetricCard[] = [
    { label: "Queued", value: workspace.summary.queued, caption: "Awaiting assignment", Icon: FileText, tone: "bg-blue-50 text-blue-600" },
    { label: "In Progress", value: workspace.summary.in_progress, caption: "Actively being worked on", Icon: Clock3, tone: "bg-orange-50 text-orange-600" },
    { label: "Internal Review", value: workspace.summary.internal_review, caption: "Under review", Icon: Users, tone: "bg-violet-50 text-violet-600" },
    { label: "Approved", value: workspace.summary.approved, caption: "Approved and ready", Icon: CheckCircle2, tone: "bg-emerald-50 text-emerald-600" },
    { label: "Published", value: published, caption: "Published / Live", Icon: Send, tone: "bg-blue-50 text-blue-600" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Deliverables Workspace" />

      <div className="space-y-5 p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-slate-950">Deliverables Workspace</h1>
            <p className="mt-1 text-sm text-slate-500">Manage and track all deliverables across marketing operations.</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Link href="/marketing/requests/create" className="inline-flex h-10 items-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700"><Plus className="h-4 w-4" />Register Operation</Link>
            <Link href="/marketing/requests" className="inline-flex h-10 items-center gap-2 rounded-lg border px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open Requests Workspace</Link>
            <button className="inline-flex h-10 items-center gap-2 rounded-lg border px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><Filter className="h-4 w-4" />Filters</button>
          </div>
        </div>

        <DomainNav items={marketingNavItems} />

        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
          {metrics.map(({ label, value, caption, Icon, tone }) => (
            <div key={label} className="rounded-xl border bg-white p-5 shadow-sm">
              <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <span className={`flex size-12 items-center justify-center rounded-full ${tone}`}><Icon className="h-6 w-6" /></span>
                  <div><div className="text-sm text-slate-600">{label}</div><div className="text-2xl font-semibold text-slate-950">{value}</div><div className="text-xs text-slate-500">{caption}</div></div>
                </div>
                <span className="text-xl text-slate-500">&rsaquo;</span>
              </div>
            </div>
          ))}
        </section>

        {featured ? (
          <section className="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div className="grid gap-5 border-b p-5 xl:grid-cols-[minmax(0,1fr)_360px]">
              <div className="flex items-start gap-4">
                <div className="flex size-28 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-red-700 to-orange-500 text-center text-lg font-black uppercase leading-tight text-white shadow-inner">
                  {featured.title.split(" ").slice(0, 2).join(" ")}
                </div>
                <div>
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">{statusLabel(featured.status)}</span>
                    <span className="rounded-md bg-violet-50 px-2 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-200">{featured.deliverable_type}</span>
                  </div>
                  <h2 className="mt-2 text-3xl font-semibold text-slate-950">{featured.title}</h2>
                  <div className="mt-1 text-sm text-slate-600">REQ-2026-{String(featured.request_id).padStart(4, "0")} | {featured.request_title ?? "Marketing operation"}</div>
                  <div className="mt-2 text-sm text-slate-600">Requested by {featured.assignee_name ?? "Local Super Admin"} | Due {featured.due_date ?? "not set"}</div>
                </div>
              </div>
              <div className="flex items-center justify-between gap-5">
                <div className="min-w-0 flex-1">
                  <div className="text-sm font-medium text-slate-700">Overall Progress</div>
                  <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200"><div className="h-full w-[65%] rounded-full bg-emerald-500" /></div>
                  <div className="mt-2 text-xs text-slate-500">Last updated: 1 hour ago</div>
                </div>
                <div className="text-3xl font-semibold text-emerald-600">65%</div>
                <Link href={`/marketing/requests/${featured.request_id}`} className="inline-flex h-10 items-center rounded-lg border px-4 text-sm font-semibold text-slate-700">View in Request</Link>
              </div>
            </div>
            <div className="grid gap-4 border-b p-5 md:grid-cols-3 xl:grid-cols-6">
              {[["Work Package", featured.assigned_unit, "Poster design"], ["Assignee", featured.assignee_name ?? "Unassigned", "Designer"], ["Priority", "High", ""], ["Target Audience", "Youth 16-30", "Not specified"], ["Channel", "Digital & Print", "Social, Web, Print"], ["Deliverable Type", featured.deliverable_type, "Static design"]].map(([label, value, caption]) => (
                <div key={label} className="border-r last:border-r-0">
                  <div className="text-xs text-slate-500">{label}</div>
                  <div className="mt-1 font-semibold text-slate-950">{value}</div>
                  {caption ? <div className="text-xs text-slate-500">{caption}</div> : null}
                </div>
              ))}
            </div>
            <div className="grid gap-4 p-5 xl:grid-cols-[1fr_1.2fr_.8fr]">
              <div className="rounded-xl border p-4">
                <h3 className="font-semibold">Latest Version <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs">v{featured.version_count || 1}.0</span></h3>
                <div className="mt-4 flex gap-4">
                  <div className="flex size-24 items-center justify-center rounded-lg bg-red-600 font-black text-white">PDF</div>
                  <div><div className="font-semibold">{featured.title.replaceAll(" ", "_")}_v2.pdf</div><div className="text-sm text-slate-500">1.2 MB | PDF</div><div className="text-sm text-slate-500">Uploaded 2026-05-27 14:32</div><div className="text-sm text-slate-500">By {featured.assignee_name ?? "Marketing User"}</div></div>
                </div>
                <div className="mt-4 grid grid-cols-2 gap-2"><button className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border text-sm"><Download className="h-4 w-4" />Download</button><button className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border text-sm"><Eye className="h-4 w-4" />Preview</button></div>
              </div>
              <div className="rounded-xl border p-4">
                <h3 className="font-semibold">Recent Activity</h3>
                <div className="mt-4 space-y-4">
                  {["File uploaded (v2.0)", "Review requested", "Version updated", "Work started"].map((item, index) => (
                    <div key={item} className="flex gap-3">
                      <span className="mt-1 size-3 rounded-full bg-blue-500" />
                      <div className="flex-1"><div className="font-medium">{item}</div><div className="text-xs text-slate-500">{index + 1} hour{index ? "s" : ""} ago | {featured.assignee_name ?? "System"}</div></div>
                    </div>
                  ))}
                </div>
              </div>
              <div className="space-y-4">
                <div className="rounded-xl border p-4"><h3 className="font-semibold">Next Steps</h3><div className="mt-3 space-y-3 text-sm"><div>Internal review</div><div>Final approval</div><div>Publish deliverable</div></div></div>
                <div className="rounded-xl border p-4"><h3 className="font-semibold">Linked to Request</h3><div className="mt-3 font-semibold">REQ-2026-{String(featured.request_id).padStart(4, "0")}</div><div className="text-sm text-slate-500">{featured.request_title}</div></div>
              </div>
            </div>
          </section>
        ) : null}

        <section className="rounded-2xl border bg-white shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
            <div className="flex flex-wrap gap-6 text-sm font-medium"><span className="border-b-2 border-red-600 pb-3 text-red-600">All Deliverables</span><span>Queued</span><span>In Progress</span><span>Internal Review</span><span>Approved</span><span>Published</span></div>
            <label className="flex h-10 w-full max-w-xs items-center gap-2 rounded-lg border px-3"><Search className="h-4 w-4 text-slate-400" /><input className="min-w-0 flex-1 text-sm outline-none" placeholder="Search deliverables..." /></label>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[1000px] text-sm">
              <thead className="bg-slate-50 text-left text-xs text-slate-500"><tr>{["Deliverable", "Request", "Type", "Assignee", "Status", "Due Date", "Progress", "Updated", "Actions"].map((h) => <th key={h} className="px-4 py-3 font-semibold">{h}</th>)}</tr></thead>
              <tbody className="divide-y">
                {rows.map((row, index) => (
                  <tr key={row.id} className="hover:bg-slate-50">
                    <td className="px-4 py-4"><div className="font-semibold">{row.title}</div><div className="text-xs text-slate-500">OP-2026-{String(row.id).padStart(4, "0")}</div></td>
                    <td className="px-4 py-4"><div className="font-semibold">REQ-2026-{String(row.request_id).padStart(4, "0")}</div><div className="text-xs text-slate-500">{row.request_title}</div></td>
                    <td className="px-4 py-4"><span className="rounded-md bg-blue-50 px-2 py-1 text-xs text-blue-700 ring-1 ring-blue-200">{row.deliverable_type}</span></td>
                    <td className="px-4 py-4">{row.assignee_name ?? "-"}</td>
                    <td className="px-4 py-4"><span className="rounded-md bg-violet-50 px-2 py-1 text-xs capitalize text-violet-700 ring-1 ring-violet-200">{statusLabel(row.status)}</span></td>
                    <td className="px-4 py-4"><div className="font-semibold">{row.due_date ?? "-"}</div><div className="text-xs text-slate-500">7 days left</div></td>
                    <td className="px-4 py-4"><div className="h-2 w-24 overflow-hidden rounded-full bg-slate-200"><div className="h-full rounded-full bg-blue-600" style={{ width: `${[40, 25, 55, 65][index % 4]}%` }} /></div></td>
                    <td className="px-4 py-4 text-slate-500">{index + 1}d ago</td>
                    <td className="px-4 py-4"><div className="flex gap-2"><Link href={`/marketing/requests/${row.request_id}`} className="inline-flex size-9 items-center justify-center rounded-lg border"><Eye className="h-4 w-4" /></Link><button className="inline-flex size-9 items-center justify-center rounded-lg border"><Pencil className="h-4 w-4" /></button><button className="inline-flex size-9 items-center justify-center rounded-lg border"><MoreHorizontal className="h-4 w-4" /></button></div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
