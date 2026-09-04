import { Head, Link, useForm } from "@inertiajs/react";
import { CalendarRange, Save } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Events", href: "/events" },
  { title: "Create Event Line", href: "/event-series/create" },
];

type FormData = {
  name: string;
  series_key: string;
  slug: string;
  description: string;
  objectives: string;
  default_title_pattern: string;
  default_event_type: string;
  default_format: string;
  default_theme: string;
  status: string;
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

export default function CreateEventSeries() {
  const form = useForm<FormData>({
    name: "",
    series_key: "",
    slug: "",
    description: "",
    objectives: "",
    default_title_pattern: "",
    default_event_type: "",
    default_format: "",
    default_theme: "",
    status: "active",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Create Event Line" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-orange-700">Event Line</div>
            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Create Event Line</h1>
            <p className="mt-1 max-w-3xl text-sm text-slate-600">
              Set up the permanent identity for a recurring event before creating its yearly iterations.
            </p>
          </div>
          <Link href="/events">
            <Button variant="outline">
              <CalendarRange className="h-4 w-4" />
              Events
            </Button>
          </Link>
        </div>

        <form
          onSubmit={(event) => {
            event.preventDefault();
            form.post("/event-series", { preserveScroll: true });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <CardTitle>Permanent Identity</CardTitle>
              <CardDescription>These defaults pre-fill future iterations but do not rewrite historic event records.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <Field label="Name" error={form.errors.name}>
                <Input value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} />
              </Field>
              <Field label="Series Key" error={form.errors.series_key}>
                <Input value={form.data.series_key} onChange={(event) => form.setData("series_key", event.target.value)} placeholder="digital-youth-festival" />
              </Field>
              <Field label="Slug" error={form.errors.slug}>
                <Input value={form.data.slug} onChange={(event) => form.setData("slug", event.target.value)} placeholder="digital-youth-festival" />
              </Field>
              <Field label="Default Title Pattern" error={form.errors.default_title_pattern}>
                <Input value={form.data.default_title_pattern} onChange={(event) => form.setData("default_title_pattern", event.target.value)} placeholder="Digital Youth Festival {year}" />
              </Field>
              <Field label="Default Event Type" error={form.errors.default_event_type}>
                <Input value={form.data.default_event_type} onChange={(event) => form.setData("default_event_type", event.target.value)} />
              </Field>
              <Field label="Default Format" error={form.errors.default_format}>
                <select value={form.data.default_format} onChange={(event) => form.setData("default_format", event.target.value)} className="h-10 rounded-md border px-3 text-sm">
                  <option value="">Select format</option>
                  <option value="physical">Physical</option>
                  <option value="virtual">Virtual</option>
                  <option value="hybrid">Hybrid</option>
                </select>
              </Field>
              <Field label="Default Theme" error={form.errors.default_theme}>
                <Input value={form.data.default_theme} onChange={(event) => form.setData("default_theme", event.target.value)} />
              </Field>
              <Field label="Status" error={form.errors.status}>
                <select value={form.data.status} onChange={(event) => form.setData("status", event.target.value)} className="h-10 rounded-md border px-3 text-sm">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </Field>
              <Field label="Description" error={form.errors.description}>
                <textarea value={form.data.description} onChange={(event) => form.setData("description", event.target.value)} className="min-h-28 rounded-md border px-3 py-2 text-sm" />
              </Field>
              <Field label="Objectives" error={form.errors.objectives}>
                <textarea value={form.data.objectives} onChange={(event) => form.setData("objectives", event.target.value)} className="min-h-28 rounded-md border px-3 py-2 text-sm" />
              </Field>
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Series Repository</CardTitle>
                <CardDescription>A document workspace is provisioned automatically for logos, posters, media, and working files.</CardDescription>
              </CardHeader>
            </Card>
            <Button type="submit" disabled={form.processing} className="w-full bg-red-600 text-white hover:bg-red-700">
              <Save className="h-4 w-4" />
              {form.processing ? "Saving..." : "Create Event Line"}
            </Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
