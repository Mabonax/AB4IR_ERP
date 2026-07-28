import { Head, Link, usePage } from "@inertiajs/react";
import {
  CalendarRange,
  CircleDashed,
  FileText,
  Plus,
  Presentation,
  RadioTower,
  Users,
} from "lucide-react";
import { useMemo } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { eventNavItems } from "@/config/domain-nav/events";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Events", href: "/events" },
];

type EventRow = {
  id: number;
  title: string;
  event_type: string | null;
  event_year: number | null;
  annual_series_key: string | null;
  location: string | null;
  venue_name: string | null;
  owner_name: string | null;
  status: string;
  start_date: string | null;
  end_date: string | null;
  participant_count: number;
  speaker_count: number;
  attendee_count: number;
  expected_attendees: number | null;
  planning_summary?: {
    open_tasks: number;
    overdue_tasks: number;
  };
  event_day_summary?: {
    event_day_tasks_open: number;
    outstanding_arrivals: number;
  };
  outcome_report?: {
    report_status?: string | null;
  };
};

const statusLabels: Record<string, string> = {
  planned: "Planned",
  open_for_registration: "Open",
  active: "Active",
  completed: "Completed",
  cancelled: "Cancelled",
};

function statusChipClass(status: string): string {
  switch (status) {
    case "completed":
      return "border-emerald-200 bg-emerald-50 text-emerald-700";
    case "active":
      return "border-sky-200 bg-sky-50 text-sky-700";
    case "open_for_registration":
      return "border-amber-200 bg-amber-50 text-amber-700";
    case "cancelled":
      return "border-rose-200 bg-rose-50 text-rose-700";
    default:
      return "border-slate-200 bg-slate-100 text-slate-700";
  }
}

function reportChipClass(status?: string | null): string {
  switch (status) {
    case "finalized":
      return "border-emerald-200 bg-emerald-50 text-emerald-700";
    case "submitted":
      return "border-sky-200 bg-sky-50 text-sky-700";
    default:
      return "border-amber-200 bg-amber-50 text-amber-700";
  }
}

function formatDateRange(startDate?: string | null, endDate?: string | null): string {
  if (!startDate && !endDate) {
    return "Dates not scheduled";
  }

  if (startDate && endDate && startDate !== endDate) {
    return `${startDate} to ${endDate}`;
  }

  return startDate ?? endDate ?? "Dates not scheduled";
}

export default function EventsIndex({
  events,
  stats,
}: {
  events: { data: EventRow[] };
  stats: {
    total_events: number;
    planned_events: number;
    open_events: number;
    active_events: number;
    completed_events: number;
    annual_events: number;
    total_participants: number;
    total_attendees: number;
    total_speakers: number;
  };
}) {
  const { auth } = usePage<SharedData>().props;
  const canManage = (auth?.user?.permissions ?? []).includes("domain.events.manage");
  const portfolio = events.data ?? [];

  const seriesDirectory = useMemo(() => {
    const grouped = new Map<
      string,
      {
        key: string;
        title: string;
        event_type: string | null;
        years: EventRow[];
        participants: number;
        completed: number;
        active: number;
      }
    >();

    for (const event of portfolio) {
      if (!event.annual_series_key) {
        continue;
      }

      const existing = grouped.get(event.annual_series_key) ?? {
        key: event.annual_series_key,
        title: event.title,
        event_type: event.event_type,
        years: [],
        participants: 0,
        completed: 0,
        active: 0,
      };

      existing.years.push(event);
      existing.participants += event.participant_count ?? 0;
      if (event.status === "completed") {
        existing.completed += 1;
      }
      if (event.status === "active") {
        existing.active += 1;
      }

      grouped.set(event.annual_series_key, existing);
    }

    return Array.from(grouped.values())
      .map((series) => ({
        ...series,
        years: [...series.years].sort((a, b) => (b.event_year ?? 0) - (a.event_year ?? 0)),
      }))
      .sort((a, b) => (b.years[0]?.event_year ?? 0) - (a.years[0]?.event_year ?? 0));
  }, [portfolio]);

  const standaloneEvents = useMemo(
    () =>
      portfolio
        .filter((event) => !event.annual_series_key)
        .sort((a, b) => (b.start_date ?? "").localeCompare(a.start_date ?? "")),
    [portfolio]
  );

  const actionQueue = useMemo(() => {
    return [...portfolio]
      .map((event) => ({
        id: event.id,
        title: event.title,
        status: event.status,
        openTasks: event.planning_summary?.open_tasks ?? 0,
        overdueTasks: event.planning_summary?.overdue_tasks ?? 0,
        reportStatus: event.outcome_report?.report_status ?? "draft",
        arrivals: event.event_day_summary?.outstanding_arrivals ?? 0,
      }))
      .filter((event) => event.overdueTasks > 0 || event.arrivals > 0 || event.reportStatus !== "finalized")
      .sort((a, b) => b.overdueTasks - a.overdueTasks || b.arrivals - a.arrivals)
      .slice(0, 5);
  }, [portfolio]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Events" />

      <div className="space-y-6 p-4">
        <section className="overflow-hidden rounded-3xl border border-red-200 bg-gradient-to-br from-red-50 via-white to-red-50 shadow-sm">
          <div className="grid gap-6 p-6 xl:grid-cols-[1.3fr,0.7fr] xl:p-8">
            <div className="space-y-5">
              <div className="flex flex-wrap items-center gap-3">
                <span className="inline-flex items-center gap-2 rounded-full border border-red-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-red-700">
                  <Presentation className="h-3.5 w-3.5" />
                  Events Domain
                </span>
                <DomainNav items={eventNavItems} />
              </div>

              <div className="space-y-3">
                <h1 className="max-w-3xl text-3xl font-semibold tracking-tight text-slate-950">
                  Browse events by event line first, then open the exact year you need.
                </h1>
                <p className="max-w-3xl text-sm leading-6 text-slate-600">
                  This directory is designed for institutional memory: select an event line, choose the year, then review the planning, participant, register, event-day, and reporting data for that specific run.
                </p>
              </div>

              <div className="flex flex-wrap items-center gap-3">
                {canManage ? (
                  <Link href="/events/create">
                    <Button className="bg-red-600 text-white hover:bg-red-700">
                      <Plus className="h-4 w-4" />
                      Add Event
                    </Button>
                  </Link>
                ) : null}
                <Link href={seriesDirectory[0] ? `/events/series/${seriesDirectory[0].key}` : "/events"}>
                  <Button variant="outline">Open Latest Event Line</Button>
                </Link>
              </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
              {[
                {
                  label: "Event Lines",
                  value: seriesDirectory.length,
                  hint: "Distinct annual or recurring event groups",
                },
                {
                  label: "Open Planning Tasks",
                  value: portfolio.reduce((sum, event) => sum + (event.planning_summary?.open_tasks ?? 0), 0),
                  hint: "Across all events",
                },
                {
                  label: "Outstanding Arrivals",
                  value: portfolio.reduce((sum, event) => sum + (event.event_day_summary?.outstanding_arrivals ?? 0), 0),
                  hint: "Confirmed or registered people not yet present",
                },
                {
                  label: "Draft Reports",
                  value: portfolio.filter((event) => (event.outcome_report?.report_status ?? "draft") !== "finalized").length,
                  hint: "Events still needing close-out",
                },
              ].map((item) => (
                <div key={item.label} className="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm">
                  <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{item.label}</div>
                  <div className="mt-2 text-3xl font-semibold text-slate-950">{item.value}</div>
                  <div className="mt-1 text-xs text-slate-500">{item.hint}</div>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          {[
            {
              label: "Total Events",
              value: stats.total_events,
              hint: "All event records",
              icon: <CalendarRange className="h-4 w-4" />,
            },
            {
              label: "Open Registration",
              value: stats.open_events,
              hint: "Currently taking participants",
              icon: <RadioTower className="h-4 w-4" />,
            },
            {
              label: "Active Events",
              value: stats.active_events,
              hint: "Running now",
              icon: <CircleDashed className="h-4 w-4" />,
            },
            {
              label: "Annual Series",
              value: stats.annual_events,
              hint: "Recurring event runs",
              icon: <Presentation className="h-4 w-4" />,
            },
            {
              label: "Participants",
              value: stats.total_participants,
              hint: `${stats.total_attendees} attendees, ${stats.total_speakers} speakers/facilitators`,
              icon: <Users className="h-4 w-4" />,
            },
            {
              label: "Completed Events",
              value: stats.completed_events,
              hint: "Closed delivery cycles",
              icon: <FileText className="h-4 w-4" />,
            },
          ].map((item) => (
            <Card key={item.label} className="border-slate-200 shadow-sm">
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <CardTitle className="text-sm font-semibold text-slate-900">{item.label}</CardTitle>
                    <CardDescription>{item.hint}</CardDescription>
                  </div>
                  <div className="rounded-xl bg-red-50 p-2 text-red-600">{item.icon}</div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-semibold text-slate-950">{item.value}</div>
              </CardContent>
            </Card>
          ))}
        </section>

        <section className="grid gap-6 xl:grid-cols-[1.7fr,0.9fr]">
          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <CardTitle>Event Lines</CardTitle>
              <CardDescription>
                Click an event line, then choose the year you are interested in to open that specific event file.
              </CardDescription>
            </CardHeader>
            <CardContent>
              {seriesDirectory.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                  No annual event lines are available yet. Start by creating an event with an annual series key.
                </div>
              ) : (
                <div className="space-y-4">
                  {seriesDirectory.map((series) => (
                    <div key={series.key} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="space-y-1">
                          <div className="text-lg font-semibold text-slate-950">{series.title}</div>
                          <div className="text-sm text-slate-500">
                            {[series.event_type, series.key].filter(Boolean).join(" | ")}
                          </div>
                        </div>
                        <Link href={`/events/series/${series.key}`}>
                          <Button variant="outline" size="sm">Open Event Line</Button>
                        </Link>
                      </div>

                      <div className="mt-4 grid gap-3 sm:grid-cols-4">
                        <div className="rounded-xl bg-slate-50 p-3">
                          <div className="text-xs uppercase tracking-wide text-slate-500">Years</div>
                          <div className="mt-1 text-sm font-medium text-slate-900">{series.years.length}</div>
                        </div>
                        <div className="rounded-xl bg-slate-50 p-3">
                          <div className="text-xs uppercase tracking-wide text-slate-500">Participants</div>
                          <div className="mt-1 text-sm font-medium text-slate-900">{series.participants}</div>
                        </div>
                        <div className="rounded-xl bg-slate-50 p-3">
                          <div className="text-xs uppercase tracking-wide text-slate-500">Active Years</div>
                          <div className="mt-1 text-sm font-medium text-slate-900">{series.active}</div>
                        </div>
                        <div className="rounded-xl bg-slate-50 p-3">
                          <div className="text-xs uppercase tracking-wide text-slate-500">Completed Years</div>
                          <div className="mt-1 text-sm font-medium text-slate-900">{series.completed}</div>
                        </div>
                      </div>

                      <div className="mt-4">
                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Choose a Year</div>
                        <div className="mt-3 flex flex-wrap gap-2">
                          {series.years.map((year) => (
                            <Link key={year.id} href={`/events/${year.id}`}>
                              <Button variant="outline" size="sm">
                                {year.event_year ?? "Event"} • {statusLabels[year.status] ?? year.status.replaceAll("_", " ")}
                              </Button>
                            </Link>
                          ))}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <div className="space-y-6">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Standalone Events</CardTitle>
                <CardDescription>Events that do not belong to an annual series.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {standaloneEvents.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    All current events belong to annual lines.
                  </div>
                ) : (
                  standaloneEvents.slice(0, 6).map((event) => (
                    <div key={event.id} className="rounded-xl border border-slate-200 bg-white p-4">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <div className="font-semibold text-slate-950">{event.title}</div>
                          <div className="mt-1 text-xs text-slate-500">
                            {[event.event_type, event.location, event.event_year].filter(Boolean).join(" | ") || "Standalone event"}
                          </div>
                        </div>
                        <Link href={`/events/${event.id}`}>
                          <Button variant="outline" size="sm">Open</Button>
                        </Link>
                      </div>
                      <div className="mt-3 text-xs text-slate-500">
                        {formatDateRange(event.start_date, event.end_date)}
                      </div>
                    </div>
                  ))
                )}
              </CardContent>
            </Card>

            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle>Action Queue</CardTitle>
                <CardDescription>Where the events team should intervene next.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {actionQueue.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    No immediate event interventions are currently flagged.
                  </div>
                ) : (
                  actionQueue.map((event) => (
                    <Link
                      key={event.id}
                      href={`/events/${event.id}`}
                      className="block rounded-xl border border-slate-200 bg-white p-4 transition hover:border-red-300 hover:bg-red-50"
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <div className="font-semibold text-slate-950">{event.title}</div>
                          <div className="mt-1 text-xs uppercase tracking-wide text-slate-500">
                            {statusLabels[event.status] ?? event.status.replaceAll("_", " ")}
                          </div>
                        </div>
                        <span className={`rounded-full border px-2 py-1 text-[11px] font-medium ${reportChipClass(event.reportStatus)}`}>
                          {event.reportStatus.replaceAll("_", " ")}
                        </span>
                      </div>
                      <div className="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div>
                          <div className="text-xs uppercase tracking-wide text-slate-500">Open</div>
                          <div className="mt-1 font-semibold text-slate-900">{event.openTasks}</div>
                        </div>
                        <div>
                          <div className="text-xs uppercase tracking-wide text-slate-500">Overdue</div>
                          <div className="mt-1 font-semibold text-slate-900">{event.overdueTasks}</div>
                        </div>
                        <div>
                          <div className="text-xs uppercase tracking-wide text-slate-500">Arrivals</div>
                          <div className="mt-1 font-semibold text-slate-900">{event.arrivals}</div>
                        </div>
                      </div>
                    </Link>
                  ))
                )}
              </CardContent>
            </Card>
          </div>
        </section>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle>All Event Records</CardTitle>
            <CardDescription>
              Use this administrative register when you need a flat list instead of the event-line chronology above.
            </CardDescription>
          </CardHeader>
          <CardContent>
            {portfolio.length === 0 ? (
              <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                No events are registered yet.
              </div>
            ) : (
              <div className="overflow-x-auto rounded-2xl border border-slate-200">
                <table className="min-w-full text-sm">
                  <thead className="bg-slate-950 text-left text-xs uppercase tracking-wide text-slate-200">
                    <tr>
                      <th className="px-4 py-3 font-medium">Event</th>
                      <th className="px-4 py-3 font-medium">Series / Schedule</th>
                      <th className="px-4 py-3 font-medium">Delivery</th>
                      <th className="px-4 py-3 font-medium">Planning</th>
                      <th className="px-4 py-3 font-medium">Participants</th>
                      <th className="px-4 py-3 font-medium">Reporting</th>
                      <th className="px-4 py-3 font-medium">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 bg-white">
                    {portfolio.map((event) => (
                      <tr key={event.id} className="align-top hover:bg-slate-50">
                        <td className="px-4 py-4">
                          <div className="font-semibold text-slate-950">{event.title}</div>
                          <div className="mt-1 text-xs text-slate-500">
                            {[event.event_type, event.owner_name].filter(Boolean).join(" | ") || "Operational record"}
                          </div>
                          <div className="mt-2">
                            <span className={`rounded-full border px-2.5 py-1 text-[11px] font-medium ${statusChipClass(event.status)}`}>
                              {statusLabels[event.status] ?? event.status.replaceAll("_", " ")}
                            </span>
                          </div>
                        </td>
                        <td className="px-4 py-4">
                          <div className="font-medium text-slate-900">{event.annual_series_key ?? "Standalone event"}</div>
                          <div className="mt-1 text-xs text-slate-500">{event.event_year ?? "-"}</div>
                          <div className="mt-2 text-xs text-slate-500">{formatDateRange(event.start_date, event.end_date)}</div>
                        </td>
                        <td className="px-4 py-4">
                          <div className="font-medium text-slate-900">{event.location ?? "Location pending"}</div>
                          <div className="text-xs text-slate-500">{event.venue_name ?? "Venue pending"}</div>
                          <div className="mt-3 text-xs text-slate-500">Expected attendees: {event.expected_attendees ?? "-"}</div>
                        </td>
                        <td className="px-4 py-4">
                          <div className="space-y-2">
                            <div>
                              <div className="text-xs uppercase tracking-wide text-slate-500">Open Tasks</div>
                              <div className="font-semibold text-slate-900">{event.planning_summary?.open_tasks ?? 0}</div>
                            </div>
                            <div>
                              <div className="text-xs uppercase tracking-wide text-slate-500">Overdue</div>
                              <div className="font-semibold text-slate-900">{event.planning_summary?.overdue_tasks ?? 0}</div>
                            </div>
                          </div>
                        </td>
                        <td className="px-4 py-4">
                          <div className="space-y-2">
                            <div>
                              <div className="text-xs uppercase tracking-wide text-slate-500">Total</div>
                              <div className="font-semibold text-slate-900">{event.participant_count}</div>
                            </div>
                            <div className="text-xs text-slate-500">
                              {event.speaker_count} speakers/facilitators • {event.attendee_count} attendees/other
                            </div>
                          </div>
                        </td>
                        <td className="px-4 py-4">
                          <span className={`rounded-full border px-2.5 py-1 text-[11px] font-medium ${reportChipClass(event.outcome_report?.report_status)}`}>
                            {(event.outcome_report?.report_status ?? "draft").replaceAll("_", " ")}
                          </span>
                          <div className="mt-3 text-xs text-slate-500">
                            Event-day open tasks: {event.event_day_summary?.event_day_tasks_open ?? 0}
                          </div>
                        </td>
                        <td className="px-4 py-4">
                          <div className="flex flex-wrap gap-2">
                            <Link href={`/events/${event.id}`}>
                              <Button variant="outline" size="sm">View</Button>
                            </Link>
                            {canManage ? (
                              <Link href={`/events/${event.id}/edit`}>
                                <Button size="sm" className="bg-red-600 text-white hover:bg-red-700">Edit</Button>
                              </Link>
                            ) : null}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
