import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Approvals", href: "/marketing/approvals" },
];

export default function MarketingApprovalsIndex({
  approvalQueue,
}: {
  approvalQueue: {
    pending: Array<{ id: number; title: string; request_title: string | null; assigned_unit: string; assignee_name: string | null; latest_version: number | null; review_notes: string | null }>;
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Approval Review" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Approval Review Screen</h1>
            <p className="text-sm text-muted-foreground">
              Manager queue for deliverables that are in internal review and waiting for per-deliverable approval.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        <div className="space-y-4">
          {approvalQueue.pending.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">No deliverables are currently awaiting approval.</section>
          ) : approvalQueue.pending.map((deliverable) => (
            <section key={deliverable.id} className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-lg font-semibold">{deliverable.title}</h2>
              <div className="mt-1 text-sm text-muted-foreground">
                {deliverable.request_title ?? "No request title"} | {deliverable.assigned_unit.replaceAll("_", " ")} | {deliverable.assignee_name ?? "Unassigned"}
              </div>
              <div className="mt-1 text-xs text-muted-foreground">
                Latest submitted version {deliverable.latest_version ?? "-"}
              </div>
              {deliverable.review_notes ? <div className="mt-2 text-sm text-muted-foreground">{deliverable.review_notes}</div> : null}
            </section>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
