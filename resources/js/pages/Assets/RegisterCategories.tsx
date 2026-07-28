import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { assetNavItems } from "@/config/domain-nav/assets";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Assets", href: "/assets" },
  { title: "Asset Register", href: "/assets/register" },
];

export default function AssetRegisterCategories({
  categories,
}: {
  categories: Array<{
    id: number;
    name: string;
    active_assets_count: number;
  }>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Asset Register - Categories" />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Asset Register: Categories</h1>
          <DomainNav items={assetNavItems} />
        </div>

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left font-medium">Category</th>
                <th className="px-3 py-2 text-left font-medium">Active Count</th>
                <th className="px-3 py-2 text-left font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              {categories.length === 0 ? (
                <tr>
                  <td className="px-3 py-4 text-muted-foreground" colSpan={3}>
                    No categories found.
                  </td>
                </tr>
              ) : (
                categories.map((category) => (
                  <tr key={category.id} className="border-t">
                    <td className="px-3 py-2">{category.name}</td>
                    <td className="px-3 py-2">{category.active_assets_count}</td>
                    <td className="px-3 py-2">
                      <Link
                        href={`/assets/register/${category.id}/models`}
                        className="rounded-md border border-red-500 px-3 py-1.5 text-xs text-red-600 hover:bg-red-500 hover:text-white"
                      >
                        View Models
                      </Link>
                    </td>
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
