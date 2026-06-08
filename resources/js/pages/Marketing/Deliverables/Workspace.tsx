import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Deliverables Workspace", href: "/marketing/deliverables/workspace" },
];

export default function MarketingDeliverablesWorkspace({
  workspace,
}: {
  workspace: {
    summary: { queued: number; in_progress: number; internal_review: number; approved: number };
    deliverables: Array<{ id: number; request_id: number; title: string; request_title: string | null; deliverable_type: string; assigned_unit: string; status: string; due_date: string | null; assignee_name: string | null; version_count: number }>;
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Deliverables Workspace" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Deliverables Workspace</h1>
            <p className="text-sm text-muted-foreground">
              Operational lane view for graphics, communications, digital, events support, and content deliverables.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Queued</div><div className="mt-2 text-2xl font-semibold">{workspace.summary.queued}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">In Progress</div><div className="mt-2 text-2xl font-semibold">{workspace.summary.in_progress}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Internal Review</div><div className="mt-2 text-2xl font-semibold">{workspace.summary.internal_review}</div></div>
          <div className="rounded-xl border bg-card p-4 shadow-sm"><div className="text-xs text-muted-foreground">Approved</div><div className="mt-2 text-2xl font-semibold">{workspace.summary.approved}</div></div>
        </section>

        <div className="space-y-4">
          {workspace.deliverables.map((deliverable) => (
            <section key={deliverable.id} className="rounded-xl border bg-card p-4 shadow-sm">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 className="text-lg font-semibold">{deliverable.title}</h2>
                  <div className="mt-1 text-sm text-muted-foreground">
                    {deliverable.request_title ?? "No request title"} | {deliverable.deliverable_type.replaceAll("_", " ")} | {deliverable.assigned_unit.replaceAll("_", " ")}
                  </div>
                  <div className="mt-1 text-xs text-muted-foreground">
                    {deliverable.assignee_name ?? "Unassigned"} | {deliverable.status.replaceAll("_", " ")} | Versions {deliverable.version_count}
                    {deliverable.due_date ? ` | Due ${deliverable.due_date}` : ""}
                  </div>
                </div>
                <Link href={`/marketing/requests/${deliverable.request_id}`} className="text-sm text-blue-700 underline">
                  Open request workspace
                </Link>
              </div>
            </section>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
