import { Head, Link, useForm } from "@inertiajs/react";
import { CalendarRange, Link2, MapPinned, Presentation, Users } from "lucide-react";

import { DomainNav, type DomainNavItem } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type EventFormData = {
  title: string;
  event_type: string;
  event_format: string;
  event_series_id: string;
  annual_series_key: string;
  event_year: string;
  is_annual: string;
  theme: string;
  track_name: string;
  location: string;
  venue_name: string;
  venue_address: string;
  venue_contact_person: string;
  venue_contact_phone: string;
  venue_contact_email: string;
  start_date: string;
  end_date: string;
  status: string;
  description: string;
  objectives: string;
  technical_requirements: string;
  registration_link: string;
  zoom_join_url: string;
  zoom_host_url: string;
  zoom_meeting_id: string;
  zoom_passcode: string;
  expected_attendees: string;
  owner_staff_member_id: string;
  partner_stakeholder_ids: string[];
};

type RouteDef = {
  url: string;
  method: "post" | "put" | "patch";
};

type Props = {
  mode: "create" | "edit";
  pageTitle: string;
  title: string;
  description: string;
  breadcrumbs: BreadcrumbItem[];
  submitRoute: RouteDef;
  initialData: EventFormData;
  staffMembers: Array<{ id: number; name: string }>;
  stakeholders: Array<{ id: number; name: string }>;
  eventSeries?: Array<{ id: number; name: string; series_key: string; slug: string }>;
  backHref?: string;
};

const eventDetailNavItems: DomainNavItem[] = [
  { label: "Events", href: "/events", icon: <CalendarRange className="h-4 w-4" /> },
];

function Field({
  label,
  error,
  required,
  children,
}: {
  label: string;
  error?: string;
  required?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="grid gap-2">
      <Label className="text-sm font-medium text-slate-700">
        {label}
        {required ? <span className="ml-1 text-red-600">*</span> : null}
      </Label>
      {children}
      {error ? <p className="text-xs text-red-600">{error}</p> : null}
    </div>
  );
}

export function EventFormPage({
  mode,
  pageTitle,
  title,
  description,
  breadcrumbs,
  submitRoute,
  initialData,
  staffMembers,
  stakeholders,
  eventSeries = [],
  backHref = "/events",
}: Props) {
  const form = useForm<EventFormData>(initialData);

  const togglePartner = (value: string) => {
    const current = form.data.partner_stakeholder_ids;
    form.setData(
      "partner_stakeholder_ids",
      current.includes(value) ? current.filter((item) => item !== value) : [...current, value]
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {mode === "create" ? "Create Event" : "Edit Event"}
            </div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
              <p className="max-w-3xl text-sm text-muted-foreground">{description}</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <DomainNav items={eventDetailNavItems} />
            <Link href={backHref}>
              <Button variant="outline">Back to Events</Button>
            </Link>
          </div>
        </div>

        <form
          onSubmit={(event) => {
            event.preventDefault();
            form.submit(submitRoute.method, submitRoute.url, {
              preserveScroll: true,
            });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
          <div className="space-y-5">
            <Card className="border-orange-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-orange-50 p-2 text-orange-600">
                    <Presentation className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Event Identity</CardTitle>
                    <CardDescription>Core event identity, ownership, and annual-series positioning.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Event Title" required error={form.errors.title}>
                  <Input value={form.data.title} onChange={(e) => form.setData("title", e.target.value)} />
                </Field>
                <Field label="Event Type" error={form.errors.event_type}>
                  <Input value={form.data.event_type} onChange={(e) => form.setData("event_type", e.target.value)} />
                </Field>
                <Field label="Event Format" error={form.errors.event_format}>
                  <Select value={form.data.event_format || undefined} onValueChange={(value) => form.setData("event_format", value)}>
                    <SelectTrigger><SelectValue placeholder="Select format" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="physical">Physical</SelectItem>
                      <SelectItem value="virtual">Virtual</SelectItem>
                      <SelectItem value="hybrid">Hybrid</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <Field label="Event Line" error={form.errors.event_series_id}>
                  <Select
                    value={form.data.event_series_id || "none"}
                    onValueChange={(value) => {
                      if (value === "none") {
                        form.setData({
                          ...form.data,
                          event_series_id: "",
                        });
                        return;
                      }

                      const selected = eventSeries.find((series) => String(series.id) === value);
                      form.setData({
                        ...form.data,
                        event_series_id: value,
                        annual_series_key: selected?.series_key ?? form.data.annual_series_key,
                        is_annual: "1",
                      });
                    }}
                  >
                    <SelectTrigger><SelectValue placeholder="Standalone or select line" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">Standalone / legacy key</SelectItem>
                      {eventSeries.map((series) => (
                        <SelectItem key={series.id} value={String(series.id)}>
                          {series.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </Field>
                <Field label="Annual Series Key" error={form.errors.annual_series_key}>
                  <Input value={form.data.annual_series_key} onChange={(e) => form.setData("annual_series_key", e.target.value)} />
                </Field>
                <Field label="Event Year" error={form.errors.event_year}>
                  <Input type="number" value={form.data.event_year} onChange={(e) => form.setData("event_year", e.target.value)} />
                </Field>
                <Field label="Annual Event" error={form.errors.is_annual}>
                  <Select value={form.data.is_annual || "1"} onValueChange={(value) => form.setData("is_annual", value)}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1">Yes</SelectItem>
                      <SelectItem value="0">No</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <Field label="Theme" error={form.errors.theme}>
                  <Input value={form.data.theme} onChange={(e) => form.setData("theme", e.target.value)} />
                </Field>
                <Field label="Track / Stream" error={form.errors.track_name}>
                  <Input value={form.data.track_name} onChange={(e) => form.setData("track_name", e.target.value)} />
                </Field>
                <Field label="Event Owner" error={form.errors.owner_staff_member_id}>
                  <Select value={form.data.owner_staff_member_id || undefined} onValueChange={(value) => form.setData("owner_staff_member_id", value)}>
                    <SelectTrigger><SelectValue placeholder="Select owner" /></SelectTrigger>
                    <SelectContent>
                      {staffMembers.map((staff) => (
                        <SelectItem key={staff.id} value={String(staff.id)}>
                          {staff.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </Field>
              </CardContent>
            </Card>

            <Card className="border-orange-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-orange-50 p-2 text-orange-600">
                    <MapPinned className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Venue and Schedule</CardTitle>
                    <CardDescription>Venue, delivery window, and operational location details.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Location" error={form.errors.location}>
                  <Input value={form.data.location} onChange={(e) => form.setData("location", e.target.value)} />
                </Field>
                <Field label="Venue Name" error={form.errors.venue_name}>
                  <Input value={form.data.venue_name} onChange={(e) => form.setData("venue_name", e.target.value)} />
                </Field>
                <Field label="Start Date" required error={form.errors.start_date}>
                  <Input type="date" value={form.data.start_date} onChange={(e) => form.setData("start_date", e.target.value)} />
                </Field>
                <Field label="End Date" error={form.errors.end_date}>
                  <Input type="date" value={form.data.end_date} onChange={(e) => form.setData("end_date", e.target.value)} />
                </Field>
                <Field label="Status" required error={form.errors.status}>
                  <Select value={form.data.status || undefined} onValueChange={(value) => form.setData("status", value)}>
                    <SelectTrigger><SelectValue placeholder="Select status" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="planned">Planned</SelectItem>
                      <SelectItem value="open_for_registration">Open For Registration</SelectItem>
                      <SelectItem value="registration_closed">Registration Closed</SelectItem>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="completed">Completed</SelectItem>
                      <SelectItem value="cancelled">Cancelled</SelectItem>
                      <SelectItem value="postponed">Postponed</SelectItem>
                      <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <Field label="Expected Attendees" error={form.errors.expected_attendees}>
                  <Input type="number" value={form.data.expected_attendees} onChange={(e) => form.setData("expected_attendees", e.target.value)} />
                </Field>
                <Field label="Venue Contact Person" error={form.errors.venue_contact_person}>
                  <Input value={form.data.venue_contact_person} onChange={(e) => form.setData("venue_contact_person", e.target.value)} />
                </Field>
                <Field label="Venue Contact Phone" error={form.errors.venue_contact_phone}>
                  <Input value={form.data.venue_contact_phone} onChange={(e) => form.setData("venue_contact_phone", e.target.value)} />
                </Field>
                <Field label="Venue Contact Email" error={form.errors.venue_contact_email}>
                  <Input type="email" value={form.data.venue_contact_email} onChange={(e) => form.setData("venue_contact_email", e.target.value)} />
                </Field>
                <Field label="Venue Address" error={form.errors.venue_address}>
                  <textarea
                    value={form.data.venue_address}
                    onChange={(e) => form.setData("venue_address", e.target.value)}
                    className="min-h-24 rounded-md border px-3 py-2 text-sm"
                  />
                </Field>
              </CardContent>
            </Card>

            <Card className="border-orange-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-orange-50 p-2 text-orange-600">
                    <Link2 className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Delivery Links and Content</CardTitle>
                    <CardDescription>Registration, Zoom, technical setup, and event narrative.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Registration Link" error={form.errors.registration_link}>
                  <Input value={form.data.registration_link} onChange={(e) => form.setData("registration_link", e.target.value)} />
                </Field>
                <Field label="Zoom Join URL" error={form.errors.zoom_join_url}>
                  <Input value={form.data.zoom_join_url} onChange={(e) => form.setData("zoom_join_url", e.target.value)} />
                </Field>
                <Field label="Zoom Host URL" error={form.errors.zoom_host_url}>
                  <Input value={form.data.zoom_host_url} onChange={(e) => form.setData("zoom_host_url", e.target.value)} />
                </Field>
                <Field label="Zoom Meeting ID" error={form.errors.zoom_meeting_id}>
                  <Input value={form.data.zoom_meeting_id} onChange={(e) => form.setData("zoom_meeting_id", e.target.value)} />
                </Field>
                <Field label="Zoom Passcode" error={form.errors.zoom_passcode}>
                  <Input value={form.data.zoom_passcode} onChange={(e) => form.setData("zoom_passcode", e.target.value)} />
                </Field>
                <Field label="Technical Requirements" error={form.errors.technical_requirements}>
                  <textarea
                    value={form.data.technical_requirements}
                    onChange={(e) => form.setData("technical_requirements", e.target.value)}
                    className="min-h-24 rounded-md border px-3 py-2 text-sm"
                  />
                </Field>
                <Field label="Description" error={form.errors.description}>
                  <textarea
                    value={form.data.description}
                    onChange={(e) => form.setData("description", e.target.value)}
                    className="min-h-24 rounded-md border px-3 py-2 text-sm"
                  />
                </Field>
                <Field label="Objectives" error={form.errors.objectives}>
                  <textarea
                    value={form.data.objectives}
                    onChange={(e) => form.setData("objectives", e.target.value)}
                    className="min-h-24 rounded-md border px-3 py-2 text-sm"
                  />
                </Field>
              </CardContent>
            </Card>

            <Card className="border-orange-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-orange-50 p-2 text-orange-600">
                    <Users className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Event Partners</CardTitle>
                    <CardDescription>Select the stakeholders supporting this event.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid gap-3 md:grid-cols-2">
                  {stakeholders.map((stakeholder) => {
                    const selected = form.data.partner_stakeholder_ids.includes(String(stakeholder.id));
                    return (
                      <button
                        key={stakeholder.id}
                        type="button"
                        onClick={() => togglePartner(String(stakeholder.id))}
                        className={`rounded-lg border px-4 py-3 text-left text-sm transition ${
                          selected
                            ? "border-red-600 bg-red-50 text-red-700"
                            : "border-slate-200 bg-white text-slate-700 hover:border-orange-300 hover:bg-orange-50"
                        }`}
                      >
                        {stakeholder.name}
                      </button>
                    );
                  })}
                </div>
              </CardContent>
            </Card>

            <div className="flex flex-wrap items-center justify-end gap-3">
              <Link href={backHref}>
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" disabled={form.processing} className="bg-red-600 text-white hover:bg-red-700">
                {form.processing ? "Saving..." : mode === "create" ? "Create Event" : "Update Event"}
              </Button>
            </div>
          </div>

          <div className="space-y-5">
            <Card className="border-slate-200 bg-slate-900 text-white shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Event Snapshot</CardTitle>
                <CardDescription className="text-slate-300">
                  Current planning posture while you complete the event setup.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm text-slate-200">
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Owner</div>
                  <div className="mt-1 font-medium text-white">
                    {staffMembers.find((staff) => String(staff.id) === form.data.owner_staff_member_id)?.name ?? "Not selected"}
                  </div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Format</div>
                  <div className="mt-1 font-medium capitalize text-white">{form.data.event_format || "Not selected"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Series Key</div>
                  <div className="mt-1 font-medium text-white">{form.data.annual_series_key || "Not set"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Partners</div>
                  <div className="mt-1 font-medium text-white">{form.data.partner_stakeholder_ids.length}</div>
                </div>
              </CardContent>
            </Card>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
