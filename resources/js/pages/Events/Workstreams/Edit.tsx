import { EventWorkstreamFormPage } from "@/components/event-workstream-form-page";
import { type BreadcrumbItem } from "@/types";

export default function EventWorkstreamEdit({
  event,
  workstream,
}: {
  event: any;
  workstream: any;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: workstream.name, href: `/events/${event.id}/workstreams/${workstream.id}/edit` },
  ];

  return (
    <EventWorkstreamFormPage
      mode="edit"
      pageTitle="Edit Event Department"
      title={`Edit ${workstream.name}`}
      description="Update the department lane details without working inside a compressed modal."
      breadcrumbs={breadcrumbs}
      event={event}
      submitRoute={{ url: `/events/${event.id}/workstreams/${workstream.id}`, method: "put" }}
      initialData={{
        name: workstream.name ?? "",
        description: workstream.description ?? "",
        sort_order: String(workstream.sort_order ?? 1),
      }}
    />
  );
}
