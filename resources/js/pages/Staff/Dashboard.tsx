import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { staffNavItems } from "@/config/domain-nav/staff";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff", href: "/staff" },
];

export default function StaffDashboard({
  stats,
}: {
  stats: {
    totalStaff: number;
    activeStaff: number;
    inactiveStaff: number;
    departmentCount: number;
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Staff Dashboard" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Staff Dashboard</h1>
          <DomainNav items={staffNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
              <CardTitle>Active</CardTitle>
              <CardDescription>Currently active</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.activeStaff}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Inactive</CardTitle>
              <CardDescription>Not active</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.inactiveStaff}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Departments</CardTitle>
              <CardDescription>Total departments</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.departmentCount}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
