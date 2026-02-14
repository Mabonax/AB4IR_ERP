import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { assetNavItems } from "@/config/domain-nav/assets";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Assets", href: "/assets" },
  { title: "Manager Dashboard", href: "/assets/manager-dashboard" },
];

export default function AssetManagerDashboard({
  stats,
  assetsByStaff,
  activityRows,
}: {
  stats: {
    departmentAssets: number;
    staffAssets: number;
    unreturnedAssets: number;
    recentActivities: number;
  };
  assetsByStaff: Array<{
    staff_member_id: number;
    staff_name: string;
    assets_count: number;
    assets: Array<{
      asset_id: number;
      asset_name: string | null;
      asset_code: string | null;
      serial_number: string | null;
      project_name: string | null;
      assigned_at: string | null;
    }>;
  }>;
  activityRows: Array<{
    id: number;
    asset: string | null;
    asset_code: string | null;
    status: string;
    target: string;
    assigned_by: string | null;
    returned_by: string | null;
    assigned_at: string | null;
    returned_at: string | null;
    notes: string | null;
  }>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Asset Manager Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Asset Manager Dashboard</h1>
          <DomainNav items={assetNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Department Assets</CardTitle>
              <CardDescription>Assigned to department scope</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.departmentAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Staff Assets</CardTitle>
              <CardDescription>Assigned to staff members</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.staffAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Unreturned</CardTitle>
              <CardDescription>Currently active assignments</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.unreturnedAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Recent Activity</CardTitle>
              <CardDescription>History events in scope</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.recentActivities}</CardContent>
          </Card>
        </div>

        <section className="rounded-xl border bg-white p-4 shadow-sm">
          <h2 className="text-base font-semibold">Assets By Staff</h2>
          <div className="mt-3 grid gap-4 md:grid-cols-2">
            {assetsByStaff.length === 0 ? (
              <p className="text-sm text-muted-foreground">No active assignments in your department scope.</p>
            ) : (
              assetsByStaff.map((row) => (
                <div key={`${row.staff_member_id}-${row.staff_name}`} className="rounded-lg border p-3">
                  <div className="font-medium">{row.staff_name}</div>
                  <div className="text-sm text-muted-foreground">Assets: {row.assets_count}</div>
                  <ul className="mt-2 list-disc space-y-1 pl-5 text-sm">
                    {row.assets.map((asset) => (
                      <li key={`${asset.asset_id}-${asset.asset_code}`}>
                        {asset.asset_code ?? "-"} | {asset.asset_name ?? "-"} | {asset.serial_number ?? "No Serial"}
                        {asset.project_name ? ` | ${asset.project_name}` : ""}
                      </li>
                    ))}
                  </ul>
                </div>
              ))
            )}
          </div>
        </section>

        <section className="overflow-x-auto rounded-xl border bg-white shadow-sm">
          <div className="border-b px-4 py-3">
            <h2 className="text-base font-semibold">Recent Assignment Activity</h2>
          </div>
          <table className="min-w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-3 py-2 text-left">Asset</th>
                <th className="px-3 py-2 text-left">Status</th>
                <th className="px-3 py-2 text-left">Target</th>
                <th className="px-3 py-2 text-left">Assigned</th>
                <th className="px-3 py-2 text-left">Returned</th>
                <th className="px-3 py-2 text-left">Notes</th>
              </tr>
            </thead>
            <tbody>
              {activityRows.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-3 py-4 text-muted-foreground">No assignment activity yet.</td>
                </tr>
              ) : (
                activityRows.map((row) => (
                  <tr key={row.id} className="border-t">
                    <td className="px-3 py-2">
                      {row.asset_code ?? "-"} | {row.asset ?? "-"}
                    </td>
                    <td className="px-3 py-2 capitalize">{row.status}</td>
                    <td className="px-3 py-2">{row.target}</td>
                    <td className="px-3 py-2">{row.assigned_at ?? "-"}</td>
                    <td className="px-3 py-2">{row.returned_at ?? "-"}</td>
                    <td className="px-3 py-2">{row.notes ?? "-"}</td>
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
