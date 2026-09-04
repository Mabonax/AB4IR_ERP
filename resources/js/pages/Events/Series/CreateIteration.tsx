import { Head, Link, useForm } from "@inertiajs/react";
import { CalendarPlus, Save } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type SeriesPayload = {
  name: string;
  slug: string;
  next_iteration_year: number;
  default_theme: string | null;
  years: Array<{ id: number; title: string; event_year: number | null }>;
};

type FormData = {
  event_year: string;
  source: string;
  source_event_id: string;
  title: string;
  theme: string;
  start_date: string;
  end_date: string;
  location: string;
  venue_name: string;
  copy_partners: boolean;
  copy_workstreams: boolean;
  copy_task_templates: boolean;
};

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
  return (
    <div className="grid gap-2">
      <Label className="text-sm font-medium text-slate-700">{label}</Label>
      {children}
      {error ? <p className="text-xs text-red-600">{error}</p> : null}
    </div>
  );
}

export default function CreateEventIteration({ series }: { series: SeriesPayload }) {
  const form = useForm<FormData>({
    event_year: String(series.next_iteration_year),
    source: series.years.length > 0 ? "latest_iteration" : "series_defaults",
    source_event_id: "",
    title: "",
    theme: series.default_theme ?? "",
    start_date: "",
    end_date: "",
    location: "",
    venue_name: "",
    copy_partners: true,
    copy_workstreams: true,
    copy_task_templates: true,
  });

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: series.name, href: `/event-series/${series.slug}` },
    { title: "Create Iteration", href: `/event-series/${series.slug}/iterations/create` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Create ${series.name} Iteration`} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-orange-700">New Iteration</div>
            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{series.name}</h1>
            <p className="mt-1 max-w-3xl text-sm text-slate-600">
              Create a new yearly event record from controlled defaults. Participants, attendance, evidence, registration links, Zoom links, and closure data are never copied.
            </p>
          </div>
          <Link href={`/event-series/${series.slug}`}>
            <Button variant="outline">Back to Event Line</Button>
          </Link>
        </div>

        <form
          onSubmit={(event) => {
            event.preventDefault();
            form.post(`/event-series/${series.slug}/iterations`, { preserveScroll: true });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
        >
          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <CardTitle>Iteration Setup</CardTitle>
              <CardDescription>Choose the source and edit the year-specific schedule before the event workspace is created.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <Field label="Event Year" error={form.errors.event_year}>
                <Input type="number" value={form.data.event_year} onChange={(event) => form.setData("event_year", event.target.value)} />
              </Field>
              <Field label="Source" error={form.errors.source}>
                <select value={form.data.source} onChange={(event) => form.setData("source", event.target.value)} className="h-10 rounded-md border px-3 text-sm">
                  <option value="series_defaults">Series defaults</option>
                  <option value="latest_iteration">Latest iteration</option>
                  <option value="selected_iteration">Selected previous iteration</option>
                </select>
              </Field>
              {form.data.source === "selected_iteration" ? (
                <Field label="Previous Iteration" error={form.errors.source_event_id}>
                  <select value={form.data.source_event_id} onChange={(event) => form.setData("source_event_id", event.target.value)} className="h-10 rounded-md border px-3 text-sm">
                    <option value="">Select previous iteration</option>
                    {series.years.map((year) => (
                      <option key={year.id} value={String(year.id)}>
                        {year.event_year ?? "Unknown"} - {year.title}
                      </option>
                    ))}
                  </select>
                </Field>
              ) : null}
              <Field label="Title Override" error={form.errors.title}>
                <Input value={form.data.title} onChange={(event) => form.setData("title", event.target.value)} />
              </Field>
              <Field label="Theme Override" error={form.errors.theme}>
                <Input value={form.data.theme} onChange={(event) => form.setData("theme", event.target.value)} />
              </Field>
              <Field label="Start Date" error={form.errors.start_date}>
                <Input type="date" value={form.data.start_date} onChange={(event) => form.setData("start_date", event.target.value)} />
              </Field>
              <Field label="End Date" error={form.errors.end_date}>
                <Input type="date" value={form.data.end_date} onChange={(event) => form.setData("end_date", event.target.value)} />
              </Field>
              <Field label="Location" error={form.errors.location}>
                <Input value={form.data.location} onChange={(event) => form.setData("location", event.target.value)} />
              </Field>
              <Field label="Venue Name" error={form.errors.venue_name}>
                <Input value={form.data.venue_name} onChange={(event) => form.setData("venue_name", event.target.value)} />
              </Field>
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <CalendarPlus className="h-4 w-4" />
                  Copy Controls
                </CardTitle>
                <CardDescription>Only reusable planning structure is copied into the new year.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                {[
                  ["copy_partners", "Copy partners as event-level snapshot"],
                  ["copy_workstreams", "Copy workstream lanes"],
                  ["copy_task_templates", "Copy task templates reset to pending"],
                ].map(([key, label]) => (
                  <label key={key} className="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                    <input
                      type="checkbox"
                      checked={Boolean(form.data[key as keyof FormData])}
                      onChange={(event) => form.setData(key as keyof FormData, event.target.checked as never)}
                      className="mt-1"
                    />
                    <span>{label}</span>
                  </label>
                ))}
              </CardContent>
            </Card>
            <Button type="submit" disabled={form.processing} className="w-full bg-red-600 text-white hover:bg-red-700">
              <Save className="h-4 w-4" />
              {form.processing ? "Creating..." : "Create Iteration"}
            </Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
