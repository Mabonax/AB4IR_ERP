import { Head, Link, router, usePage } from "@inertiajs/react";

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
  canManageStaff,
  canPromoteManager,
}: {
  staff: any;
  isSelf: boolean;
  canManageStaff: boolean;
  canPromoteManager: boolean;
}) {
  const data = staff?.data ?? staff;
  const { props } = usePage<{ flash?: Record<string, unknown> }>();
  const flash = props.flash ?? {};
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
          <div className="flex flex-wrap items-center gap-2">
            <DomainNav items={staffNavItems} />
            {canManageStaff ? (
              <Link
                href={`/staff/${data.id}/edit`}
                className="inline-flex items-center rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
              >
                Edit Staff Member
              </Link>
            ) : null}
            {canPromoteManager ? (
              <button
                type="button"
                onClick={() => {
                  if (!window.confirm(`Promote ${data.first_name} ${data.last_name} to manager?`)) {
                    return;
                  }

                  router.post(`/staff/${data.id}/promote-manager`);
                }}
                className="inline-flex items-center rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
              >
                Promote to Manager
              </button>
            ) : null}
            {canManageStaff ? (
              <button
                type="button"
                onClick={() => {
                  if (!window.confirm(`Delete ${data.first_name} ${data.last_name}?`)) {
                    return;
                  }

                  router.delete(`/staff/${data.id}`);
                }}
                className="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
              >
                Delete Staff Member
              </button>
            ) : null}
          </div>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

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
                <span className="text-muted-foreground">Manager</span>
                <span>{data.is_manager ? "Yes" : "No"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">CEO</span>
                <span>{data.is_ceo ? "Yes" : "No"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Board Member</span>
                <span>{data.is_board_member ? "Yes" : "No"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Intern</span>
                <span>{data.is_intern ? "Yes" : "No"}</span>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Leave Account</CardTitle>
            <CardDescription>Annual and sick leave visibility</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4 text-sm">
            <div className="rounded-lg border p-4">
              <div className="text-muted-foreground">Annual Available</div>
              <div className="mt-1 text-2xl font-semibold">{data.leave_account?.annual?.available ?? 0}</div>
            </div>
            <div className="rounded-lg border p-4">
              <div className="text-muted-foreground">Annual Taken</div>
              <div className="mt-1 text-2xl font-semibold">{data.leave_account?.annual?.taken ?? 0}</div>
            </div>
            <div className="rounded-lg border p-4">
              <div className="text-muted-foreground">Sick Available</div>
              <div className="mt-1 text-2xl font-semibold">{data.leave_account?.sick?.available ?? 0}</div>
            </div>
            <div className="rounded-lg border p-4">
              <div className="text-muted-foreground">Sick Taken</div>
              <div className="mt-1 text-2xl font-semibold">{data.leave_account?.sick?.taken ?? 0}</div>
            </div>
            <div className="rounded-lg border p-4 md:col-span-2">
              <div className="text-muted-foreground">Current Period</div>
              <div className="mt-1 font-medium">
                {data.leave_account?.period_start ?? "-"} to {data.leave_account?.period_end ?? "-"}
              </div>
            </div>
            <div className="rounded-lg border p-4">
              <div className="text-muted-foreground">Pending Requests</div>
              <div className="mt-1 text-2xl font-semibold">{data.leave_account?.pending?.count ?? 0}</div>
            </div>
            <div className="rounded-lg border p-4">
              <div className="text-muted-foreground">Pending Days</div>
              <div className="mt-1 text-2xl font-semibold">{data.leave_account?.pending?.days ?? 0}</div>
            </div>
          </CardContent>
        </Card>

        {data.is_intern ? (
          <Card>
            <CardHeader>
              <CardTitle>Internship</CardTitle>
              <CardDescription>Sponsorship and placement window</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Sponsor</span>
                <span>{data.intern_sponsor_name ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Start Date</span>
                <span>{data.internship_start_date ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">End Date</span>
                <span>{data.internship_end_date ?? "-"}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Duration</span>
                <span>{data.internship_duration?.label ?? "-"}</span>
              </div>
            </CardContent>
          </Card>
        ) : null}

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
