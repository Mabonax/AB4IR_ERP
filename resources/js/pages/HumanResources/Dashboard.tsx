import { Head, Link, router } from "@inertiajs/react";
import { Users, UserPlus } from "lucide-react";
import { useState } from "react";

import { Button } from "@/components/ui/button";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { humanResourcesNavItems } from "@/config/domain-nav/human-resources";
import AppLayout from "@/layouts/app-layout";
import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "/human-resources" },
];

type PendingLeaveApproval = {
  id: number;
  staff_member_name: string | null;
  department_name: string | null;
  manager_name: string | null;
  leave_type_label: string;
  start_date: string | null;
  end_date: string | null;
  total_days: number;
  status: string;
};

export default function HumanResourcesDashboard({
  stats,
  departments,
  leaveSummary,
  staffDirectory,
  pendingLeaveApprovals,
  selectedDepartmentId,
  canManageManagerLeave,
  canManageHrLeave,
}: {
  stats: {
    totalStaff: number;
    activeStaff: number;
    inactiveStaff: number;
    pendingManager: number;
    pendingHr: number;
    approved: number;
  };
  departments: {
    id: number;
    name: string;
    description?: string | null;
    staff_count: number;
  }[];
  leaveSummary: {
    totals: {
      annual_taken: number;
      annual_available: number;
      sick_taken: number;
      sick_available: number;
    };
    staff: {
      staff_id: number;
      staff_name: string;
      department_name: string | null;
      leave_account: {
        annual: { available: number; taken: number };
        sick: { available: number; taken: number };
        pending: { count: number };
      };
    }[];
  };
  staffDirectory: {
    id: number;
    name: string;
    email: string;
    employee_number: string;
    status: string;
    department_id: number | null;
    department_name: string | null;
    manager_name: string | null;
  }[];
  pendingLeaveApprovals: PendingLeaveApproval[];
  selectedDepartmentId: number | null;
  canManageManagerLeave: boolean;
  canManageHrLeave: boolean;
}) {
  const [actionOpen, setActionOpen] = useState(false);
  const [actionType, setActionType] = useState<"manager_approve" | "manager_reject" | "hr_approve" | "hr_reject" | null>(null);
  const [selectedLeave, setSelectedLeave] = useState<PendingLeaveApproval | null>(null);
  const [comment, setComment] = useState("");

  const leaveColumns = [
    { label: "Employee", key: "staff_name", className: "px-4 py-2 text-left" },
    { label: "Department", key: "department_name", className: "px-4 py-2 text-left" },
    { label: "Annual Available", key: "annual_available", className: "px-4 py-2 text-left" },
    { label: "Annual Taken", key: "annual_taken", className: "px-4 py-2 text-left" },
    { label: "Sick Available", key: "sick_available", className: "px-4 py-2 text-left" },
    { label: "Sick Taken", key: "sick_taken", className: "px-4 py-2 text-left" },
    { label: "Pending", key: "pending_count", className: "px-4 py-2 text-left" },
  ];

  const leaveRows = leaveSummary.staff.map((item) => ({
    staff_name: item.staff_name,
    department_name: item.department_name ?? "-",
    annual_available: item.leave_account.annual.available,
    annual_taken: item.leave_account.annual.taken,
    sick_available: item.leave_account.sick.available,
    sick_taken: item.leave_account.sick.taken,
    pending_count: item.leave_account.pending.count,
  }));

  const pendingColumns = [
    { label: "Employee", key: "staff_member_name", className: "px-4 py-2 text-left" },
    { label: "Department", key: "department_name", className: "px-4 py-2 text-left" },
    { label: "Type", key: "leave_type_label", className: "px-4 py-2 text-left" },
    { label: "Start", key: "start_date", className: "px-4 py-2 text-left" },
    { label: "End", key: "end_date", className: "px-4 py-2 text-left" },
    { label: "Days", key: "total_days", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ];

  const staffColumns = [
    { label: "Employee", key: "name", className: "px-4 py-2 text-left" },
    { label: "Department", key: "department_name", className: "px-4 py-2 text-left" },
    { label: "Employee #", key: "employee_number", className: "px-4 py-2 text-left" },
    { label: "Email", key: "email", className: "px-4 py-2 text-left" },
    { label: "Manager", key: "manager_name", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
  ];

  const staffRows = staffDirectory.map((item) => ({
    ...item,
    department_name: item.department_name ?? "-",
    manager_name: item.manager_name ?? "-",
  }));

  const openAction = (leave: PendingLeaveApproval, type: typeof actionType) => {
    setSelectedLeave(leave);
    setActionType(type);
    setComment("");
    setActionOpen(true);
  };

  const submitAction = (event: React.FormEvent) => {
    event.preventDefault();
    if (!selectedLeave || !actionType) return;

    const url =
      actionType === "manager_approve"
        ? `/leave-requests/${selectedLeave.id}/manager-approve`
        : actionType === "manager_reject"
          ? `/leave-requests/${selectedLeave.id}/manager-reject`
          : actionType === "hr_approve"
            ? `/leave-requests/${selectedLeave.id}/hr-approve`
            : `/leave-requests/${selectedLeave.id}/hr-reject`;

    const payload =
      actionType === "manager_approve" || actionType === "manager_reject"
        ? { manager_comment: comment }
        : { hr_comment: comment };

    router.post(url, payload, {
      preserveScroll: true,
      onSuccess: () => setActionOpen(false),
    });
  };

  const approvalTitle = canManageHrLeave
    ? "HR Leave Approvals"
    : "Manager Leave Approvals";
  const approvalDescription = canManageHrLeave
    ? "Requests that are ready for HR action."
    : "Requests from your reporting line that still need your decision.";

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Human Resources" />

      <div className="space-y-6 p-4">
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
            <CardContent className="text-2xl font-semibold">{stats.totalStaff}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Staff</CardTitle>
              <CardDescription>Currently active</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.activeStaff}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Inactive Staff</CardTitle>
              <CardDescription>Currently inactive</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.inactiveStaff}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Manager Approvals</CardTitle>
              <CardDescription>Pending</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.pendingManager}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>HR Approvals</CardTitle>
              <CardDescription>Pending</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.pendingHr}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Approved Leaves</CardTitle>
              <CardDescription>Total</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.approved}</CardContent>
          </Card>
        </div>

        {(canManageManagerLeave || canManageHrLeave) ? (
          <Card>
            <CardHeader>
              <CardTitle>{approvalTitle}</CardTitle>
              <CardDescription>{approvalDescription}</CardDescription>
            </CardHeader>
            <CardContent>
              <CustomTable
                columns={pendingColumns}
                data={pendingLeaveApprovals}
                actions={[
                  {
                    icon: "CheckCircle",
                    label: "Approve request",
                    onClick: (row) =>
                      openAction(row, canManageHrLeave ? "hr_approve" : "manager_approve"),
                  },
                  {
                    icon: "XCircle",
                    label: "Reject request",
                    variant: "danger",
                    onClick: (row) =>
                      openAction(row, canManageHrLeave ? "hr_reject" : "manager_reject"),
                  },
                ]}
              />
            </CardContent>
          </Card>
        ) : null}

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Total Annual Available</CardTitle>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{leaveSummary.totals.annual_available}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Total Annual Taken</CardTitle>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{leaveSummary.totals.annual_taken}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Total Sick Available</CardTitle>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{leaveSummary.totals.sick_available}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Total Sick Taken</CardTitle>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{leaveSummary.totals.sick_taken}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Department Staff Directory</CardTitle>
            <CardDescription>Filter staff by department and open their records for updates.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex flex-wrap items-center gap-3">
              <select
                value={selectedDepartmentId ? String(selectedDepartmentId) : ""}
                onChange={(e) =>
                  router.get(
                    "/human-resources",
                    e.currentTarget.value ? { department_id: e.currentTarget.value } : {},
                    { preserveScroll: true, preserveState: true }
                  )
                }
                className="w-full max-w-sm rounded-md border bg-card px-3 py-2 text-sm text-foreground"
              >
                <option value="">All departments</option>
                {departments.map((department) => (
                  <option key={department.id} value={department.id}>
                    {department.name}
                  </option>
                ))}
              </select>

              <Button
                variant="outline"
                onClick={() => router.get("/human-resources", {}, { preserveScroll: true, preserveState: true })}
              >
                Reset Filter
              </Button>
            </div>

            <CustomTable
              columns={staffColumns}
              data={staffRows}
              actions={[
                {
                  icon: "Eye",
                  label: "View staff member",
                  onClick: (row) => {
                    window.location.href = `/staff/${row.id}/profile`;
                  },
                },
                {
                  icon: "PencilIcon",
                  label: "Edit staff member",
                  onClick: (row) => {
                    window.location.href = `/staff/${row.id}/edit`;
                  },
                },
              ]}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Staff Leave Accounts</CardTitle>
            <CardDescription>Annual and sick leave balances across the organisation</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable columns={leaveColumns} data={leaveRows} actions={[]} />
          </CardContent>
        </Card>

        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-lg font-semibold">Departments</h2>
              <p className="text-sm text-muted-foreground">
                Add staff directly into the correct department.
              </p>
            </div>

            <Link href="/staff">
              <Button variant="outline">Open Staff Registry</Button>
            </Link>
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {departments.map((department) => (
              <Card key={department.id} className="border-orange-200">
                <CardHeader className="space-y-3">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <CardTitle>{department.name}</CardTitle>
                      <CardDescription>
                        {department.description || "Department staff allocation"}
                      </CardDescription>
                    </div>
                    <div className="rounded-full bg-orange-100 p-2 text-orange-600">
                      <Users className="h-4 w-4" />
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <div className="text-2xl font-semibold">{department.staff_count}</div>
                    <p className="text-xs text-muted-foreground">staff members assigned</p>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <Button asChild className="bg-red-600 hover:bg-red-700">
                      <Link href={staff.create.url({ query: { department_id: department.id } })}>
                        <UserPlus className="mr-2 h-4 w-4" />
                        Add Staff
                      </Link>
                    </Button>
                    <Button
                      variant="outline"
                      onClick={() =>
                        router.get("/human-resources", { department_id: department.id }, { preserveScroll: true })
                      }
                    >
                      View Staff
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </div>

      <Dialog open={actionOpen} onOpenChange={setActionOpen}>
        <DialogContent className="sm:max-w-[520px]">
          <DialogHeader>
            <DialogTitle>Leave Decision</DialogTitle>
            <DialogDescription>
              {selectedLeave?.staff_member_name ?? "Employee"} {" • "}
              {selectedLeave?.start_date ?? "-"} to {selectedLeave?.end_date ?? "-"}
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={submitAction} className="grid gap-3">
            <textarea
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder="Comment (optional)"
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
            />
            <button
              type="submit"
              className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
            >
              Confirm
            </button>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
