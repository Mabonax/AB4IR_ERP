import { Head, Link, useForm } from "@inertiajs/react";
import { Building2, CalendarRange } from "lucide-react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem } from "@/types";

type WorkstreamFormData = {
  name: string;
  description: string;
  sort_order: string;
};

type Props = {
  mode: "create" | "edit";
  pageTitle: string;
  title: string;
  description: string;
  breadcrumbs: BreadcrumbItem[];
  event: any;
  submitRoute: {
    url: string;
    method: "post" | "put";
  };
  initialData: WorkstreamFormData;
};

export function EventWorkstreamFormPage({
  mode,
  pageTitle,
  title,
  description,
  breadcrumbs,
  event,
  submitRoute,
  initialData,
}: Props) {
  const form = useForm<WorkstreamFormData>(initialData);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {mode === "create" ? "Add Department" : "Edit Department"}
            </div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
              <p className="max-w-3xl text-sm text-muted-foreground">{description}</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <DomainNav items={eventWorkflowNav(event.id)} />
            <Link href={`/events/${event.id}`}>
              <Button variant="outline">Back to Event</Button>
            </Link>
          </div>
        </div>

        <form
          onSubmit={(e) => {
            e.preventDefault();
            form.submit(submitRoute.method, submitRoute.url, { preserveScroll: true });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
          <Card className="border-slate-200 shadow-sm">
            <CardHeader>
              <div className="flex items-start gap-3">
                <div className="rounded-xl bg-red-50 p-2 text-red-600">
                  <Building2 className="h-4 w-4" />
                </div>
                <div>
                  <CardTitle className="text-base">Department Details</CardTitle>
                  <CardDescription>Name the responsibility lane and describe what it owns in the event.</CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="name">Department name</Label>
                <Input id="name" value={form.data.name} onChange={(e) => form.setData("name", e.target.value)} />
                {form.errors.name ? <p className="text-xs text-red-600">{form.errors.name}</p> : null}
              </div>
              <div className="space-y-2">
                <Label htmlFor="sort_order">Sort order</Label>
                <Input
                  id="sort_order"
                  type="number"
                  min={1}
                  value={form.data.sort_order}
                  onChange={(e) => form.setData("sort_order", e.target.value)}
                />
                {form.errors.sort_order ? <p className="text-xs text-red-600">{form.errors.sort_order}</p> : null}
              </div>
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="description">Description</Label>
                <textarea
                  id="description"
                  value={form.data.description}
                  onChange={(e) => form.setData("description", e.target.value)}
                  className="min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                />
                {form.errors.description ? <p className="text-xs text-red-600">{form.errors.description}</p> : null}
              </div>
            </CardContent>
          </Card>

          <div className="space-y-5">
            <Card className="border-slate-200 shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Current Event</CardTitle>
                <CardDescription>This department will live under this event record.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-sm text-slate-700">
                <div className="font-medium text-slate-950">{event.title}</div>
                <div>{event.event_year ?? "No year set"} | {event.venue_name ?? event.location ?? "Venue pending"}</div>
                <div className="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                  Existing departments: {event.workstreams?.length ?? 0}
                </div>
              </CardContent>
            </Card>

            <div className="flex flex-wrap items-center justify-end gap-3">
              <Link href={`/events/${event.id}`}>
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" disabled={form.processing}>
                {form.processing ? "Saving..." : mode === "create" ? "Create Department" : "Save Department"}
              </Button>
            </div>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
