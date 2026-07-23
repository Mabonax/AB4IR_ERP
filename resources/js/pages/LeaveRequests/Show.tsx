import { Head, router, usePage } from "@inertiajs/react";
import { File, FileImage, FileText } from "lucide-react";
import { type FormEvent, useState } from "react";

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
  const [supportingDocumentKind, setSupportingDocumentKind] = useState("signed_leave_form");

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

  const uploadSupportingDocument = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = event.currentTarget;

    router.post(`/leave-requests/${leaveRequest.id}/documents`, new FormData(form), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };

  const fileIcon = (document: any) => {
    const mimeType = String(document.mime_type ?? "");

    if (mimeType.includes("image")) {
      return <FileImage className="h-5 w-5 text-emerald-600" />;
    }

    if (mimeType.includes("pdf") || mimeType.includes("word")) {
      return <FileText className="h-5 w-5 text-blue-600" />;
    }

    return <File className="h-5 w-5 text-slate-500" />;
  };

  const roleContext = leaveRequest.permissions?.is_hr_user
    ? {
        title: "HR Review",
        description: "This request is in the human resources review workflow.",
        className: "border-green-200 bg-green-50 text-green-800",
      }
    : leaveRequest.permissions?.is_manager_user
      ? {
          title: "Manager Review",
          description: "This request belongs to your reporting line and may require your decision.",
          className: "border-blue-200 bg-blue-50 text-blue-800",
        }
      : leaveRequest.permissions?.is_requester
        ? {
            title: "Requester View",
            description: "This is your leave request record and status history.",
            className: "border-amber-200 bg-amber-50 text-amber-800",
          }
        : {
            title: "Leave Record",
            description: "You are viewing this request based on your allowed leave visibility.",
            className: "border-slate-200 bg-slate-50 text-slate-800",
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

        <div className={`rounded-lg border px-4 py-3 ${roleContext.className}`}>
          <div className="text-sm font-semibold">{roleContext.title}</div>
          <div className="text-sm opacity-90">{roleContext.description}</div>
        </div>

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

            <Card>
              <CardHeader>
                <CardTitle>Supporting Documents</CardTitle>
                <CardDescription>
                  Upload signed leave forms, medical certificates, and other evidence. Files are stored in the employee evidence library for this request.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {leaveRequest.permissions?.can_upload_supporting_documents ? (
                  <form onSubmit={uploadSupportingDocument} className="grid gap-3 rounded-lg border p-3 md:grid-cols-2">
                    <div>
                      <label className="text-xs font-medium text-muted-foreground">Document type</label>
                      <select
                        name="document_kind"
                        value={supportingDocumentKind}
                        onChange={(event) => setSupportingDocumentKind(event.target.value)}
                        className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      >
                        <option value="signed_leave_form">Signed leave form</option>
                        <option value="medical_certificate">Medical certificate</option>
                        <option value="manager_support">Manager support</option>
                        <option value="other">Other evidence</option>
                      </select>
                    </div>
                    <div>
                      <label className="text-xs font-medium text-muted-foreground">Title</label>
                      <input name="title" className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Optional title" />
                    </div>
                    <div className="md:col-span-2">
                      <label className="text-xs font-medium text-muted-foreground">Description</label>
                      <textarea name="description" rows={2} className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Optional notes" />
                    </div>
                    <div className="md:col-span-2">
                      <label className="text-xs font-medium text-muted-foreground">File</label>
                      <input
                        name="file"
                        type="file"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        required
                      />
                      <div className="mt-1 text-xs text-muted-foreground">
                        Accepted: PDF, Word, JPG, PNG. Maximum 25 MB.
                      </div>
                    </div>
                    <button type="submit" className="rounded-md bg-slate-900 px-3 py-2 text-sm text-white hover:bg-slate-800 md:col-span-2">
                      Upload Supporting Document
                    </button>
                  </form>
                ) : (
                  <div className="rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                    You can view this leave request, but your role cannot add supporting documents to it.
                  </div>
                )}

                <div className="divide-y rounded-lg border">
                  {(leaveRequest.documents ?? []).length === 0 ? (
                    <div className="px-3 py-4 text-sm text-muted-foreground">No supporting documents uploaded yet.</div>
                  ) : null}

                  {(leaveRequest.documents ?? []).map((document: any) => (
                    <div key={document.id} className="flex flex-wrap items-center justify-between gap-3 px-3 py-3">
                      <div className="flex min-w-0 items-center gap-3">
                        {fileIcon(document)}
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium">{document.title ?? document.original_name}</div>
                          <div className="truncate text-xs text-muted-foreground">
                            {document.original_name} | {document.document_kind?.replaceAll("_", " ")} | {document.uploaded_by_name ?? "-"}
                          </div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <a href={document.download_url} className="rounded-md border px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">
                          Download
                        </a>
                        {document.can_delete ? (
                          <button
                            type="button"
                            onClick={() => {
                              if (window.confirm("Delete this supporting document?")) {
                                router.delete(document.delete_url, { preserveScroll: true });
                              }
                            }}
                            className="rounded-md border border-rose-300 px-3 py-2 text-xs text-rose-700 hover:bg-rose-50"
                          >
                            Delete
                          </button>
                        ) : null}
                      </div>
                    </div>
                  ))}
                </div>
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
