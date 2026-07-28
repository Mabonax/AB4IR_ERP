import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { assetNavItems } from "@/config/domain-nav/assets";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

export default function AssetRegisterItems({
  category,
  model_name,
  items,
}: {
  category: { id: number; name: string };
  model_name: string;
  items: Array<{
    id: number;
    asset_code: string | null;
    serial_number: string | null;
    serial_state: string;
    status: string;
    assigned_to: string | null;
    last_assignment_at: string | null;
  }>;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Assets", href: "/assets" },
    { title: "Asset Register", href: "/assets/register" },
    { title: category.name, href: `/assets/register/${category.id}/models` },
    { title: model_name, href: "#" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Asset Register - ${category.name} - ${model_name}`} />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">
            Asset Register: {category.name} / {model_name}
          </h1>
          <DomainNav items={assetNavItems} />
        </div>

        <div className="flex">
          <Link
            href={`/assets/register/${category.id}/models`}
            className="rounded-md border border-red-500 px-3 py-1.5 text-xs text-red-600 hover:bg-red-500 hover:text-white"
          >
            Back to Models
          </Link>
        </div>

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left font-medium">AssetID</th>
                <th className="px-3 py-2 text-left font-medium">Serial</th>
                <th className="px-3 py-2 text-left font-medium">Serial State</th>
                <th className="px-3 py-2 text-left font-medium">Status</th>
                <th className="px-3 py-2 text-left font-medium">Assigned To</th>
                <th className="px-3 py-2 text-left font-medium">Last Assignment Date</th>
              </tr>
            </thead>
            <tbody>
              {items.length === 0 ? (
                <tr>
                  <td className="px-3 py-4 text-muted-foreground" colSpan={6}>
                    No active assets found for this model.
                  </td>
                </tr>
              ) : (
                items.map((item) => (
                  <tr key={item.id} className="border-t">
                    <td className="px-3 py-2">{item.asset_code ?? "-"}</td>
                    <td className="px-3 py-2">{item.serial_number ?? "-"}</td>
                    <td className="px-3 py-2">{item.serial_state}</td>
                    <td className="px-3 py-2 capitalize">{item.status}</td>
                    <td className="px-3 py-2">{item.assigned_to ?? "-"}</td>
                    <td className="px-3 py-2">{item.last_assignment_at ?? "-"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </section>
      </div>
    </AppLayout>
  );
}
