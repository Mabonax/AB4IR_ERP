import { Head } from "@inertiajs/react";
import { useState } from "react";

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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { CustomTable } from "@/components/custom-table";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "/human-resources" },
  { title: "Leave Management", href: "/leave-requests" },
];

export default function LeaveRequestsIndex({
  myRequests,
  managerQueue,
  hrQueue,
}: {
  myRequests: any[];
  managerQueue: any[];
  hrQueue: any[];
}) {
  const [actionOpen, setActionOpen] = useState(false);
  const [actionType, setActionType] = useState<"manager_approve" | "manager_reject" | "hr_approve" | "hr_reject" | null>(null);
  const [selectedLeave, setSelectedLeave] = useState<any | null>(null);
  const [comment, setComment] = useState("");

  const openAction = (leave: any, type: typeof actionType) => {
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

  const columns = [
    { label: "Employee", key: "staff_name", className: "px-4 py-2 text-left" },
    { label: "Start", key: "start_date", className: "px-4 py-2 text-left" },
    { label: "End", key: "end_date", className: "px-4 py-2 text-left" },
    { label: "Days", key: "total_days", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Manager", key: "manager_name", className: "px-4 py-2 text-left" },
  ];

  const mapRequests = (items: any[]) =>
    items.map((item) => ({
      ...item,
      staff_name: item.staff_member_name ?? "-",
      manager_name: item.manager_name ?? "-",
    }));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Leave Management" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Leave Management</h1>
          <DomainNav items={humanResourcesNavItems} />
        </div>

        <Card>
          <CardHeader>
            <CardTitle>My Requests</CardTitle>
            <CardDescription>Submitted leave requests</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable columns={columns} data={mapRequests(myRequests)} actions={[]} />
          </CardContent>
        </Card>

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
                  onClick: (row) => openAction(row, "manager_approve"),
                },
                {
                  icon: "XCircle",
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
                  onClick: (row) => openAction(row, "hr_approve"),
                },
                {
                  icon: "XCircle",
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
              {selectedLeave?.staff_member_name ?? "Employee"} •{" "}
              {selectedLeave?.start_date ?? "-"} to {selectedLeave?.end_date ?? "-"}
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={submitAction} className="grid gap-3">
            <textarea
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder="Comment (optional)"
              className="rounded-md border px-3 py-2 text-sm"
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
