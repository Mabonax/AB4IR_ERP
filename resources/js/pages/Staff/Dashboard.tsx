import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { staffNavItems } from "@/config/domain-nav/staff";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff", href: "/staff" },
];

export default function StaffDashboard({
  stats,
  managerLeave,
}: {
  stats: {
    totalStaff: number;
    activeStaff: number;
    inactiveStaff: number;
    departmentCount: number;
  };
  managerLeave: {
    pending_approvals: number;
    team_members: number;
    team_annual_available: number;
    team_sick_available: number;
    team: {
      staff_name: string;
      department_name: string | null;
      leave_account: {
        annual: { available: number };
        sick: { available: number };
        pending: { count: number };
      };
    }[];
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

        {managerLeave.team_members > 0 ? (
          <div className="space-y-4">
            <div>
              <h2 className="text-lg font-semibold">Manager Leave View</h2>
              <p className="text-sm text-muted-foreground">
                Direct report balances and pending approvals.
              </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <Card>
                <CardHeader>
                  <CardTitle>Pending Approvals</CardTitle>
                </CardHeader>
                <CardContent className="text-2xl font-semibold">
                  {managerLeave.pending_approvals}
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>Team Members</CardTitle>
                </CardHeader>
                <CardContent className="text-2xl font-semibold">
                  {managerLeave.team_members}
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>Annual Available</CardTitle>
                </CardHeader>
                <CardContent className="text-2xl font-semibold">
                  {managerLeave.team_annual_available}
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>Sick Available</CardTitle>
                </CardHeader>
                <CardContent className="text-2xl font-semibold">
                  {managerLeave.team_sick_available}
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardHeader>
                <CardTitle>Direct Report Leave Accounts</CardTitle>
                <CardDescription>Current balances for your team</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {managerLeave.team.map((member) => (
                  <div key={member.staff_name} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                      <div>
                        <div className="font-medium">{member.staff_name}</div>
                        <div className="text-sm text-muted-foreground">{member.department_name ?? "No department"}</div>
                      </div>
                      <div className="grid gap-2 text-sm sm:grid-cols-3">
                        <div>Annual: {member.leave_account.annual.available}</div>
                        <div>Sick: {member.leave_account.sick.available}</div>
                        <div>Pending: {member.leave_account.pending.count}</div>
                      </div>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        ) : null}
      </div>
    </AppLayout>
  );
}
