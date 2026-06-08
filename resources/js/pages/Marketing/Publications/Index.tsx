import { Head, router, usePage } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Publications", href: "/marketing/publications" },
];

export default function MarketingPublicationsIndex({
  publications,
}: {
  publications: { data: Array<{ id: number; publication_channel: string; published_by_name: string | null; published_at: string | null; external_reference: string | null; publication_notes: string | null; metrics?: { data?: Array<{ metric_date: string | null; reach: number | null; impressions: number | null; engagements: number | null; clicks: number | null; sessions: number | null; conversions: number | null; followers: number | null }> } }> };
}) {
  const flash = (usePage<SharedData>().props.flash ?? {}) as Record<string, unknown>;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Publication Records" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Publication Records</h1>
            <p className="text-sm text-muted-foreground">
              Publication records connect approved assets to channels and metric snapshots for campaign performance tracking.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        {flash.success ? <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">{String(flash.success)}</div> : null}

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Import Metric Snapshots</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Upload a CSV with `publication_record_id`, `metric_date`, and any of `impressions`, `reach`, `engagements`, `clicks`, `sessions`, `conversions`, `followers`.
          </p>
          <form
            className="mt-4 grid gap-3 md:grid-cols-[1fr_auto]"
            onSubmit={(event) => {
              event.preventDefault();
              const formData = new FormData(event.currentTarget);
              router.post("/marketing/publications/import-metrics", formData, { forceFormData: true, preserveScroll: true });
            }}
          >
            <input name="file" type="file" accept=".csv,text/csv" className="rounded-md border bg-background px-3 py-2 text-sm" />
            <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">
              Import CSV
            </button>
          </form>
        </section>

        <div className="space-y-4">
          {publications.data.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">No publication records captured yet.</section>
          ) : publications.data.map((publication) => (
            <section key={publication.id} className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-lg font-semibold">{publication.publication_channel}</h2>
              <div className="mt-1 text-sm text-muted-foreground">
                Published by {publication.published_by_name ?? "-"} | {publication.published_at ?? "-"}
              </div>
              {publication.external_reference ? <div className="mt-1 text-xs text-muted-foreground">{publication.external_reference}</div> : null}
              {publication.publication_notes ? <div className="mt-2 text-sm text-muted-foreground">{publication.publication_notes}</div> : null}
              {(publication.metrics?.data ?? []).length ? (
                <div className="mt-3 grid gap-2 md:grid-cols-4">
                  {(publication.metrics?.data ?? []).map((metric, index) => (
                    <div key={`${publication.id}-${metric.metric_date ?? "metric"}-${index}`} className="rounded-md border p-2 text-xs text-muted-foreground">
                      {metric.metric_date ?? "-"} | Reach {metric.reach ?? 0} | Impressions {metric.impressions ?? 0}
                    </div>
                  ))}
                </div>
              ) : null}
            </section>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
