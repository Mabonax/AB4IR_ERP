import { Head, router } from "@inertiajs/react";
import { useState } from "react";

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
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "/human-resources" },
  { title: "Leave Management", href: "/leave-requests" },
];

type LeaveRow = {
  id: number;
  staff_member_name?: string | null;
  department_name?: string | null;
  manager_name?: string | null;
  leave_type_label?: string;
  start_date?: string | null;
  end_date?: string | null;
  total_days?: number;
  status?: string;
  can_revoke?: boolean;
};

export default function LeaveRequestsIndex({
  myRequests,
  managerQueue,
  hrQueue,
  leaveRegister,
  teamLeaveSummary,
}: {
  myRequests: LeaveRow[];
  managerQueue: LeaveRow[];
  hrQueue: LeaveRow[];
  leaveRegister: LeaveRow[];
  teamLeaveSummary: any[];
}) {
  const [actionOpen, setActionOpen] = useState(false);
  const [actionType, setActionType] = useState<"manager_approve" | "manager_reject" | "hr_approve" | "hr_reject" | null>(null);
  const [selectedLeave, setSelectedLeave] = useState<LeaveRow | null>(null);
  const [comment, setComment] = useState("");

  const openAction = (leave: LeaveRow, type: typeof actionType) => {
    setSelectedLeave(leave);
    setActionType(type);
    setComment("");
    setActionOpen(true);
  };

  const submitAction = (e: React.FormEvent) => {
    e.preventDefault();
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
      onSuccess: () => {
        setActionOpen(false);
      },
    });
  };

  const revokeRequest = (leave: LeaveRow) => {
    router.post(`/leave-requests/${leave.id}/revoke`, {}, { preserveScroll: true });
  };

  const columns = [
    { label: "Employee", key: "staff_name", className: "px-4 py-2 text-left" },
    { label: "Department", key: "department_name", className: "px-4 py-2 text-left" },
    { label: "Type", key: "leave_type_label", className: "px-4 py-2 text-left" },
    { label: "Start", key: "start_date", className: "px-4 py-2 text-left" },
    { label: "End", key: "end_date", className: "px-4 py-2 text-left" },
    { label: "Days", key: "total_days", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Manager", key: "manager_name", className: "px-4 py-2 text-left" },
  ];

  const mapRequests = (items: LeaveRow[]) =>
    items.map((item) => ({
      ...item,
      staff_name: item.staff_member_name ?? "-",
      department_name: item.department_name ?? "-",
      manager_name: item.manager_name ?? "-",
    }));

  const teamColumns = [
    { label: "Employee", key: "staff_name", className: "px-4 py-2 text-left" },
    { label: "Department", key: "department_name", className: "px-4 py-2 text-left" },
    { label: "Annual Available", key: "annual_available", className: "px-4 py-2 text-left" },
    { label: "Sick Available", key: "sick_available", className: "px-4 py-2 text-left" },
    { label: "Pending Requests", key: "pending_count", className: "px-4 py-2 text-left" },
  ];

  const mappedTeam = teamLeaveSummary.map((item) => ({
    staff_name: item.staff_name,
    department_name: item.department_name ?? "-",
    annual_available: item.leave_account?.annual?.available ?? 0,
    sick_available: item.leave_account?.sick?.available ?? 0,
    pending_count: item.leave_account?.pending?.count ?? 0,
  }));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Leave Management" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Leave Management</h1>
          <DomainNav items={humanResourcesNavItems} />
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Leave Register</CardTitle>
            <CardDescription>Approved leave records (who is on leave and for which dates)</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable columns={columns} data={mapRequests(leaveRegister)} actions={[]} />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>My Requests</CardTitle>
            <CardDescription>Submitted leave requests</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={[...columns, { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" }]}
              data={mapRequests(myRequests)}
              actions={[
                {
                  icon: "Undo2",
                  label: "Revoke leave request",
                  variant: "danger",
                  visible: (row) => row.can_revoke === true,
                  onClick: (row) => revokeRequest(row),
                },
              ]}
            />
          </CardContent>
        </Card>

        {mappedTeam.length > 0 ? (
          <Card>
            <CardHeader>
              <CardTitle>Team Leave Accounts</CardTitle>
              <CardDescription>Direct reports and their current leave balances</CardDescription>
            </CardHeader>
            <CardContent>
              <CustomTable columns={teamColumns} data={mappedTeam} actions={[]} />
            </CardContent>
          </Card>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Manager Approvals</CardTitle>
            <CardDescription>Requests awaiting manager action</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={[...columns, { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" }]}
              data={mapRequests(managerQueue)}
              actions={[
                {
                  icon: "CheckCircle",
                  label: "Approve request",
                  onClick: (row) => openAction(row, "manager_approve"),
                },
                {
                  icon: "XCircle",
                  label: "Reject request",
                  variant: "danger",
                  onClick: (row) => openAction(row, "manager_reject"),
                },
              ]}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>HR Approvals</CardTitle>
            <CardDescription>Requests awaiting HR action</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={[...columns, { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" }]}
              data={mapRequests(hrQueue)}
              actions={[
                {
                  icon: "CheckCircle",
                  label: "Approve request",
                  onClick: (row) => openAction(row, "hr_approve"),
                },
                {
                  icon: "XCircle",
                  label: "Reject request",
                  variant: "danger",
                  onClick: (row) => openAction(row, "hr_reject"),
                },
              ]}
            />
          </CardContent>
        </Card>
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
