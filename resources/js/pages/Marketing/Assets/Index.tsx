import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Marketing", href: "/marketing" },
  { title: "Assets", href: "/marketing/assets" },
];

export default function MarketingAssetsIndex({
  assets,
}: {
  assets: { data: Array<{ id: number; asset_type: string; asset_file_name: string | null; deliverable_title: string | null; version_number: number | null; reusable: boolean; archived_at: string | null; publications?: { data?: Array<{ id: number; publication_channel: string; published_at: string | null }> } }> };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Asset Library" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Asset Library</h1>
            <p className="text-sm text-muted-foreground">
              Approved assets become reusable library items instead of being buried inside one-off job records.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        <div className="space-y-4">
          {assets.data.length === 0 ? (
            <section className="rounded-xl border bg-card p-4 text-sm text-muted-foreground shadow-sm">No approved assets are available yet.</section>
          ) : assets.data.map((asset) => (
            <section key={asset.id} className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-lg font-semibold">{asset.asset_file_name ?? asset.asset_type}</h2>
              <div className="mt-1 text-sm text-muted-foreground">
                {asset.asset_type.replaceAll("_", " ")} | {asset.deliverable_title ?? "No deliverable"} | Version {asset.version_number ?? "-"}
              </div>
              <div className="mt-1 text-xs text-muted-foreground">
                {asset.reusable ? "Reusable asset" : "Single-use asset"}{asset.archived_at ? ` | Archived ${asset.archived_at}` : ""}
              </div>
            </section>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
