import { EventFormPage } from "@/components/event-form-page";
import { type BreadcrumbItem } from "@/types";

export default function EventsEdit({
  event,
  staffMembers,
  stakeholders,
}: {
  event: any;
  staffMembers: Array<{ id: number; name: string }>;
  stakeholders: Array<{ id: number; name: string }>;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: "Edit", href: `/events/${event.id}/edit` },
  ];

  return (
    <EventFormPage
      mode="edit"
      pageTitle="Edit Event"
      title={`Edit ${event.title}`}
      description="Update the event profile, venue, partner structure, and delivery links without leaving the event domain workflow."
      breadcrumbs={breadcrumbs}
      submitRoute={{ url: `/events/${event.id}`, method: "put" }}
      staffMembers={staffMembers}
      stakeholders={stakeholders}
      backHref={`/events/${event.id}`}
      initialData={{
        title: event.title ?? "",
        event_type: event.event_type ?? "",
        event_format: event.event_format ?? "",
        annual_series_key: event.annual_series_key ?? "",
        event_year: event.event_year ? String(event.event_year) : "",
        is_annual: event.is_annual ? "1" : "0",
        theme: event.theme ?? "",
        track_name: event.track_name ?? "",
        location: event.location ?? "",
        venue_name: event.venue_name ?? "",
        venue_address: event.venue_address ?? "",
        venue_contact_person: event.venue_contact_person ?? "",
        venue_contact_phone: event.venue_contact_phone ?? "",
        venue_contact_email: event.venue_contact_email ?? "",
        start_date: event.start_date ?? "",
        end_date: event.end_date ?? "",
        status: event.status ?? "planned",
        description: event.description ?? "",
        objectives: event.objectives ?? "",
        technical_requirements: event.technical_requirements ?? "",
        registration_link: event.registration_link ?? "",
        zoom_join_url: event.zoom_join_url ?? "",
        zoom_host_url: event.zoom_host_url ?? "",
        zoom_meeting_id: event.zoom_meeting_id ?? "",
        zoom_passcode: event.zoom_passcode ?? "",
        expected_attendees:
          event.expected_attendees !== null && event.expected_attendees !== undefined
            ? String(event.expected_attendees)
            : "",
        owner_staff_member_id:
          event.owner_staff_member_id !== null && event.owner_staff_member_id !== undefined
            ? String(event.owner_staff_member_id)
            : "",
        partner_stakeholder_ids: Array.isArray(event.partner_stakeholder_ids)
          ? event.partner_stakeholder_ids.map((id: number) => String(id))
          : [],
      }}
    />
  );
}
