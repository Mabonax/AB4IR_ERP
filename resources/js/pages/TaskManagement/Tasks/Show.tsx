import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { taskManagementNavItems } from "@/config/domain-nav/task-management";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type TaskStatus = "open" | "in_progress" | "blocked" | "pending_review" | "changes_requested" | "completed" | "cancelled";
type TaskTab = "overview" | "evidence" | "finalization" | "documents" | "reassign" | "comments" | "history";

type TaskDocument = {
  id: number;
  title: string;
  document_kind: string;
  notes: string | null;
  file_name: string;
  mime_type: string | null;
  file_size: number | null;
  uploaded_by_name: string | null;
  created_at: string | null;
};

type TaskComment = {
  id: number;
  user_name: string | null;
  message: string;
  created_at: string | null;
};

type TaskHistory = {
  id: number;
  actor_name: string | null;
  action: string;
  summary: string;
  meta?: Record<string, unknown> | null;
  created_at: string | null;
};

type TaskDetail = {
  id: number;
  title: string;
  description: string | null;
  status: TaskStatus;
  priority: "low" | "medium" | "high" | "urgent";
  due_date: string | null;
  context_type: string;
  project_name: string | null;
  program_title: string | null;
  creator_name: string | null;
  creator_department_name: string | null;
  assignee_name: string | null;
  assigned_to_user_id: number | null;
  assigned_department_id: number | null;
  assigned_department_name: string | null;
  completion_notes: string | null;
  proof_url: string | null;
  proof_file_name: string | null;
  has_proof_file: boolean;
  submitted_for_review_at: string | null;
  submitted_by_name: string | null;
  manager_review_notes: string | null;
  reviewed_at: string | null;
  reviewed_by_name: string | null;
  returned_for_amendments_at: string | null;
  completed_at: string | null;
  transaction_state: "open" | "closed";
  transaction_opened_at: string | null;
  transaction_closed_at: string | null;
  closed_by_name: string | null;
  documents: TaskDocument[] | { data?: TaskDocument[] };
  comments: TaskComment[] | { data?: TaskComment[] };
  history: TaskHistory[] | { data?: TaskHistory[] };
  can: {
    update_status: boolean;
    comment: boolean;
    reassign: boolean;
    submit_for_review: boolean;
    approve_completion: boolean;
    return_for_amendments: boolean;
    upload_document: boolean;
  };
};

const statusBadgeClass = (status: TaskStatus) => {
  switch (status) {
    case "completed":
      return "border-green-200 bg-green-50 text-green-700";
    case "pending_review":
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

const transactionBadgeClass = (state: "open" | "closed") =>
  state === "closed"
    ? "border-green-200 bg-green-50 text-green-700"
    : "border-red-200 bg-red-50 text-red-700";

const documentKindLabel = (kind: string) => kind.replaceAll("_", " ");

export default function TaskManagementTaskShow({
  task,
  assignees,
  departments,
}: {
  task: TaskDetail;
  assignees: Array<{ id: number; name: string; email: string }>;
  departments: Array<{ id: number; name: string }>;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [activeTab, setActiveTab] = useState<TaskTab>("overview");
  const documents = Array.isArray(task.documents) ? task.documents : (task.documents.data ?? []);
  const comments = Array.isArray(task.comments) ? task.comments : (task.comments.data ?? []);
  const history = Array.isArray(task.history) ? task.history : (task.history.data ?? []);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Task Management", href: "/task-management/tasks" },
    { title: "Tasks", href: "/task-management/tasks" },
    { title: task.title, href: `/task-management/tasks/${task.id}` },
  ];

  const reviewReady = task.status === "pending_review";
  const showEvidenceSubmission = task.can.submit_for_review && !["pending_review", "completed", "cancelled"].includes(task.status);
  const showStatusUpdate = task.can.update_status && !["pending_review", "completed", "cancelled"].includes(task.status);
  const canManageFinalization = task.can.approve_completion || task.can.return_for_amendments;
  const hasEvidenceVisibility = showEvidenceSubmission || task.has_proof_file || Boolean(task.proof_url) || Boolean(task.submitted_for_review_at) || Boolean(task.completion_notes);
  const hasDocumentsVisibility = task.can.upload_document || documents.length > 0;
  const hasCommentsVisibility = task.can.comment || comments.length > 0;
  const visibleTabs: Array<{ key: TaskTab; label: string }> = [
    { key: "overview", label: "Overview" },
    ...(hasEvidenceVisibility ? [{ key: "evidence" as const, label: showEvidenceSubmission ? "Upload Evidence" : "Evidence" }] : []),
    ...(canManageFinalization ? [{ key: "finalization" as const, label: "Finalization" }] : []),
    ...(hasDocumentsVisibility ? [{ key: "documents" as const, label: "Documents" }] : []),
    ...(task.can.reassign ? [{ key: "reassign" as const, label: "Reassign" }] : []),
    ...(hasCommentsVisibility ? [{ key: "comments" as const, label: "Comments" }] : []),
    { key: "history", label: "History" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={task.title} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="space-y-2">
            <button
              type="button"
              onClick={() => router.visit("/task-management/tasks")}
              className="text-sm text-muted-foreground underline underline-offset-4"
            >
              Back to tasks
            </button>
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-xl font-semibold">{task.title}</h1>
              <span className={`rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${statusBadgeClass(task.status)}`}>
                {task.status.replaceAll("_", " ")}
              </span>
              <span className={`rounded-full border px-2.5 py-1 text-xs font-medium capitalize ${transactionBadgeClass(task.transaction_state)}`}>
                Transaction {task.transaction_state}
              </span>
            </div>
            <p className="text-sm text-muted-foreground">
              This page holds the full task transaction, including evidence, documents, reassignment, comments, review history, and final manager closure.
            </p>
          </div>
          <DomainNav items={taskManagementNavItems} />
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {flash.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        <section className="grid gap-4 xl:grid-cols-[2fr_1fr]">
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Task Summary</h2>
            {task.description ? <p className="mt-2 text-sm text-muted-foreground">{task.description}</p> : null}
            <div className="mt-4 grid gap-3 md:grid-cols-2">
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Routing</div>
                <div className="mt-1 text-sm font-medium">{task.assignee_name ?? "Department queue"}</div>
                <div className="mt-1 text-xs text-muted-foreground">{task.assigned_department_name ?? "No department queue"}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Context</div>
                <div className="mt-1 text-sm font-medium">{task.project_name ?? task.program_title ?? "General operational task"}</div>
                <div className="mt-1 text-xs text-muted-foreground">{task.context_type.replaceAll("_", " ")}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Opened By</div>
                <div className="mt-1 text-sm font-medium">{task.creator_name ?? "-"}</div>
                <div className="mt-1 text-xs text-muted-foreground">{task.creator_department_name ?? "No department recorded"}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Dates</div>
                <div className="mt-1 text-sm font-medium">{task.due_date ? `Due ${task.due_date}` : "No due date"}</div>
                <div className="mt-1 text-xs text-muted-foreground">
                  Opened {task.transaction_opened_at ?? "-"}
                  {task.transaction_closed_at ? ` | Closed ${task.transaction_closed_at}` : " | Awaiting manager closure"}
                </div>
              </div>
            </div>
          </div>

          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Current Workflow State</h2>
            <div className="mt-3 space-y-3 text-sm">
              <div className="rounded-lg border p-3">
                <div className="font-medium">Delivery state</div>
                <div className="mt-1 text-muted-foreground">
                  {task.status === "pending_review"
                    ? `Delivered by ${task.submitted_by_name ?? "assignee"} and waiting for manager signoff.`
                    : task.status === "completed"
                      ? `Approved by ${task.reviewed_by_name ?? task.closed_by_name ?? "manager"} and closed.`
                      : task.status === "changes_requested"
                        ? `Returned for amendments by ${task.reviewed_by_name ?? "manager"}.`
                        : `Still in active delivery with ${task.assignee_name ?? task.assigned_department_name ?? "the current queue"}.`}
                </div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="font-medium">Proof and review</div>
                <div className="mt-1 text-muted-foreground">
                  {task.submitted_for_review_at ? `Submitted ${task.submitted_for_review_at}.` : "Evidence not yet submitted for review."}
                </div>
                {task.manager_review_notes ? (
                  <div className="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    <span className="font-medium">Manager note:</span> {task.manager_review_notes}
                  </div>
                ) : null}
              </div>
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="flex flex-wrap gap-2">
            {visibleTabs.map((tab) => (
              <button
                key={tab.key}
                type="button"
                onClick={() => setActiveTab(tab.key)}
                className={activeTab === tab.key ? "rounded-md border border-slate-900 bg-slate-900 px-3 py-2 text-sm text-white" : "rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"}
              >
                {tab.label}
              </button>
            ))}
          </div>

          <div className="mt-4">
            {activeTab === "overview" ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Progress Notes</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Assignees can keep the task moving here, but final completion still requires manager signoff.
                  </p>
                  {showStatusUpdate ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/task-management/tasks/${task.id}/status`, {
                          status: formData.get("status"),
                          completion_notes: formData.get("completion_notes"),
                        }, { preserveScroll: true });
                      }}
                    >
                      <select name="status" defaultValue={task.status} className="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="blocked">Blocked</option>
                        {task.can.approve_completion ? <option value="cancelled">Cancelled</option> : null}
                      </select>
                      <textarea
                        name="completion_notes"
                        rows={4}
                        defaultValue={task.completion_notes ?? ""}
                        placeholder="Record progress, blockers, or what is being changed."
                        className="rounded-md border bg-background px-3 py-2 text-sm"
                      />
                      <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">
                        Save Progress
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Progress updates are locked here because the task is already under managed review or closed.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Transaction Notes</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Completion notes</div>
                      <div className="mt-1 text-muted-foreground">{task.completion_notes ?? "No completion notes recorded yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Submitted for review</div>
                      <div className="mt-1 text-muted-foreground">{task.submitted_for_review_at ?? "Not submitted yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Reviewed</div>
                      <div className="mt-1 text-muted-foreground">
                        {task.reviewed_at ? `${task.reviewed_at} by ${task.reviewed_by_name ?? "manager"}` : "No manager review recorded yet."}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "evidence" ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Evidence Submission</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Use this section for the assignee’s actual completion pack, proof link, or email evidence before manager review.
                  </p>
                  {showEvidenceSubmission ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/task-management/tasks/${task.id}/submit-review`, formData, {
                          preserveScroll: true,
                          forceFormData: true,
                        });
                      }}
                    >
                      <textarea
                        name="completion_notes"
                        rows={4}
                        defaultValue={task.completion_notes ?? ""}
                        placeholder="Summarise what was completed and what the evidence proves."
                        className="rounded-md border bg-background px-3 py-2 text-sm"
                      />
                      <input
                        name="proof_url"
                        type="url"
                        defaultValue={task.proof_url ?? ""}
                        placeholder="Proof link or email reference URL"
                        className="rounded-md border bg-background px-3 py-2 text-sm"
                      />
                      <input name="proof_file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      {task.has_proof_file ? (
                        <label className="flex items-center gap-2 text-xs text-muted-foreground">
                          <input name="remove_proof_file" type="checkbox" value="1" />
                          Remove current uploaded proof file
                        </label>
                      ) : null}
                      <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                        Submit For Review
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Evidence submission is not available in the current task state.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Current Evidence Record</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Submitted by</div>
                      <div className="mt-1 text-muted-foreground">{task.submitted_by_name ?? task.assignee_name ?? "Not submitted yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Proof link</div>
                      <div className="mt-1 text-muted-foreground">
                        {task.proof_url ? <a href={task.proof_url} className="text-blue-700 underline" target="_blank" rel="noreferrer">Open linked proof</a> : "No linked proof."}
                      </div>
                    </div>
                    <div>
                      <div className="font-medium">Proof file</div>
                      <div className="mt-1 text-muted-foreground">
                        {task.has_proof_file ? (
                          <a href={`/task-management/tasks/${task.id}/proof`} className="text-blue-700 underline">
                            {task.proof_file_name ?? "Download proof"}
                          </a>
                        ) : "No uploaded proof file."}
                      </div>
                    </div>
                    <div>
                      <div className="font-medium">Completion notes</div>
                      <div className="mt-1 text-muted-foreground">{task.completion_notes ?? "No notes captured yet."}</div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "finalization" && canManageFinalization ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Manager Finalization</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Only the manager closes the transaction. Approval marks the task complete. Return sends it back for amendments.
                  </p>
                  {reviewReady ? (
                    <div className="mt-3 space-y-4">
                      <div className="rounded-md border bg-slate-50 p-3 text-sm">
                        <div className="font-medium">Review packet</div>
                        <div className="mt-2 space-y-1 text-muted-foreground">
                          <div>Submitted by: {task.submitted_by_name ?? task.assignee_name ?? "-"}</div>
                          <div>Submitted at: {task.submitted_for_review_at ?? "-"}</div>
                          <div>Proof file: {task.has_proof_file ? task.proof_file_name ?? "Uploaded proof" : "No proof file"}</div>
                          <div>Proof link: {task.proof_url ?? "No proof link"}</div>
                        </div>
                      </div>

                      {task.can.approve_completion ? (
                        <form
                          className="grid gap-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.currentTarget);
                            router.post(`/task-management/tasks/${task.id}/approve`, {
                              manager_review_notes: formData.get("manager_review_notes"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <textarea
                            name="manager_review_notes"
                            rows={4}
                            defaultValue={task.manager_review_notes ?? ""}
                            placeholder="Manager notes confirming the work is accepted and can be closed."
                            className="rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          <button type="submit" className="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                            Mark Task As Complete
                          </button>
                        </form>
                      ) : null}

                      {task.can.return_for_amendments ? (
                        <form
                          className="grid gap-3"
                          onSubmit={(e) => {
                            e.preventDefault();
                            const formData = new FormData(e.currentTarget);
                            router.post(`/task-management/tasks/${task.id}/return`, {
                              manager_review_notes: formData.get("manager_review_notes"),
                            }, { preserveScroll: true });
                          }}
                        >
                          <textarea
                            name="manager_review_notes"
                            rows={4}
                            defaultValue={task.manager_review_notes ?? ""}
                            placeholder="List the amendments required before the task can be completed."
                            className="rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          <button type="submit" className="rounded-md bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                            Return For Amendments
                          </button>
                        </form>
                      ) : null}
                    </div>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      {task.status === "completed"
                        ? `This transaction is already closed by ${task.closed_by_name ?? "manager"}.`
                        : "The assignee must first submit the work for review before manager completion becomes available here."}
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Finalization Record</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Transaction state</div>
                      <div className="mt-1 text-muted-foreground">{task.transaction_state === "closed" ? "Closed" : "Open and awaiting final manager closure."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Completed at</div>
                      <div className="mt-1 text-muted-foreground">{task.completed_at ?? "Not completed yet."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Closed by</div>
                      <div className="mt-1 text-muted-foreground">{task.closed_by_name ?? "Not yet closed."}</div>
                    </div>
                    <div>
                      <div className="font-medium">Manager review notes</div>
                      <div className="mt-1 text-muted-foreground">{task.manager_review_notes ?? "No review notes recorded yet."}</div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "documents" && hasDocumentsVisibility ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Task Documents</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Use this area for supporting files, feedback packs, editable versions, revised drafts, and approval references.
                  </p>
                  {task.can.upload_document ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/task-management/tasks/${task.id}/documents`, formData, {
                          preserveScroll: true,
                          forceFormData: true,
                        });
                      }}
                    >
                      <input name="title" placeholder="Document title" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <select name="document_kind" defaultValue="supporting" className="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="supporting">Supporting document</option>
                        <option value="delivery">Delivery evidence</option>
                        <option value="review_feedback">Review feedback</option>
                        <option value="revised_submission">Revised submission</option>
                        <option value="approval_reference">Approval reference</option>
                      </select>
                      <textarea name="notes" rows={3} placeholder="What is this document for?" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <input name="file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">
                        Upload Document
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      You do not currently have document upload rights on this task.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Uploaded Documents</h3>
                  <div className="mt-3 space-y-3">
                    {documents.length === 0 ? (
                      <p className="text-sm text-muted-foreground">No task documents uploaded yet.</p>
                    ) : documents.map((document) => (
                      <div key={document.id} className="rounded-md border p-3">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                          <div>
                            <div className="font-medium">{document.title}</div>
                            <div className="mt-1 text-xs text-muted-foreground">
                              {documentKindLabel(document.document_kind)} | {document.uploaded_by_name ?? "-"} | {document.created_at ?? "-"}
                            </div>
                            <div className="mt-1 text-xs text-muted-foreground">{document.file_name}</div>
                            {document.notes ? <div className="mt-2 text-sm text-muted-foreground">{document.notes}</div> : null}
                          </div>
                          <a href={`/task-management/tasks/${task.id}/documents/${document.id}`} className="rounded-md border px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                            Download
                          </a>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "reassign" && task.can.reassign ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Reassign Task</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Reassignment resets the workflow and keeps the transaction open. Use it when the work must move to another responsible person or queue.
                  </p>
                  {task.can.reassign ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/task-management/tasks/${task.id}/reassign`, {
                          assigned_to_user_id: formData.get("assigned_to_user_id"),
                          assigned_department_id: formData.get("assigned_department_id"),
                          reason: formData.get("reason"),
                          status: "changes_requested",
                        }, { preserveScroll: true });
                      }}
                    >
                      <select name="assigned_to_user_id" defaultValue={String(task.assigned_to_user_id ?? "")} className="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">Department queue / no direct assignee</option>
                        {assignees.map((assignee) => (
                          <option key={assignee.id} value={assignee.id}>
                            {assignee.name} | {assignee.email}
                          </option>
                        ))}
                      </select>
                      <select name="assigned_department_id" defaultValue={String(task.assigned_department_id ?? "")} className="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">No department queue</option>
                        {departments.map((department) => (
                          <option key={department.id} value={department.id}>
                            {department.name}
                          </option>
                        ))}
                      </select>
                      <textarea name="reason" rows={4} placeholder="Explain what must change and why this task is being redirected." className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <button type="submit" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                        Reassign Task
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Reassignment is restricted to the current task manager workflow.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Current Routing</h3>
                  <div className="mt-3 space-y-3 text-sm">
                    <div>
                      <div className="font-medium">Assigned user</div>
                      <div className="mt-1 text-muted-foreground">{task.assignee_name ?? "No direct assignee"}</div>
                    </div>
                    <div>
                      <div className="font-medium">Assigned department</div>
                      <div className="mt-1 text-muted-foreground">{task.assigned_department_name ?? "No department queue"}</div>
                    </div>
                    <div>
                      <div className="font-medium">Current state after reassignment</div>
                      <div className="mt-1 text-muted-foreground">
                        Reassignments return the task to active delivery and keep manager closure unavailable until new evidence is submitted again.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}

            {activeTab === "comments" && hasCommentsVisibility ? (
              <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Post Comment</h3>
                  {task.can.comment ? (
                    <form
                      className="mt-3 grid gap-3"
                      onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        router.post(`/task-management/tasks/${task.id}/comment`, {
                          message: formData.get("message"),
                        }, { preserveScroll: true });
                        e.currentTarget.reset();
                      }}
                    >
                      <textarea name="message" rows={4} className="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Post a workflow note, blocker, or review clarification." />
                      <button type="submit" className="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                        Post Comment
                      </button>
                    </form>
                  ) : (
                    <div className="mt-3 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                      Commenting is not available on this task for your current role.
                    </div>
                  )}
                </div>

                <div className="rounded-lg border p-4">
                  <h3 className="text-sm font-semibold">Comment Thread</h3>
                  <div className="mt-3 space-y-3">
                    {comments.length === 0 ? (
                      <p className="text-sm text-muted-foreground">No comments yet.</p>
                    ) : comments.map((comment) => (
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
                <h3 className="text-sm font-semibold">Task History</h3>
                <div className="mt-3 space-y-3">
                  {history.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No history recorded yet.</p>
                  ) : history.map((item) => (
                    <div key={item.id} className="rounded-md border p-3">
                      <div className="text-xs text-muted-foreground">{item.actor_name ?? "System"} | {item.created_at ?? "-"}</div>
                      <div className="mt-1 text-sm">{item.summary}</div>
                      {item.meta?.proof_file_name ? (
                        <div className="mt-1 text-xs text-muted-foreground">Proof file: {String(item.meta.proof_file_name)}</div>
                      ) : null}
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
