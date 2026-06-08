import { Head, Link, router, useForm } from "@inertiajs/react";

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
  deliverables?: Array<{ title: string; status: string; assigned_unit: string }>;
};

export default function MarketingRequestsIndex({
  requests,
  filters,
  can,
}: {
  requests: { data: RequestRow[] };
  filters: Record<string, string>;
  can: { create: boolean };
}) {
  const form = useForm({
    search: filters.search ?? "",
    status: filters.status ?? "",
    priority: filters.priority ?? "",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Marketing Requests" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Marketing Requests</h1>
            <p className="text-sm text-muted-foreground">
              Requests are the business brief layer above work packages and deliverables. Each request can now drive multiple outputs across graphics, digital, communications, events support, and content.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {can.create ? (
              <Link href="/marketing/requests/create" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                New Request
              </Link>
            ) : null}
            <DomainNav items={marketingNavItems} />
          </div>
        </div>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <form
            className="grid gap-3 md:grid-cols-4"
            onSubmit={(event) => {
              event.preventDefault();
              router.get("/marketing/requests", form.data, { preserveState: true, preserveScroll: true });
            }}
          >
            <input className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.search} onChange={(event) => form.setData("search", event.currentTarget.value)} placeholder="Search title, objective, or description" />
            <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.status} onChange={(event) => form.setData("status", event.currentTarget.value)}>
              <option value="">All statuses</option>
              <option value="draft">Draft</option>
              <option value="submitted">Submitted</option>
              <option value="planned">Planned</option>
              <option value="in_production">In production</option>
              <option value="in_review">In review</option>
              <option value="partially_approved">Partially approved</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.data.priority} onChange={(event) => form.setData("priority", event.currentTarget.value)}>
              <option value="">All priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <div className="flex gap-2">
              <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Apply</button>
              <button type="button" className="rounded-md border px-4 py-2 text-sm" onClick={() => router.get("/marketing/requests")}>Reset</button>
            </div>
          </form>
        </section>

        <div className="space-y-4">
          {requests.data.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">No marketing requests have been registered yet.</section>
          ) : requests.data.map((requestRecord) => (
            <section key={requestRecord.id} className="rounded-xl border bg-card p-4 shadow-sm">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <h2 className="text-lg font-semibold">{requestRecord.title}</h2>
                    <span className="rounded-full border bg-slate-50 px-2.5 py-1 text-xs font-medium capitalize">
                      {requestRecord.status.replaceAll("_", " ")}
                    </span>
                    <span className="rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700">
                      {requestRecord.priority.toUpperCase()}
                    </span>
                  </div>
                  <div className="text-sm text-muted-foreground">{requestRecord.objective ?? "No objective captured yet."}</div>
                  <div className="text-xs text-muted-foreground">
                    Requester {requestRecord.requester_name ?? "-"} | Approver {requestRecord.approver_name ?? "-"} | {requestRecord.project_name ?? requestRecord.event_name ?? "Standalone marketing initiative"}
                    {requestRecord.due_date ? ` | Due ${requestRecord.due_date}` : ""}
                  </div>
                  {requestRecord.source_marketing_job_id ? (
                    <div className="text-xs text-muted-foreground">Migrated from legacy marketing job #{requestRecord.source_marketing_job_id}.</div>
                  ) : null}
                </div>
                <Link href={`/marketing/requests/${requestRecord.id}`} className="rounded-md border border-orange-500 px-3 py-1.5 text-sm text-orange-600 hover:bg-orange-500 hover:text-white">
                  Open Request
                </Link>
              </div>
            </section>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
