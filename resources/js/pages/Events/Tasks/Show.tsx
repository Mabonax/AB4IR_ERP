import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { CheckCircle2, FileText, Pencil, RotateCcw } from "lucide-react";

import { DomainNav } from "@/components/domain-nav";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem, type SharedData } from "@/types";

const phaseLabels: Record<string, string> = {
  pre_event: "Pre-Event",
  preparations: "Preparations",
  event_day: "Event Day",
  post_event: "Post-Event",
};

function completionLabel(status?: string | null): string {
  switch (status) {
    case "approved":
      return "Verified";
    case "submitted":
      return "Pending verification";
    case "changes_requested":
      return "Changes requested";
    default:
      return "Not submitted";
  }
}

function canManageEvents(auth: SharedData["auth"]): boolean {
  const permissions = auth?.user?.permissions ?? [];
  const roles = (auth?.user?.roles ?? []).map((role) => role.toLowerCase());

  return permissions.includes("domain.events.manage") || roles.includes("super-admin") || roles.includes("super admin");
}

function Detail({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</div>
      <div className="mt-1 text-sm text-slate-900">{value}</div>
    </div>
  );
}

export default function EventTaskShow({ event, task }: { event: any; task: any }) {
  const { auth } = usePage<SharedData>().props;
  const canManage = canManageEvents(auth);
  const reviewForm = useForm({
    manager_review_notes: task.manager_review_notes ?? "",
  });

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: task.duty, href: `/events/${event.id}/tasks/${task.id}` },
  ];

  const submitReview = (action: "approve" | "return") => {
    reviewForm.post(`/events/${event.id}/tasks/${task.id}/${action}`, {
      preserveScroll: true,
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={task.duty} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Event Task Review</div>
            <h1 className="text-2xl font-semibold tracking-tight">{task.duty}</h1>
            <p className="max-w-3xl text-sm text-muted-foreground">
              Review the task evidence, completion submission, manager feedback, and final verification decision.
            </p>
            <div className="flex flex-wrap gap-2">
              <Badge variant="outline">{phaseLabels[task.phase] ?? task.phase}</Badge>
              <Badge variant="outline">{task.task_group ?? "General"}</Badge>
              <Badge variant="outline">{String(task.status).replaceAll("_", " ")}</Badge>
              <Badge variant={task.completion_status === "approved" ? "default" : "outline"}>{completionLabel(task.completion_status)}</Badge>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <DomainNav items={eventWorkflowNav(event.id)} />
            {canManage ? (
              <Link href={`/events/${event.id}/tasks/${task.id}/edit`}>
                <Button variant="outline">
                  <Pencil className="mr-2 h-4 w-4" />
                  Update Task
                </Button>
              </Link>
            ) : null}
          </div>
        </div>

        <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
          <div className="space-y-6">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Task Details</CardTitle>
                <CardDescription>The delivery scope and ownership context for this task.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                <Detail label="Event" value={event.title} />
                <Detail label="Department" value={task.workstream_name ?? "Not assigned"} />
                <Detail label="Responsible person" value={task.responsible_person ?? "Not assigned"} />
                <Detail label="Due date" value={task.due_date ?? "Not set"} />
                <div className="md:col-span-2">
                  <Detail label="Expected deliverable" value={task.outcome ?? "No deliverable recorded."} />
                </div>
                <div className="md:col-span-2">
                  <Detail label="Latest update" value={task.comment ?? "No update recorded."} />
                </div>
              </CardContent>
            </Card>

            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Evidence</CardTitle>
                <CardDescription>Files and links submitted to support completion or procurement decisions.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {task.has_evidence_file ? (
                  <a
                    href={`/events/${event.id}/tasks/${task.id}/evidence`}
                    className="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                  >
                    <FileText className="mr-2 h-4 w-4" />
                    {task.evidence_file_name ?? "Download evidence"}
                  </a>
                ) : (
                  <div className="rounded-md border border-dashed border-slate-200 px-3 py-4 text-sm text-slate-500">No main evidence file uploaded.</div>
                )}

                {task.evidence_url ? (
                  <a href={task.evidence_url} target="_blank" rel="noreferrer" className="block text-sm font-medium text-blue-700 underline-offset-4 hover:underline">
                    Open linked evidence
                  </a>
                ) : null}

                {task.attachments?.length ? (
                  <div className="space-y-2">
                    <div className="text-sm font-medium text-slate-900">Supporting attachments</div>
                    {task.attachments.map((attachment: any) => (
                      <a
                        key={attachment.id}
                        href={`/events/${event.id}/tasks/${task.id}/attachments/${attachment.id}`}
                        className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                      >
                        <span className="truncate">{attachment.file_name}</span>
                        <span className="ml-3 text-xs text-slate-500">Download</span>
                      </a>
                    ))}
                  </div>
                ) : (
                  <div className="rounded-md border border-dashed border-slate-200 px-3 py-4 text-sm text-slate-500">No supporting attachments uploaded.</div>
                )}
              </CardContent>
            </Card>
          </div>

          <div className="space-y-6">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Verification</CardTitle>
                <CardDescription>Manager review status and decision trail.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm">
                <Detail label="Verification status" value={completionLabel(task.completion_status)} />
                <Detail label="Submitted" value={task.submitted_for_verification_at ? `${task.submitted_for_verification_at}${task.submitted_by_name ? ` by ${task.submitted_by_name}` : ""}` : "Not submitted"} />
                <Detail label="Reviewed" value={task.reviewed_at ? `${task.reviewed_at}${task.reviewed_by_name ? ` by ${task.reviewed_by_name}` : ""}` : "Not reviewed"} />
                <Detail label="Review notes" value={task.manager_review_notes ?? "No manager feedback recorded."} />
              </CardContent>
            </Card>

            {canManage && task.completion_status === "submitted" ? (
              <Card className="border-slate-200 shadow-sm">
                <CardHeader>
                  <CardTitle>Manager Decision</CardTitle>
                  <CardDescription>Approve the submitted work or return it with feedback.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <textarea
                    value={reviewForm.data.manager_review_notes}
                    onChange={(event) => reviewForm.setData("manager_review_notes", event.target.value)}
                    className="min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none"
                    placeholder="Review notes"
                  />
                  {reviewForm.errors.manager_review_notes ? <p className="text-xs text-red-600">{reviewForm.errors.manager_review_notes}</p> : null}
                  <div className="flex flex-wrap gap-3">
                    <Button type="button" disabled={reviewForm.processing} onClick={() => submitReview("approve")}>
                      <CheckCircle2 className="mr-2 h-4 w-4" />
                      Approve Task
                    </Button>
                    <Button type="button" variant="outline" disabled={reviewForm.processing} onClick={() => submitReview("return")}>
                      <RotateCcw className="mr-2 h-4 w-4" />
                      Return for Amendments
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ) : null}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
