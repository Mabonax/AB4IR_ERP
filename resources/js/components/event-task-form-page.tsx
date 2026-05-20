import { Head, Link, useForm } from "@inertiajs/react";
import { ClipboardList, FileUp, Link2 } from "lucide-react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem } from "@/types";

const phaseLabels: Record<string, string> = {
  pre_event: "Pre-Event",
  preparations: "Preparations",
  event_day: "Event Day",
  post_event: "Post-Event",
};

const statusOptions = [
  { value: "pending", label: "Pending" },
  { value: "in_progress", label: "In Progress" },
  { value: "completed", label: "Completed" },
  { value: "on_going", label: "On Going" },
  { value: "blocked", label: "Blocked" },
  { value: "cancelled", label: "Cancelled" },
];

type TaskFormData = {
  event_workstream_id: string;
  phase: string;
  task_group: string;
  is_custom: boolean;
  duty: string;
  due_date: string;
  responsible_person: string;
  outcome: string;
  status: string;
  comment: string;
  evidence_url: string;
  evidence_file: File | null;
  evidence_file_name: string | null;
  has_evidence_file: boolean;
  remove_evidence_file: boolean;
  sort_order: string;
};

type Props = {
  mode: "create" | "edit";
  pageTitle: string;
  title: string;
  description: string;
  breadcrumbs: BreadcrumbItem[];
  event: any;
  submitRoute: {
    url: string;
    method: "post" | "put";
  };
  initialData: TaskFormData;
};

export function EventTaskFormPage({
  mode,
  pageTitle,
  title,
  description,
  breadcrumbs,
  event,
  submitRoute,
  initialData,
}: Props) {
  const form = useForm<TaskFormData>(initialData);
  const workstreamOptions = event.workstreams ?? [];
  const selectedWorkstream =
    workstreamOptions.find((workstream: any) => String(workstream.id) === form.data.event_workstream_id) ?? workstreamOptions[0] ?? null;
  const taskGroupOptions = selectedWorkstream?.task_group_options ?? [];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {mode === "create" ? "Add Planning Task" : "Update Planning Task"}
            </div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
              <p className="max-w-3xl text-sm text-muted-foreground">{description}</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <DomainNav items={eventWorkflowNav(event.id)} />
            <Link href={`/events/${event.id}`}>
              <Button variant="outline">Back to Event</Button>
            </Link>
          </div>
        </div>

        <form
          onSubmit={(e) => {
            e.preventDefault();
            form.submit(submitRoute.method, submitRoute.url, {
              preserveScroll: true,
              forceFormData: true,
            });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <div className="flex items-start gap-3">
                <div className="rounded-xl bg-red-50 p-2 text-red-600">
                  <ClipboardList className="h-4 w-4" />
                </div>
                <div>
                  <CardTitle className="text-base">Task Setup</CardTitle>
                  <CardDescription>Assign the task to a department, phase, and task group, then record its evidence and status.</CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="duty">Task</Label>
                <Input id="duty" value={form.data.duty} onChange={(e) => form.setData("duty", e.target.value)} />
                {form.errors.duty ? <p className="text-xs text-red-600">{form.errors.duty}</p> : null}
              </div>

              <div className="space-y-2">
                <Label htmlFor="event_workstream_id">Department</Label>
                <select
                  id="event_workstream_id"
                  value={form.data.event_workstream_id}
                  onChange={(e) => {
                    const nextWorkstream = workstreamOptions.find((workstream: any) => String(workstream.id) === e.target.value);
                    form.setData({
                      ...form.data,
                      event_workstream_id: e.target.value,
                      task_group: nextWorkstream?.task_group_options?.includes(form.data.task_group)
                        ? form.data.task_group
                        : (nextWorkstream?.task_group_options?.[0] ?? ""),
                    });
                  }}
                  className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none"
                >
                  {workstreamOptions.map((workstream: any) => (
                    <option key={workstream.id} value={String(workstream.id)}>
                      {workstream.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="task_group">Task group</Label>
                <select
                  id="task_group"
                  value={form.data.task_group}
                  onChange={(e) => form.setData("task_group", e.target.value)}
                  className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none"
                >
                  {taskGroupOptions.map((groupName: string) => (
                    <option key={groupName} value={groupName}>
                      {groupName}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="phase">Phase</Label>
                <select
                  id="phase"
                  value={form.data.phase}
                  onChange={(e) => form.setData("phase", e.target.value)}
                  className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none"
                >
                  {Object.entries(phaseLabels).map(([value, label]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <select
                  id="status"
                  value={form.data.status}
                  onChange={(e) => form.setData("status", e.target.value)}
                  className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none"
                >
                  {statusOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="due_date">Due date</Label>
                <Input id="due_date" type="date" value={form.data.due_date} onChange={(e) => form.setData("due_date", e.target.value)} />
              </div>

              <div className="space-y-2">
                <Label htmlFor="responsible_person">Responsible person</Label>
                <Input
                  id="responsible_person"
                  value={form.data.responsible_person}
                  onChange={(e) => form.setData("responsible_person", e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="sort_order">Sort order</Label>
                <Input
                  id="sort_order"
                  type="number"
                  min={1}
                  value={form.data.sort_order}
                  onChange={(e) => form.setData("sort_order", e.target.value)}
                />
              </div>

              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="outcome">Outcome / deliverable</Label>
                <textarea
                  id="outcome"
                  value={form.data.outcome}
                  onChange={(e) => form.setData("outcome", e.target.value)}
                  className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none"
                />
              </div>

              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="comment">Progress update</Label>
                <textarea
                  id="comment"
                  value={form.data.comment}
                  onChange={(e) => form.setData("comment", e.target.value)}
                  className="min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none"
                />
              </div>

              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="evidence_url">Evidence link / participation link</Label>
                <div className="relative">
                  <Link2 className="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" />
                  <Input
                    id="evidence_url"
                    type="url"
                    value={form.data.evidence_url}
                    onChange={(e) => form.setData("evidence_url", e.target.value)}
                    className="pl-9"
                  />
                </div>
              </div>

              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="evidence_file">Evidence file</Label>
                <div className="relative">
                  <FileUp className="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" />
                  <Input
                    id="evidence_file"
                    type="file"
                    className="pl-9"
                    onChange={(e) => form.setData("evidence_file", e.target.files?.[0] ?? null)}
                  />
                </div>
                {form.data.has_evidence_file ? (
                  <div className="flex flex-wrap items-center gap-3 text-xs text-slate-600">
                    <span>Current file: {form.data.evidence_file_name ?? "Evidence uploaded"}</span>
                    <label className="inline-flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={form.data.remove_evidence_file}
                        onChange={(e) => form.setData("remove_evidence_file", e.target.checked)}
                      />
                      Remove existing file
                    </label>
                  </div>
                ) : null}
              </div>
            </CardContent>
          </Card>

          <div className="space-y-5">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Current Context</CardTitle>
                <CardDescription>This task will sit under the active event planning workflow.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-sm text-slate-700">
                <div className="font-medium text-slate-950">{event.title}</div>
                <div>{selectedWorkstream?.name ?? "No department selected"} | {phaseLabels[form.data.phase] ?? form.data.phase}</div>
                <div>{form.data.task_group || "No task group selected yet"}</div>
              </CardContent>
            </Card>

            <div className="flex flex-wrap items-center justify-end gap-3">
              <Link href={`/events/${event.id}`}>
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" disabled={form.processing || !form.data.duty.trim()}>
                {form.processing ? "Saving..." : mode === "create" ? "Create Task" : "Save Task"}
              </Button>
            </div>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
