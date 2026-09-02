import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Marketing", href: "/marketing" }];

function MetricCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-xl border bg-card p-4 shadow-sm">
      <div className="text-xs text-muted-foreground">{label}</div>
      <div className="mt-2 text-2xl font-semibold">{value}</div>
    </div>
  );
}

function BreakdownCard({ title, items }: { title: string; items: Array<{ label: string; count: number }> }) {
  return (
    <section className="rounded-xl border bg-card p-4 shadow-sm">
      <h2 className="text-sm font-semibold">{title}</h2>
      <div className="mt-3 space-y-2">
        {items.length === 0 ? (
          <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">No data recorded yet.</div>
        ) : items.map((item) => (
          <div key={item.label} className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm">
            <span className="capitalize">{item.label.replaceAll("_", " ")}</span>
            <span className="font-medium">{item.count}</span>
          </div>
        ))}
      </div>
    </section>
  );
}

export default function MarketingDashboard({
  dashboard,
}: {
  dashboard: {
    operations: {
      active_requests: number;
      deliverables_in_queue: number;
      overdue_deliverables: number;
      approvals_pending: number;
      items_published_this_week: number;
      workload_by_assignee: Array<{ label: string; count: number }>;
      workload_by_unit: Array<{ label: string; count: number }>;
      work_by_type: Array<{ label: string; count: number }>;
    };
    performance: {
      reach: number;
      impressions: number;
      engagements: number;
      clicks: number;
      conversions: number;
      followers: number;
      publication_activity: Array<{ label: string; count: number }>;
      top_campaigns: Array<{ title: string; reach: number; engagements: number }>;
      website_referrals: number;
    };
    can: {
      create_request: boolean;
      view_performance: boolean;
    };
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Marketing Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Marketing Operations</h1>
            <p className="text-sm text-muted-foreground">
              Govern marketing briefs, deliverables, approved assets, publications, and metrics while Task Management remains the work intake and completion workflow.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {dashboard.can.create_request ? (
              <Link href="/marketing/requests/create" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                Register Operation
              </Link>
            ) : null}
            <DomainNav items={marketingNavItems} />
          </div>
        </div>

        <section className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-semibold">Operations Dashboard</h2>
            <Link href="/marketing/deliverables/workspace" className="text-sm text-blue-700 underline">
              Open deliverables workspace
            </Link>
          </div>
          <div className="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
            <MetricCard label="Active Requests" value={dashboard.operations.active_requests} />
            <MetricCard label="Deliverables In Queue" value={dashboard.operations.deliverables_in_queue} />
            <MetricCard label="Overdue Deliverables" value={dashboard.operations.overdue_deliverables} />
            <MetricCard label="Approvals Pending" value={dashboard.operations.approvals_pending} />
            <MetricCard label="Published This Week" value={dashboard.operations.items_published_this_week} />
          </div>
          <div className="grid gap-4 xl:grid-cols-3">
            <BreakdownCard title="Workload By Assignee" items={dashboard.operations.workload_by_assignee} />
            <BreakdownCard title="Workload By Unit" items={dashboard.operations.workload_by_unit} />
            <BreakdownCard title="Work By Deliverable Type" items={dashboard.operations.work_by_type} />
          </div>
        </section>

        {dashboard.can.view_performance ? (
          <section className="space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="text-base font-semibold">Performance Dashboard</h2>
              <Link href="/marketing/publications" className="text-sm text-blue-700 underline">
                Open publication register
              </Link>
            </div>
            <div className="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
              <MetricCard label="Reach" value={dashboard.performance.reach} />
              <MetricCard label="Impressions" value={dashboard.performance.impressions} />
              <MetricCard label="Engagements" value={dashboard.performance.engagements} />
              <MetricCard label="Clicks" value={dashboard.performance.clicks} />
              <MetricCard label="Conversions" value={dashboard.performance.conversions} />
              <MetricCard label="Followers" value={dashboard.performance.followers} />
            </div>
            <div className="grid gap-4 xl:grid-cols-3">
              <BreakdownCard title="Publication Activity" items={dashboard.performance.publication_activity} />
              <section className="rounded-xl border bg-card p-4 shadow-sm xl:col-span-2">
                <h2 className="text-sm font-semibold">Top Campaigns</h2>
                <div className="mt-3 space-y-3">
                  {dashboard.performance.top_campaigns.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">No campaign metrics recorded yet.</div>
                  ) : dashboard.performance.top_campaigns.map((campaign) => (
                    <div key={campaign.title} className="rounded-lg border p-3">
                      <div className="font-medium">{campaign.title}</div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Reach {campaign.reach} | Engagements {campaign.engagements}
                      </div>
                    </div>
                  ))}
                </div>
                <div className="mt-4 rounded-lg border border-dashed p-3 text-sm text-muted-foreground">
                  Website referral sessions recorded: {dashboard.performance.website_referrals}
                </div>
              </section>
            </div>
          </section>
        ) : null}
      </div>
    </AppLayout>
  );
}
