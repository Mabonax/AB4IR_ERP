import { EventTaskFormPage } from "@/components/event-task-form-page";
import { type BreadcrumbItem } from "@/types";

export default function EventTaskCreate({
  event,
  defaults,
}: {
  event: any;
  defaults: {
    event_workstream_id: number | null;
    phase: string;
  };
}) {
  const selectedWorkstream =
    event.workstreams?.find((workstream: any) => workstream.id === defaults.event_workstream_id) ?? event.workstreams?.[0] ?? null;

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: "Add Task", href: `/events/${event.id}/tasks/create` },
  ];

  return (
    <EventTaskFormPage
      mode="create"
      pageTitle="Add Event Task"
      title={`Add Task to ${event.title}`}
      description="Create a planning task on its own page so evidence, links, and progress can be managed without modal constraints."
      breadcrumbs={breadcrumbs}
      event={event}
      submitRoute={{ url: `/events/${event.id}/tasks`, method: "post" }}
      initialData={{
        event_workstream_id: String(selectedWorkstream?.id ?? ""),
        phase: defaults.phase ?? "pre_event",
        task_group: selectedWorkstream?.task_group_options?.[0] ?? "",
        is_custom: true,
        duty: "",
        due_date: "",
        responsible_person: "",
        outcome: "",
        status: "pending",
        comment: "",
        evidence_url: "",
        evidence_file: null,
        evidence_attachments: [],
        attachments: [],
        remove_attachment_ids: [],
        evidence_file_name: null,
        has_evidence_file: false,
        remove_evidence_file: false,
        sort_order: String((event.planning_summary?.total_tasks ?? 0) + 1),
      }}
    />
  );
}
