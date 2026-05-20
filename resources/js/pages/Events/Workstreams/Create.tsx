import { EventWorkstreamFormPage } from "@/components/event-workstream-form-page";
import { type BreadcrumbItem } from "@/types";

export default function EventWorkstreamCreate({ event }: { event: any }) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: "Add Department", href: `/events/${event.id}/workstreams/create` },
  ];

  return (
    <EventWorkstreamFormPage
      mode="create"
      pageTitle="Add Event Department"
      title={`Add Department to ${event.title}`}
      description="Create a dedicated department lane for this event so its planning tasks can be managed on their own page flow."
      breadcrumbs={breadcrumbs}
      event={event}
      submitRoute={{ url: `/events/${event.id}/workstreams`, method: "post" }}
      initialData={{
        name: "",
        description: "",
        sort_order: String((event.workstreams?.length ?? 0) + 1),
      }}
    />
  );
}
