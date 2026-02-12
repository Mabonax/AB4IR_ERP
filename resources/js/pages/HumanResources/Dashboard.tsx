import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { humanResourcesNavItems } from "@/config/domain-nav/human-resources";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "/human-resources" },
];

export default function HumanResourcesDashboard({
  stats,
}: {
  stats: {
    totalStaff: number;
    activeStaff: number;
    inactiveStaff: number;
    pendingManager: number;
    pendingHr: number;
    approved: number;
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Human Resources" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Human Resources</h1>
          <DomainNav items={humanResourcesNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Total Staff</CardTitle>
              <CardDescription>All staff members</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalStaff}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Staff</CardTitle>
              <CardDescription>Currently active</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.activeStaff}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Inactive Staff</CardTitle>
              <CardDescription>Currently inactive</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.inactiveStaff}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Manager Approvals</CardTitle>
              <CardDescription>Pending</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.pendingManager}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>HR Approvals</CardTitle>
              <CardDescription>Pending</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.pendingHr}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Approved Leaves</CardTitle>
              <CardDescription>Total</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.approved}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
