import { Head, Link } from "@inertiajs/react";
import { CalendarClock, CheckCircle2, Download, Eye, Filter, Hourglass, Send, ShieldCheck, Workflow } from "lucide-react";
import { type ComponentType } from "react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Approvals", href: "/marketing/approvals" },
];

type PendingDeliverable = {
  id: number;
  request_id: number;
  title: string;
  request_title: string | null;
  assigned_unit: string;
  assignee_name: string | null;
  work_task_id: number | null;
  work_task_title: string | null;
  work_task_status: string | null;
  latest_version: number | null;
  review_notes: string | null;
};

type MetricCard = {
  label: string;
  value: number;
  caption: string;
  Icon: ComponentType<{ className?: string }>;
  tone: string;
};

const typeTone = (type: string) => {
  if (/digital/i.test(type)) return "bg-blue-50 text-blue-700 ring-blue-200";
  if (/print/i.test(type)) return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (/video/i.test(type)) return "bg-pink-50 text-pink-700 ring-pink-200";
  return "bg-violet-50 text-violet-700 ring-violet-200";
};

export default function MarketingApprovalsIndex({
  approvalQueue,
}: {
  approvalQueue: { pending: PendingDeliverable[] };
}) {
  const pending = approvalQueue.pending;
  const metrics: MetricCard[] = [
    { label: "Pending Approval", value: pending.length, caption: "Awaiting your review", Icon: Hourglass, tone: "border-rose-100 bg-rose-50/40 text-rose-600" },
    { label: "Due Soon", value: Math.min(2, pending.length), caption: "Due in the next 3 days", Icon: CalendarClock, tone: "border-amber-100 bg-amber-50/50 text-amber-600" },
    { label: "Approved", value: 18, caption: "This month", Icon: CheckCircle2, tone: "border-violet-100 bg-violet-50/50 text-indigo-600" },
    { label: "Completed", value: 12, caption: "This month", Icon: ShieldCheck, tone: "border-emerald-100 bg-emerald-50/50 text-emerald-600" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Approvals" />

      <div className="space-y-6 p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-semibold tracking-tight text-slate-950">Approvals</h1>
            <p className="mt-1 text-base text-slate-500">Review and approve deliverables before they are published or distributed.</p>
          </div>
          <button className="inline-flex h-11 items-center gap-2 rounded-lg bg-red-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
            <Download className="h-4 w-4" />
            Export Report
          </button>
        </div>

        <DomainNav items={marketingNavItems} />

        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {metrics.map(({ label, value, caption, Icon, tone }) => (
            <div key={label} className={`rounded-2xl border p-6 shadow-sm ${tone}`}>
              <div className="flex items-center gap-5">
                <span className="flex size-14 items-center justify-center rounded-full bg-white/70">
                  <Icon className="h-7 w-7" />
                </span>
                <div>
                  <div className="text-sm text-slate-600">{label}</div>
                  <div className="mt-1 text-3xl font-semibold text-slate-950">{value}</div>
                  <div className="text-sm text-slate-500">{caption}</div>
                </div>
              </div>
            </div>
          ))}
        </section>

        <section className="rounded-2xl border bg-white shadow-sm">
          <div className="grid gap-3 border-b p-4 xl:grid-cols-[minmax(0,1fr)_180px_180px_180px_220px]">
            <label className="flex h-11 items-center gap-2 rounded-lg border px-3">
              <SearchIcon />
              <input className="min-w-0 flex-1 text-sm outline-none" placeholder="Search deliverables by title, request ID, or requester..." />
            </label>
            <select className="h-11 rounded-lg border px-3 text-sm"><option>All Statuses</option></select>
            <select className="h-11 rounded-lg border px-3 text-sm"><option>All Priorities</option></select>
            <select className="h-11 rounded-lg border px-3 text-sm"><option>All Types</option></select>
            <button className="inline-flex h-11 items-center justify-between rounded-lg border px-3 text-sm text-slate-500">Select date range <CalendarClock className="h-4 w-4" /></button>
          </div>

          <div className="border-b px-5 py-4">
            <h2 className="font-semibold text-slate-950">Approval Queue</h2>
            <p className="text-sm text-slate-500">Deliverables awaiting your review and approval.</p>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[1120px] text-sm">
              <thead className="border-b bg-slate-50 text-left text-xs text-slate-500">
                <tr>
                  {["Deliverable", "Request", "Type", "Priority", "Requester", "Due Date", "Submitted", "Status", "Actions"].map((heading) => (
                    <th key={heading} className="px-4 py-3 font-semibold">{heading}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y">
                {pending.length === 0 ? (
                  <tr><td colSpan={9} className="px-4 py-10 text-center text-slate-500">No deliverables are currently awaiting approval.</td></tr>
                ) : pending.map((deliverable) => (
                  <tr key={deliverable.id} className="hover:bg-slate-50">
                    <td className="px-4 py-4">
                      <div className="flex items-center gap-3">
                        <span className="flex size-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600"><Workflow className="h-4 w-4" /></span>
                        <div>
                          <div className="font-semibold text-slate-950">{deliverable.title}</div>
                          <div className="text-xs text-slate-500">DEL-2026-{String(deliverable.id).padStart(4, "0")}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4">
                      <div className="font-semibold text-slate-950">REQ-2026-{String(deliverable.request_id).padStart(4, "0")}</div>
                      <div className="text-xs text-slate-500">{deliverable.request_title ?? "No request title"}</div>
                      {deliverable.work_task_id ? <div className="mt-1 text-xs text-blue-600">Task #{deliverable.work_task_id}</div> : null}
                    </td>
                    <td className="px-4 py-4"><span className={`rounded-md px-2 py-1 text-xs font-medium ring-1 ${typeTone(deliverable.assigned_unit)}`}>{deliverable.assigned_unit.replaceAll("_", " ")}</span></td>
                    <td className="px-4 py-4"><span className="rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-rose-200">High</span></td>
                    <td className="px-4 py-4"><div className="font-medium text-slate-950">{deliverable.assignee_name ?? "-"}</div><div className="text-xs text-slate-500">Marketing</div></td>
                    <td className="px-4 py-4"><div className="font-semibold text-red-600">2026-06-05</div><div className="text-xs text-slate-500">2 days left</div></td>
                    <td className="px-4 py-4"><div>2026-05-27</div><div className="text-xs text-slate-500">10:32 AM</div></td>
                    <td className="px-4 py-4"><span className="rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">Pending Review</span></td>
                    <td className="px-4 py-4"><div className="flex gap-2"><Link href={`/marketing/requests/${deliverable.request_id}`} className="inline-flex size-9 items-center justify-center rounded-lg border text-slate-600"><Eye className="h-4 w-4" /></Link><Link href={`/marketing/requests/${deliverable.request_id}`} className="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 px-3 text-sm font-medium text-red-600"><Send className="h-4 w-4" />Review</Link></div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t px-5 py-4 text-sm text-slate-500">
            <span>Showing 1 to {pending.length} of {pending.length} deliverables</span>
            <div className="flex gap-2"><button className="size-9 rounded-lg border">&lt;</button><button className="size-9 rounded-lg bg-red-600 text-white">1</button><button className="size-9 rounded-lg border">&gt;</button></div>
          </div>
        </section>

        <section className="flex flex-wrap items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm">
          <div className="flex items-center gap-3"><span className="flex size-8 items-center justify-center rounded-full bg-white text-blue-600">i</span><span><strong>Approval Process</strong><br />Review each deliverable carefully. You can provide feedback, request changes, or approve for final publication.</span></div>
          <button className="inline-flex h-10 items-center gap-2 rounded-lg border border-blue-200 bg-white px-4 text-blue-700">View Approval Guidelines <Filter className="h-4 w-4" /></button>
        </section>
      </div>
    </AppLayout>
  );
}

function SearchIcon() {
  return <Filter className="h-4 w-4 rotate-90 text-slate-400" />;
}
