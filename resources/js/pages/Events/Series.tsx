import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import { eventSeriesNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem } from "@/types";

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

export default function EventSeriesShow({
  series,
}: {
  series: {
    series_key: string;
    title: string | null;
    event_type: string | null;
    theme: string | null;
    track_name: string | null;
    stats: {
      years_run: number;
      completed_events: number;
      active_events: number;
      open_events: number;
      total_participants: number;
      total_attendees: number;
      total_speakers: number;
    };
    years: Array<{
      id: number;
      title: string;
      event_year: number | null;
      status: string;
      location: string | null;
      venue_name: string | null;
      start_date: string | null;
      end_date: string | null;
      owner_name: string | null;
      participant_count: number;
      speaker_count: number;
      attendee_count: number;
      planning_summary: { open_tasks: number; overdue_tasks: number };
      event_day_summary: { event_day_tasks_open: number; outstanding_arrivals: number };
      outcome_report: { report_status: string };
    }>;
  };
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: series.title ?? "Series", href: `/events/series/${series.series_key}` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={series.title ?? "Event Series"} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-[0.24em] text-orange-700">Event Series</div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{series.title ?? "Untitled series"}</h1>
              <p className="text-sm text-muted-foreground">
                Review the historical run of this event line year by year, then open the exact year you need for its delivery, register, and reporting stats.
              </p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <DomainNav items={eventSeriesNav(series.series_key)} />
            <Link href="/events">
              <Button variant="outline">Back to Events</Button>
            </Link>
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          {[
            ["Years Run", series.stats.years_run],
            ["Completed", series.stats.completed_events],
            ["Active", series.stats.active_events],
            ["Open", series.stats.open_events],
            ["Participants", series.stats.total_participants],
            ["Speakers", series.stats.total_speakers],
          ].map(([label, value]) => (
            <Card key={String(label)} className="border-slate-200 shadow-sm">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm">{label}</CardTitle>
              </CardHeader>
              <CardContent className="text-3xl font-semibold text-slate-950">{String(value)}</CardContent>
            </Card>
          ))}
        </div>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle>Series History</CardTitle>
            <CardDescription>
              Select the exact year you are interested in to open that event file and view its detailed operational stats.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {series.years.map((year) => (
              <div key={year.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="space-y-1">
                    <div className="text-lg font-semibold text-slate-950">{year.event_year ?? "Unknown Year"}</div>
                    <div className="text-sm text-slate-500">
                      {[year.location, year.venue_name, year.owner_name].filter(Boolean).join(" | ") || year.title}
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={`rounded-full border px-2.5 py-1 text-[11px] font-medium ${statusChipClass(year.status)}`}>
                      {statusLabels[year.status] ?? year.status.replaceAll("_", " ")}
                    </span>
                    <Link href={`/events/${year.id}`}>
                      <Button size="sm" className="bg-red-600 text-white hover:bg-red-700">Open {year.event_year ?? "Event"}</Button>
                    </Link>
                  </div>
                </div>

                <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Schedule</div>
                    <div className="mt-1 text-sm font-medium text-slate-900">
                      {year.start_date && year.end_date && year.start_date !== year.end_date
                        ? `${year.start_date} to ${year.end_date}`
                        : year.start_date ?? year.end_date ?? "Not scheduled"}
                    </div>
                  </div>
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Participants</div>
                    <div className="mt-1 text-sm font-medium text-slate-900">{year.participant_count}</div>
                  </div>
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Open Tasks</div>
                    <div className="mt-1 text-sm font-medium text-slate-900">{year.planning_summary.open_tasks}</div>
                  </div>
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Overdue Tasks</div>
                    <div className="mt-1 text-sm font-medium text-slate-900">{year.planning_summary.overdue_tasks}</div>
                  </div>
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Report Status</div>
                    <div className="mt-1 text-sm font-medium capitalize text-slate-900">
                      {year.outcome_report.report_status.replaceAll("_", " ")}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
