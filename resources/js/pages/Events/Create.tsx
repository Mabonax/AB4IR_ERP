import { EventFormPage } from "@/components/event-form-page";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Events", href: "/events" },
  { title: "Create", href: "/events/create" },
];

export default function EventsCreate({
  staffMembers,
  stakeholders,
  eventSeries,
}: {
  staffMembers: Array<{ id: number; name: string }>;
  stakeholders: Array<{ id: number; name: string }>;
  eventSeries: Array<{ id: number; name: string; series_key: string; slug: string }>;
}) {
  return (
    <EventFormPage
      mode="create"
      pageTitle="Create Event"
      title="Create Event"
      description="Set up a new institutional event with its venue, delivery structure, partner context, and operational links before planning and participant work begins."
      breadcrumbs={breadcrumbs}
      submitRoute={{ url: "/events", method: "post" }}
      staffMembers={staffMembers}
      stakeholders={stakeholders}
      eventSeries={eventSeries}
      initialData={{
        title: "",
        event_type: "",
        event_format: "",
        event_series_id: "",
        annual_series_key: "",
        event_year: "",
        is_annual: "1",
        theme: "",
        track_name: "",
        location: "",
        venue_name: "",
        venue_address: "",
        venue_contact_person: "",
        venue_contact_phone: "",
        venue_contact_email: "",
        start_date: "",
        end_date: "",
        status: "planned",
        description: "",
        objectives: "",
        technical_requirements: "",
        registration_link: "",
        zoom_join_url: "",
        zoom_host_url: "",
        zoom_meeting_id: "",
        zoom_passcode: "",
        expected_attendees: "",
        owner_staff_member_id: "",
        partner_stakeholder_ids: [],
      }}
    />
  );
}
