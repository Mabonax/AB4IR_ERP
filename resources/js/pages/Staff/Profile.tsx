import { Head, Link } from "@inertiajs/react";

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

export default function StaffProfile({
  staff,
  isSelf,
}: {
  staff: any;
  isSelf: boolean;
}) {
  const data = staff?.data ?? staff;
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Staff", href: "/staff" },
    { title: isSelf ? "My Profile" : "Staff Profile", href: "#" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Staff Profile" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">
              {data.first_name} {data.last_name}
            </h1>
            <div className="text-sm text-muted-foreground">
              {data.department_name ?? "No department"}
            </div>
          </div>
          <DomainNav items={staffNavItems} />
        </div>

        {isSelf && (
          <div className="text-sm">
            <Link
              href="/leave-requests"
              className="text-red-600 hover:underline"
            >
              View my leave requests
            </Link>
          </div>
        )}

        <div className="grid gap-6 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Personal Information</CardTitle>
              <CardDescription>Staff details</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Email</span>
                <span>{data.email ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Phone</span>
                <span>{data.phone ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Employee #</span>
                <span>{data.employee_number ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Status</span>
                <span className="capitalize">{data.status ?? "-"}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Employment</CardTitle>
              <CardDescription>Department and manager</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Department</span>
                <span>{data.department_name ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Manager</span>
                <span>{data.manager_name ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Start Date</span>
                <span>{data.start_date ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">CEO</span>
                <span>{data.is_ceo ? "Yes" : "No"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Board Member</span>
                <span>{data.is_board_member ? "Yes" : "No"}</span>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Next of Kin</CardTitle>
            <CardDescription>Emergency contact</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3 text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Full Name</span>
              <span>{data.next_of_kin?.full_name ?? "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Relationship</span>
              <span>{data.next_of_kin?.relationship ?? "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Phone</span>
              <span>{data.next_of_kin?.phone ?? "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Email</span>
              <span>{data.next_of_kin?.email ?? "-"}</span>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
