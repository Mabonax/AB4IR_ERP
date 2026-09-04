import { EventTaskFormPage } from "@/components/event-task-form-page";
import { type BreadcrumbItem } from "@/types";

export default function EventTaskEdit({
  event,
  task,
}: {
  event: any;
  task: any;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: task.duty, href: `/events/${event.id}/tasks/${task.id}/edit` },
  ];

  return (
    <EventTaskFormPage
      mode="edit"
      pageTitle="Edit Event Task"
      title={`Update ${task.duty}`}
      description="Update progress, evidence, links, and completion state on a full page instead of an in-place modal."
      breadcrumbs={breadcrumbs}
      event={event}
      submitRoute={{ url: `/events/${event.id}/tasks/${task.id}`, method: "put" }}
      reviewUrl={`/events/${event.id}/tasks/${task.id}`}
      initialData={{
        event_workstream_id: String(task.event_workstream_id ?? ""),
        phase: task.phase ?? "pre_event",
        task_group: task.task_group ?? "",
        is_custom: Boolean(task.is_custom),
        duty: task.duty ?? "",
        due_date: task.due_date ?? "",
        responsible_person: task.responsible_person ?? "",
        outcome: task.outcome ?? "",
        status: task.status ?? "pending",
        comment: task.comment ?? "",
        evidence_url: task.evidence_url ?? "",
        evidence_file: null,
        evidence_attachments: [],
        attachments: task.attachments ?? [],
        remove_attachment_ids: [],
        evidence_file_name: task.evidence_file_name ?? null,
        has_evidence_file: Boolean(task.has_evidence_file),
        remove_evidence_file: false,
        sort_order: String(task.sort_order ?? 1),
      }}
    />
  );
}
