import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type DashboardJob = {
  id: number;
  title: string;
  job_type: string;
  status: string;
  priority: string;
  due_date: string | null;
  assignee_name: string | null;
  event_name: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
];

function MetricCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-xl border bg-card p-4 shadow-sm">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-2 text-2xl font-semibold">{value}</div>
    </div>
  );
}

function JobList({ title, items, empty }: { title: string; items: DashboardJob[]; empty: string }) {
  return (
    <section className="rounded-xl border bg-card p-4 shadow-sm">
      <h2 className="text-sm font-semibold">{title}</h2>
      <div className="mt-3 space-y-3">
        {items.length === 0 ? (
          <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">{empty}</div>
        ) : (
          items.map((item) => (
            <div key={item.id} className="rounded-lg border p-3">
              <div className="font-medium">{item.title}</div>
              <div className="mt-1 text-xs text-muted-foreground">
                {item.job_type.replaceAll("_", " ")} | {item.priority.toUpperCase()} | {item.status.replaceAll("_", " ")}
              </div>
              <div className="mt-1 text-xs text-muted-foreground">
                {item.assignee_name ?? "Marketing queue"}{item.event_name ? ` | ${item.event_name}` : ""}{item.due_date ? ` | Due ${item.due_date}` : ""}
              </div>
              <Link href={`/marketing/jobs/${item.id}`} className="mt-2 inline-block text-xs text-blue-700 underline">
                Open workflow
              </Link>
            </div>
          ))
        )}
      </div>
    </section>
  );
}

export default function MarketingDashboard({
  dashboard,
}: {
  dashboard: {
    persona: "manager" | "staff";
    can_create: boolean;
    summary: { total: number; open: number; in_progress: number; pending_approval: number; changes_requested: number; approved: number };
    assigned: DashboardJob[];
    pending_approval: DashboardJob[];
    changes_requested: DashboardJob[];
    by_type: Array<{ job_type: string; count: number }>;
  };
}) {
  const managerView = dashboard.persona === "manager";

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Marketing Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Marketing Dashboard</h1>
            <p className="text-sm text-muted-foreground">
              {managerView
                ? "Manager view of design, social content, content planning, letters, approvals, and amendment pressure."
                : "Personal marketing workflow view of assigned work, submissions awaiting approval, and returned amendments."}
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        <section className="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
          <MetricCard label="Visible Jobs" value={dashboard.summary.total} />
          <MetricCard label="Open" value={dashboard.summary.open} />
          <MetricCard label="In Progress" value={dashboard.summary.in_progress} />
          <MetricCard label="Awaiting Approval" value={dashboard.summary.pending_approval} />
          <MetricCard label="Amendments" value={dashboard.summary.changes_requested} />
          <MetricCard label="Approved" value={dashboard.summary.approved} />
        </section>

        <section className="grid gap-4 xl:grid-cols-2">
          <JobList
            title={managerView ? "Awaiting Manager Approval" : "Assigned To Me"}
            items={managerView ? dashboard.pending_approval : dashboard.assigned}
            empty={managerView ? "No marketing deliverables are waiting for approval." : "No marketing items are currently assigned to you."}
          />
          <JobList
            title="Returned For Amendments"
            items={dashboard.changes_requested}
            empty="No marketing items have been returned for amendments."
          />
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-sm font-semibold">Marketing Mix</h2>
          <div className="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            {dashboard.by_type.length === 0 ? (
              <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                No marketing work has been logged yet.
              </div>
            ) : dashboard.by_type.map((item) => (
              <div key={item.job_type} className="rounded-lg border p-4">
                <div className="text-xs text-muted-foreground">{item.job_type.replaceAll("_", " ")}</div>
                <div className="mt-2 text-2xl font-semibold">{item.count}</div>
              </div>
            ))}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
