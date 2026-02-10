import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { assetNavItems } from "@/config/domain-nav/assets";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Assets", href: "/assets" },
];

export default function AssetsDashboard({
  stats,
}: {
  stats: {
    totalAssets: number;
    assignedAssets: number;
    unassignedAssets: number;
    maintenanceAssets: number;
    retiredAssets: number;
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Assets Dashboard" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Assets Dashboard</h1>
          <DomainNav items={assetNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Total Assets</CardTitle>
              <CardDescription>All assets</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalAssets}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Assigned</CardTitle>
              <CardDescription>In use</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.assignedAssets}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Unassigned</CardTitle>
              <CardDescription>Available</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.unassignedAssets}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Maintenance</CardTitle>
              <CardDescription>Being fixed</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.maintenanceAssets}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Retired</CardTitle>
              <CardDescription>Out of service</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.retiredAssets}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
