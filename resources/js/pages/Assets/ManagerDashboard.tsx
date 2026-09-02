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
  { title: "Manager Analytics", href: "/assets/manager-dashboard" },
];

export default function AssetManagerDashboard({
  stats,
  assetRows,
  assetsByStaff,
  activityRows,
}: {
  stats: {
    portfolioAssets: number;
    departmentAssets: number;
    staffAssets: number;
    maintenanceAssets: number;
    retiredAssets: number;
    unreturnedAssets: number;
    recentActivities: number;
  };
  assetRows: Array<{
    asset_id: number;
    asset_code: string | null;
    asset_name: string | null;
    category_name: string | null;
    status: string;
    assigned_to: string | null;
    maintenance_state: string;
    maintenance_issue: string | null;
    decommissioned_at: string | null;
    updated_at: string | null;
  }>;
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
      <Head title="Asset Manager Analytics" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Asset Manager Analytics</h1>
          <DomainNav items={assetNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Portfolio</CardTitle>
              <CardDescription>Assets in your department history</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.portfolioAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Assignments</CardTitle>
              <CardDescription>Still out in circulation</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.unreturnedAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Maintenance</CardTitle>
              <CardDescription>Assets currently in repair</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.maintenanceAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Retired</CardTitle>
              <CardDescription>Decommissioned assets in scope</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.retiredAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Staff Assets</CardTitle>
              <CardDescription>Actively assigned to staff members</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.staffAssets}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Recent Activity</CardTitle>
              <CardDescription>Assignment events in department scope</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.recentActivities}</CardContent>
          </Card>
        </div>

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <div className="border-b px-4 py-3">
            <h2 className="text-base font-semibold">Department Asset Portfolio</h2>
            <p className="text-sm text-muted-foreground">
              Current state of every asset that has moved through your department.
            </p>
          </div>
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left">Asset</th>
                <th className="px-3 py-2 text-left">Category</th>
                <th className="px-3 py-2 text-left">Status</th>
                <th className="px-3 py-2 text-left">Assigned To</th>
                <th className="px-3 py-2 text-left">Maintenance</th>
                <th className="px-3 py-2 text-left">Updated</th>
              </tr>
            </thead>
            <tbody>
              {assetRows.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-3 py-4 text-muted-foreground">
                    No assets have been routed through your department yet.
                  </td>
                </tr>
              ) : (
                assetRows.map((row) => (
                  <tr key={row.asset_id} className="border-t">
                    <td className="px-3 py-2">
                      {row.asset_code ?? "-"} | {row.asset_name ?? "-"}
                    </td>
                    <td className="px-3 py-2">{row.category_name ?? "-"}</td>
                    <td className="px-3 py-2 capitalize">{row.status.replaceAll("_", " ")}</td>
                    <td className="px-3 py-2">{row.assigned_to ?? "-"}</td>
                    <td className="px-3 py-2">
                      {row.maintenance_state === "in_progress"
                        ? row.maintenance_issue ?? "In maintenance"
                        : row.maintenance_state === "history"
                          ? "Maintenance history"
                          : "None"}
                    </td>
                    <td className="px-3 py-2">{row.updated_at ?? row.decommissioned_at ?? "-"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Assets By Staff</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Only active staff allocations appear here. Assets in maintenance or retired state stay in the portfolio table above.
          </p>
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

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <div className="border-b px-4 py-3">
            <h2 className="text-base font-semibold">Recent Assignment Activity</h2>
            <p className="text-sm text-muted-foreground">
              Assignment history stays separate from the live portfolio state.
            </p>
          </div>
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
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
