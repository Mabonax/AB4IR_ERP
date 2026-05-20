import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { humanResourcesNavItems } from "@/config/domain-nav/human-resources";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

export default function LeaveRequestShow({
  leaveRequest,
}: {
  leaveRequest: any;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [managerComment, setManagerComment] = useState(leaveRequest.manager_comment ?? "");
  const [hrComment, setHrComment] = useState(leaveRequest.hr_comment ?? "");

  const submitManagerAction = (action: "approve" | "reject") => {
    router.post(`/leave-requests/${leaveRequest.id}/manager-${action}`, {
      manager_comment: managerComment,
    });
  };

  const submitHrAction = (action: "approve" | "reject") => {
    router.post(`/leave-requests/${leaveRequest.id}/hr-${action}`, {
      hr_comment: hrComment,
    });
  };

  const revokeRequest = () => {
    router.post(`/leave-requests/${leaveRequest.id}/revoke`);
  };

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Human Resources", href: "/human-resources" },
    { title: "Leave Management", href: "/leave-requests" },
    { title: `Request #${leaveRequest.id}`, href: `/leave-requests/${leaveRequest.id}` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Leave Request #${leaveRequest.id}`} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Leave Request #{leaveRequest.id}</h1>
            <p className="text-sm text-muted-foreground">
              Detailed leave request record with status history and approval actions.
            </p>
          </div>
          <DomainNav items={humanResourcesNavItems} />
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        <div className="grid gap-4 lg:grid-cols-3">
          <div className="space-y-4 lg:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle>Request Summary</CardTitle>
                <CardDescription>Core leave request details and calculated duration.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
                <div>
                  <div className="text-muted-foreground">Employee</div>
                  <div className="font-medium">{leaveRequest.staff_member?.name ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Employee Email</div>
                  <div className="font-medium">{leaveRequest.staff_member?.email ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Employee Number</div>
                  <div className="font-medium">{leaveRequest.staff_member?.employee_number ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Department</div>
                  <div className="font-medium">{leaveRequest.staff_member?.department_name ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Manager</div>
                  <div className="font-medium">{leaveRequest.manager?.name ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Manager Email</div>
                  <div className="font-medium">{leaveRequest.manager?.email ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Leave Type</div>
                  <div className="font-medium">{leaveRequest.leave_type_label}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Status</div>
                  <div className="font-medium">{leaveRequest.status_label}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Requested Days</div>
                  <div className="font-medium">{leaveRequest.requested_period?.total_days ?? 0}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Start Date</div>
                  <div className="font-medium">{leaveRequest.requested_period?.start_date ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">End Date</div>
                  <div className="font-medium">{leaveRequest.requested_period?.end_date ?? "-"}</div>
                </div>
                <div>
                  <div className="text-muted-foreground">Calculated Working Days</div>
                  <div className="font-medium">{leaveRequest.requested_period?.calculated_working_days ?? 0}</div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Reason</CardTitle>
                <CardDescription>Submitted justification for the requested leave period.</CardDescription>
              </CardHeader>
              <CardContent className="text-sm">
                <div className="rounded-md border bg-muted/30 px-3 py-3">
                  {leaveRequest.reason || "No reason provided."}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Approval Timeline</CardTitle>
                <CardDescription>Submission, review, and final approval milestones.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {leaveRequest.timeline.map((item: any) => (
                  <div key={item.key} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <h3 className="font-medium">{item.label}</h3>
                        <p className="text-sm text-muted-foreground">
                          {item.actor || "-"}
                        </p>
                      </div>
                      <div className="text-sm text-muted-foreground">
                        {item.timestamp || "-"}
                      </div>
                    </div>
                    {item.comment ? (
                      <div className="mt-3 rounded-md border bg-muted/30 px-3 py-2 text-sm">
                        {item.comment}
                      </div>
                    ) : null}
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>

          <div className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Record Metadata</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2 text-sm">
                <div className="flex justify-between gap-3">
                  <span className="text-muted-foreground">Submitted At</span>
                  <span>{leaveRequest.submitted_at ?? "-"}</span>
                </div>
                <div className="flex justify-between gap-3">
                  <span className="text-muted-foreground">Manager Decision</span>
                  <span>{leaveRequest.manager_approved_at ?? "-"}</span>
                </div>
                <div className="flex justify-between gap-3">
                  <span className="text-muted-foreground">HR Decision</span>
                  <span>{leaveRequest.hr_approved_at ?? "-"}</span>
                </div>
                <div className="flex justify-between gap-3">
                  <span className="text-muted-foreground">Last Updated</span>
                  <span>{leaveRequest.updated_at ?? "-"}</span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Manager Review</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="rounded-md border bg-muted/30 px-3 py-2">
                  {leaveRequest.manager_comment || "No manager comment recorded."}
                </div>

                {leaveRequest.permissions?.can_manager_approve || leaveRequest.permissions?.can_manager_reject ? (
                  <>
                    <textarea
                      rows={3}
                      value={managerComment}
                      onChange={(e) => setManagerComment(e.currentTarget.value)}
                      placeholder="Manager comment"
                      className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <div className="flex flex-wrap gap-2">
                      {leaveRequest.permissions?.can_manager_approve ? (
                        <button
                          type="button"
                          onClick={() => submitManagerAction("approve")}
                          className="rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700"
                        >
                          Approve
                        </button>
                      ) : null}
                      {leaveRequest.permissions?.can_manager_reject ? (
                        <button
                          type="button"
                          onClick={() => submitManagerAction("reject")}
                          className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                        >
                          Reject
                        </button>
                      ) : null}
                    </div>
                  </>
                ) : null}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>HR Review</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="rounded-md border bg-muted/30 px-3 py-2">
                  {leaveRequest.hr_comment || "No HR comment recorded."}
                </div>

                {leaveRequest.permissions?.can_hr_approve || leaveRequest.permissions?.can_hr_reject ? (
                  <>
                    <textarea
                      rows={3}
                      value={hrComment}
                      onChange={(e) => setHrComment(e.currentTarget.value)}
                      placeholder="HR comment"
                      className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <div className="flex flex-wrap gap-2">
                      {leaveRequest.permissions?.can_hr_approve ? (
                        <button
                          type="button"
                          onClick={() => submitHrAction("approve")}
                          className="rounded-md bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700"
                        >
                          Approve
                        </button>
                      ) : null}
                      {leaveRequest.permissions?.can_hr_reject ? (
                        <button
                          type="button"
                          onClick={() => submitHrAction("reject")}
                          className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                        >
                          Reject
                        </button>
                      ) : null}
                    </div>
                  </>
                ) : null}
              </CardContent>
            </Card>

            {leaveRequest.permissions?.can_revoke ? (
              <Card>
                <CardHeader>
                  <CardTitle>Requester Action</CardTitle>
                </CardHeader>
                <CardContent>
                  <button
                    type="button"
                    onClick={revokeRequest}
                    className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                  >
                    Revoke Request
                  </button>
                </CardContent>
              </Card>
            ) : null}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
