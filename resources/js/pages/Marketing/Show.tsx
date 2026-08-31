import { Head, router, usePage } from "@inertiajs/react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type MarketingTab = "overview" | "delivery" | "approval" | "documents" | "reassign" | "comments" | "history";
type MarketingStatus = "open" | "in_progress" | "blocked" | "pending_approval" | "changes_requested" | "approved" | "cancelled";

type JobDocument = {
  id: number;
  title: string;
  document_kind: string;
  notes: string | null;
  file_name: string;
  uploaded_by_name: string | null;
  created_at: string | null;
};

type JobComment = {
  id: number;
  user_name: string | null;
  message: string;
  created_at: string | null;
};

type JobHistory = {
  id: number;
  actor_name: string | null;
  action: string;
  summary: string;
  meta?: Record<string, unknown> | null;
  created_at: string | null;
};

type JobDetail = {
  id: number;
  title: string;
  brief: string | null;
  job_type: string;
  status: MarketingStatus;
  priority: "low" | "medium" | "high" | "urgent";
  due_date: string | null;
  event_name: string | null;
  creator_name: string | null;
  creator_department_name: string | null;
  assignee_name: string | null;
  assigned_to_user_id: number | null;
  assigned_department_id: number | null;
  assigned_department_name: string | null;
  delivery_notes: string | null;
  proof_url: string | null;
  proof_file_name: string | null;
  has_proof_file: boolean;
  submitted_for_approval_at: string | null;
  submitted_by_name: string | null;
  approval_notes: string | null;
  reviewed_at: string | null;
  reviewed_by_name: string | null;
  returned_for_amendments_at: string | null;
  approved_at: string | null;
  transaction_state: "open" | "closed";
  transaction_opened_at: string | null;
  transaction_closed_at: string | null;
  closed_by_name: string | null;
  documents: JobDocument[] | { data?: JobDocument[] };
  comments: JobComment[] | { data?: JobComment[] };
  history: JobHistory[] | { data?: JobHistory[] };
  can: {
    update_status: boolean;
    comment: boolean;
    upload_document: boolean;
    reassign: boolean;
    submit_for_approval: boolean;
    approve: boolean;
    request_amendments: boolean;
  };
};

const statusBadgeClass = (status: MarketingStatus) => {
  switch (status) {
    case "approved":
      return "border-green-200 bg-green-50 text-green-700";
    case "pending_approval":
      return "border-blue-200 bg-blue-50 text-blue-700";
    case "changes_requested":
      return "border-amber-200 bg-amber-50 text-amber-700";
    case "blocked":
      return "border-rose-200 bg-rose-50 text-rose-700";
    case "cancelled":
      return "border-slate-200 bg-slate-100 text-slate-700";
    case "in_progress":
      return "border-indigo-200 bg-indigo-50 text-indigo-700";
    default:
      return "border-slate-200 bg-slate-50 text-slate-700";
  }
};

export default function MarketingShow({
  job,
  assignees,
  departments,
  users,
  documentTypes,
  slotOptions,
  defaultDocumentType,
  canManageVault,
}: {
  job: JobDetail;
  assignees: Array<{ id: number; name: string; email: string }>;
  departments: Array<{ id: number; name: string }>;
  users: Array<{ id: number; name: string; email: string }>;
  documentTypes: Array<{ value: string; label: string }>;
  slotOptions: Array<{ value: string; label: string; document_type: string }>;
  defaultDocumentType: string;
  canManageVault: boolean;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [activeTab, setActiveTab] = useState<MarketingTab>("overview");
  const documents = Array.isArray(job.documents) ? job.documents : (job.documents.data ?? []);
  const comments = Array.isArray(job.comments) ? job.comments : (job.comments.data ?? []);
  const history = Array.isArray(job.history) ? job.history : (job.history.data ?? []);
  const [vaultDocumentType, setVaultDocumentType] = useState(defaultDocumentType);
  const filteredVaultSlots = useMemo(
    () => slotOptions.filter((slot) => slot.document_type === vaultDocumentType),
    [slotOptions, vaultDocumentType],
  );

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Marketing", href: "/marketing" },
    { title: "Jobs", href: "/marketing/jobs" },
    { title: job.title, href: `/marketing/jobs/${job.id}` },
  ];

  const showDeliverySubmission = job.can.submit_for_approval && !["pending_approval", "approved", "cancelled"].includes(job.status);
  const showStatusUpdate = job.can.update_status && !["pending_approval", "approved", "cancelled"].includes(job.status);
  const canManageApproval = job.can.approve || job.can.request_amendments;
  const hasDocumentsVisibility = job.can.upload_document || documents.length > 0;
  const hasCommentsVisibility = job.can.comment || comments.length > 0;
  const tabs: Array<{ key: MarketingTab; label: string }> = [
    { key: "overview", label: "Overview" },
    { key: "delivery", label: showDeliverySubmission ? "Upload Delivery" : "Delivery" },
    ...(canManageApproval ? [{ key: "approval" as const, label: "Approval" }] : []),
    ...(hasDocumentsVisibility ? [{ key: "documents" as const, label: "Documents" }] : []),
    ...(job.can.reassign ? [{ key: "reassign" as const, label: "Reassign" }] : []),
    ...(hasCommentsVisibility ? [{ key: "comments" as const, label: "Comments" }] : []),
    { key: "history", label: "History" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={job.title} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="space-y-2">
            <button type="button" onClick={() => router.visit("/marketing/jobs")} className="text-sm text-muted-foreground underline underline-offset-4">
              Back to marketing jobs
            </button>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-xl font-semibold">{job.title}</h1>
              <span className={`rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${statusBadgeClass(job.status)}`}>
                {job.status.replaceAll("_", " ")}
              </span>
              <span className="rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 text-xs font-medium capitalize text-orange-700">
                Transaction {job.transaction_state}
              </span>
            </div>
            <p className="text-sm text-muted-foreground">
              This page tracks the full marketing approval transaction: concept delivery, supporting documents, review notes, approvals, amendments, and final closure.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        {flash.success ? <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">{String(flash.success)}</div> : null}
        {flash.error ? <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">{String(flash.error)}</div> : null}

        <section className="grid gap-4 xl:grid-cols-[2fr_1fr]">
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Marketing Brief</h2>
            {job.brief ? <p className="mt-2 text-sm text-muted-foreground">{job.brief}</p> : null}
            <div className="mt-4 grid gap-3 md:grid-cols-2">
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Work Type</div>
                <div className="mt-1 text-sm font-medium">{job.job_type.replaceAll("_", " ")}</div>
                <div className="mt-1 text-xs text-muted-foreground">Priority {job.priority.toUpperCase()}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Routing</div>
                <div className="mt-1 text-sm font-medium">{job.assignee_name ?? "Marketing queue"}</div>
                <div className="mt-1 text-xs text-muted-foreground">{job.assigned_department_name ?? "No department queue"}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Opened By</div>
                <div className="mt-1 text-sm font-medium">{job.creator_name ?? "-"}</div>
                <div className="mt-1 text-xs text-muted-foreground">{job.creator_department_name ?? "No department recorded"}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Context</div>
                <div className="mt-1 text-sm font-medium">{job.event_name ?? "Standalone marketing work"}</div>
                <div className="mt-1 text-xs text-muted-foreground">{job.due_date ? `Due ${job.due_date}` : "No due date set"}</div>
              </div>
            </div>
          </div>

          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Approval State</h2>
            <div className="mt-3 space-y-3 text-sm">
              <div className="rounded-lg border p-3">
                <div className="font-medium">Current state</div>
                <div className="mt-1 text-muted-foreground">
                  {job.status === "pending_approval"
                    ? `Delivered by ${job.submitted_by_name ?? "assignee"} and waiting for manager approval.`
                    : job.status === "approved"
                      ? `Approved by ${job.reviewed_by_name ?? job.closed_by_name ?? "manager"} and closed.`
                      : job.status === "changes_requested"
                        ? `Returned for amendments by ${job.reviewed_by_name ?? "manager"}.`
                        : `Still in active production with ${job.assignee_name ?? job.assigned_department_name ?? "the current queue"}.`}
                </div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="font-medium">Transaction dates</div>
                <div className="mt-1 text-muted-foreground">
                  Opened {job.transaction_opened_at ?? "-"}
                  {job.transaction_closed_at ? ` | Closed ${job.transaction_closed_at}` : " | Awaiting approval closure"}
                </div>
              </div>
              {job.approval_notes ? (
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900">
                  <div className="font-medium">Approval note</div>
                  <div className="mt-1 text-sm">{job.approval_notes}</div>
                </div>
              ) : null}
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
              <button key={tab.key} type="button" onClick={() => setActiveTab(tab.key)} className={activeTab === tab.key ? "rounded-md border border-slate-900 bg-slate-900 px-3 py-2 text-sm text-white" : "rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"}>
                {tab.label}
              </button>
            ))}
          </div>

          <div className="mt-4">
            {activeTab === "overview" ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Progress Updates</h3>
                  {showStatusUpdate ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/marketing/jobs/${job.id}/status`, {
                          status: formData.get("status"),
                          delivery_notes: formData.get("delivery_notes"),
                        }, { preserveScroll: true });
                      }}
                    >
                      <select name="status" defaultValue={job.status} className="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                        {job.can.approve ? <option value="cancelled">Cancelled</option> : null}
                      </select>
                      <textarea name="delivery_notes" rows={4} defaultValue={job.delivery_notes ?? ""} placeholder="Capture progress, blockers, or design/content updates." className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">
                        Save Progress
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Progress updates are locked because this item is already in approval or fully closed.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Delivery Record</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Delivery notes</div>
                      <div className="mt-1 text-muted-foreground">{job.delivery_notes ?? "No delivery notes recorded yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Submitted for approval</div>
                      <div className="mt-1 text-muted-foreground">{job.submitted_for_approval_at ?? "Not submitted yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Reviewed</div>
                      <div className="mt-1 text-muted-foreground">{job.reviewed_at ? `${job.reviewed_at} by ${job.reviewed_by_name ?? "manager"}` : "No manager review recorded yet."}</div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "delivery" ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Delivery Pack</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Upload the actual concept, signature pack, content plan, letter, or communication output here before manager approval.
                  </p>
                  {showDeliverySubmission ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/marketing/jobs/${job.id}/submit-approval`, formData, {
                          preserveScroll: true,
                          forceFormData: true,
                        });
                      }}
                    >
                      <textarea name="delivery_notes" rows={4} defaultValue={job.delivery_notes ?? ""} placeholder="Summarise what has been delivered and what the pack contains." className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <input name="proof_url" type="url" defaultValue={job.proof_url ?? ""} placeholder="Proof link or mail trail URL" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <input name="proof_file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      {job.has_proof_file ? (
                        <label className="flex items-center gap-2 text-xs text-muted-foreground">
                          <input name="remove_proof_file" type="checkbox" value="1" />
                          Remove current uploaded proof file
                        </label>
                      ) : null}
                      <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Submit For Approval
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Delivery submission is not available in the current workflow state.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Current Delivery Evidence</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Submitted by</div>
                      <div className="mt-1 text-muted-foreground">{job.submitted_by_name ?? job.assignee_name ?? "Not submitted yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Proof link</div>
                      <div className="mt-1 text-muted-foreground">
                        {job.proof_url ? <a href={job.proof_url} className="text-blue-700 underline" target="_blank" rel="noreferrer">Open linked proof</a> : "No linked proof."}
                      </div>
                    </div>
                    <div>
                      <div className="font-medium">Proof file</div>
                      <div className="mt-1 text-muted-foreground">
                        {job.has_proof_file ? <a href={`/marketing/jobs/${job.id}/proof`} className="text-blue-700 underline">{job.proof_file_name ?? "Download proof"}</a> : "No uploaded proof file."}
                      </div>
                    </div>
                    <div>
                      <div className="font-medium">Delivery notes</div>
                      <div className="mt-1 text-muted-foreground">{job.delivery_notes ?? "No delivery notes captured yet."}</div>
                    </div>
                    {canManageVault && job.status === "approved" ? (
                      <form
                        className="grid gap-3 rounded-lg border border-dashed p-3"
                        onSubmit={(e) => {
                          e.preventDefault();
                          router.post(`/marketing/jobs/${job.id}/publish-to-vault`, new FormData(e.currentTarget), {
                            preserveScroll: true,
                          });
                        }}
                      >
                        <div className="font-medium">Publish Approved Output To Organization Vault</div>
                        <input name="title" defaultValue={job.title} className="rounded-md border bg-background px-3 py-2 text-sm" />
                        <select
                          name="document_type"
                          value={vaultDocumentType}
                          onChange={(event) => setVaultDocumentType(event.target.value)}
                          className="rounded-md border bg-background px-3 py-2 text-sm"
                        >
                          {documentTypes.map((documentType) => (
                            <option key={documentType.value} value={documentType.value}>{documentType.label}</option>
                          ))}
                        </select>
                        <select name="source_kind" defaultValue={job.has_proof_file ? "proof" : "document"} className="rounded-md border bg-background px-3 py-2 text-sm">
                          {job.has_proof_file ? <option value="proof">Approved proof file</option> : null}
                          {documents.length > 0 ? <option value="document">Uploaded marketing document</option> : null}
                        </select>
                        <select name="document_id" defaultValue={documents[0]?.id ? String(documents[0].id) : ""} className="rounded-md border bg-background px-3 py-2 text-sm">
                          <option value="">Choose uploaded document</option>
                          {documents.map((document) => (
                            <option key={document.id} value={document.id}>{document.title}</option>
                          ))}
                        </select>
                        <select name="audience_scope" defaultValue="all_staff" className="rounded-md border bg-background px-3 py-2 text-sm">
                          <option value="all_staff">All staff</option>
                          <option value="department">Department</option>
                          <option value="selected_users">Selected users</option>
                        </select>
                        <select name="department_id" defaultValue="" className="rounded-md border bg-background px-3 py-2 text-sm">
                          <option value="">No department target</option>
                          {departments.map((department) => (
                            <option key={department.id} value={department.id}>{department.name}</option>
                          ))}
                        </select>
                        <select name="slot_key" defaultValue="" className="rounded-md border bg-background px-3 py-2 text-sm">
                          <option value="">No replacement slot</option>
                          {filteredVaultSlots.map((slot) => (
                            <option key={slot.value} value={slot.value}>{slot.label}</option>
                          ))}
                        </select>
                        <label className="flex items-center gap-2 text-xs text-muted-foreground">
                          <input type="checkbox" name="replace_existing" value="1" />
                          Replace current organization file in this slot
                        </label>
                        <label className="flex items-center gap-2 text-xs text-muted-foreground">
                          <input type="checkbox" name="is_active" value="1" defaultChecked />
                          Active immediately
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                          Available from
                          <input name="effective_from" type="date" className="rounded-md border bg-background px-3 py-2 text-sm font-normal text-slate-900" />
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                          Retire after
                          <input name="effective_until" type="date" className="rounded-md border bg-background px-3 py-2 text-sm font-normal text-slate-900" />
                        </label>
                        <textarea name="description" rows={3} placeholder="What users should use this for." className="rounded-md border bg-background px-3 py-2 text-sm" />
                        <div className="grid gap-2 md:grid-cols-2">
                          {users.map((user) => (
                            <label key={user.id} className="flex items-center gap-2 text-xs text-muted-foreground">
                              <input type="checkbox" name="selected_user_ids[]" value={user.id} />
                              <span>{user.name}</span>
                            </label>
                          ))}
                        </div>
                        <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                          Publish To Vault
                        </button>
                      </form>
                    ) : null}
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "approval" && canManageApproval ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Manager Approval</h3>
                  {job.status === "pending_approval" ? (
                    <div className="mt-3 space-y-4">
                      {job.can.approve ? (
                        <form
                          className="grid gap-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.currentTarget);
                            router.post(`/marketing/jobs/${job.id}/approve`, {
                              approval_notes: formData.get("approval_notes"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <textarea name="approval_notes" rows={4} defaultValue={job.approval_notes ?? ""} placeholder="Manager notes confirming that the marketing output is approved." className="rounded-md border bg-background px-3 py-2 text-sm" />
                          <button type="submit" className="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                            Approve And Close
                          </button>
                        </form>
                      ) : null}

                      {job.can.request_amendments ? (
                        <form
                          className="grid gap-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.currentTarget);
                            router.post(`/marketing/jobs/${job.id}/request-amendments`, {
                              approval_notes: formData.get("approval_notes"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <textarea name="approval_notes" rows={4} defaultValue={job.approval_notes ?? ""} placeholder="List the amendments required before this can be approved." className="rounded-md border bg-background px-3 py-2 text-sm" />
                          <button type="submit" className="rounded-md bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                            Return For Amendments
                          </button>
                        </form>
                      ) : null}
                    </div>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      {job.status === "approved"
                        ? `This marketing transaction is already closed by ${job.closed_by_name ?? "manager"}.`
                        : "The assigned marketing staff member must first submit the work for approval."}
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Approval Record</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">State</div>
                      <div className="mt-1 text-muted-foreground">{job.transaction_state === "closed" ? "Closed" : "Open and awaiting final marketing approval."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Approved at</div>
                      <div className="mt-1 text-muted-foreground">{job.approved_at ?? "Not approved yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Closed by</div>
                      <div className="mt-1 text-muted-foreground">{job.closed_by_name ?? "Not yet closed."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Approval notes</div>
                      <div className="mt-1 text-muted-foreground">{job.approval_notes ?? "No approval notes recorded yet."}</div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "documents" && hasDocumentsVisibility ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Supporting Documents</h3>
                  {job.can.upload_document ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/marketing/jobs/${job.id}/documents`, formData, {
                          preserveScroll: true,
                          forceFormData: true,
                        });
                      }}
                    >
                      <input name="title" placeholder="Document title" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <select name="document_kind" defaultValue="supporting" className="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="supporting">Supporting document</option>
                        <option value="concept">Concept draft</option>
                        <option value="delivery">Delivery pack</option>
                        <option value="review_feedback">Review feedback</option>
                        <option value="revised_submission">Revised submission</option>
                        <option value="approval_reference">Approval reference</option>
                      </select>
                      <textarea name="notes" rows={3} placeholder="What is this file for?" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <input name="file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">
                        Upload Document
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      You do not currently have document upload rights on this item.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Document Register</h3>
                  <div className="mt-3 space-y-3">
                    {documents.length === 0 ? (
                      <p className="text-sm text-muted-foreground">No marketing documents uploaded yet.</p>
                    ) : documents.map((document) => (
                      <div key={document.id} className="rounded-md border p-3">
                        <div className="font-medium">{document.title}</div>
                        <div className="mt-1 text-xs text-muted-foreground">
                          {document.document_kind.replaceAll("_", " ")} | {document.uploaded_by_name ?? "-"} | {document.created_at ?? "-"}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">{document.file_name}</div>
                        {document.notes ? <div className="mt-2 text-sm text-muted-foreground">{document.notes}</div> : null}
                        <a href={`/marketing/jobs/${job.id}/documents/${document.id}`} className="mt-2 inline-block text-sm text-blue-700 underline">
                          Download
                        </a>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "reassign" && job.can.reassign ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Reassign Marketing Work</h3>
                  <form
                    className="mt-3 grid gap-3"
                    onSubmit={(e) => {
                      e.preventDefault();
                      const formData = new FormData(e.currentTarget);
                      router.post(`/marketing/jobs/${job.id}/reassign`, {
                        assigned_to_user_id: formData.get("assigned_to_user_id"),
                        assigned_department_id: formData.get("assigned_department_id"),
                        reason: formData.get("reason"),
                      }, { preserveScroll: true });
                    }}
                  >
                    <select name="assigned_to_user_id" defaultValue={String(job.assigned_to_user_id ?? "")} className="rounded-md border bg-background px-3 py-2 text-sm">
                      <option value="">Marketing queue / no direct assignee</option>
                      {assignees.map((assignee) => (
                        <option key={assignee.id} value={assignee.id}>
                          {assignee.name} | {assignee.email}
                        </option>
                      ))}
                    </select>
                    <select name="assigned_department_id" defaultValue={String(job.assigned_department_id ?? "")} className="rounded-md border bg-background px-3 py-2 text-sm">
                      <option value="">No department queue</option>
                      {departments.map((department) => (
                        <option key={department.id} value={department.id}>
                          {department.name}
                        </option>
                      ))}
                    </select>
                    <textarea name="reason" rows={4} placeholder="Explain what needs to change and why the item is being redirected." className="rounded-md border bg-background px-3 py-2 text-sm" />
                    <button type="submit" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                      Reassign Marketing Work
                    </button>
                  </form>
                </div>
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Current Routing</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Assigned user</div>
                      <div className="mt-1 text-muted-foreground">{job.assignee_name ?? "No direct assignee"}</div>
                    </div>
                    <div>
                      <div className="font-medium">Assigned department</div>
                      <div className="mt-1 text-muted-foreground">{job.assigned_department_name ?? "No department queue"}</div>
                    </div>
                    <div>
                      <div className="font-medium">Reassignment effect</div>
                      <div className="mt-1 text-muted-foreground">Reassignment returns the transaction to active production and keeps approval closure unavailable until a fresh submission is made.</div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "comments" && hasCommentsVisibility ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Post Comment</h3>
                  {job.can.comment ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/marketing/jobs/${job.id}/comment`, {
                          message: formData.get("message"),
                        }, { preserveScroll: true });
                        e.currentTarget.reset();
                      }}
                    >
                      <textarea name="message" rows={4} className="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Post a note, feedback clarification, or design/content blocker." />
                      <button type="submit" className="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                        Post Comment
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Commenting is not available here for your current role.
                    </div>
                  )}
                </div>
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Comment Thread</h3>
                  <div className="mt-3 space-y-3">
                    {comments.length === 0 ? <p className="text-sm text-muted-foreground">No comments yet.</p> : comments.map((comment) => (
                      <div key={comment.id} className="rounded-md border p-3">
                        <div className="text-xs text-muted-foreground">{comment.user_name ?? "-"} | {comment.created_at ?? "-"}</div>
                        <div className="mt-1 text-sm">{comment.message}</div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "history" ? (
              <div className="rounded-lg border p-4">
                <h3 className="text-sm font-semibold">Transaction History</h3>
                <div className="mt-3 space-y-3">
                  {history.length === 0 ? <p className="text-sm text-muted-foreground">No history recorded yet.</p> : history.map((item) => (
                    <div key={item.id} className="rounded-md border p-3">
                      <div className="text-xs text-muted-foreground">{item.actor_name ?? "System"} | {item.created_at ?? "-"}</div>
                      <div className="mt-1 text-sm">{item.summary}</div>
                    </div>
                  ))}
                </div>
              </div>
            ) : null}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
