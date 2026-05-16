import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import { useMemo } from "react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import businessDevelopment from "@/routes/business-development";
import { type BreadcrumbItem, type SharedData } from "@/types";

type SessionRow = {
  id: number;
  title: string;
  scheduled_for: string | null;
  venue: string | null;
  status: string;
  status_label: string;
  summary: {
    panelists_total: number;
    prospects_total: number;
    scorecards_expected: number;
    scorecards_submitted: number;
    scorecards_pending: number;
    consolidated_prospects: number;
    decided_prospects: number;
  };
};

type PanelistOption = {
  id: number;
  name: string;
  email: string | null;
};

type ProspectOption = {
  id: number;
  company_name: string;
  full_name: string;
  pitch_scheduled_at: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Pitch Sessions", href: businessDevelopment.pitchSessions.index().url },
];

export default function PitchSessionsIndex({
  sessions,
  panelists,
  prospects,
}: {
  sessions: {
    data: SessionRow[];
    links?: Array<{ url: string | null; label: string; active: boolean }>;
    meta?: {
      total?: number;
      links?: Array<{ url: string | null; label: string; active: boolean }>;
    };
  };
  panelists: PanelistOption[];
  prospects: ProspectOption[];
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;

  const form = useForm({
    title: "",
    scheduled_for: "",
    venue: "",
    expected_prospect_count: "",
    notes: "",
    panelists: [] as number[],
    prospects: [] as number[],
  });

  const paginationLinks = useMemo(() => {
    if (Array.isArray(sessions.links)) return sessions.links;
    if (Array.isArray(sessions.meta?.links)) return sessions.meta.links;
    return [];
  }, [sessions.links, sessions.meta?.links]);

  const toggleSelection = (field: "panelists" | "prospects", value: number) => {
    const current = form.data[field];
    form.setData(
      field,
      current.includes(value) ? current.filter((item) => item !== value) : [...current, value]
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Pitch Sessions" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Pitch Sessions</h1>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <button
              type="button"
              onClick={() => router.visit(businessDevelopment.pitchSessions.index().url)}
              className="rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Refresh
            </button>
          </div>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {flash.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Schedule Pitch Session</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Define the panel, attach prospects, and prepare the adjudication day.
          </p>

          <form
            className="mt-4 space-y-4"
            onSubmit={(e) => {
              e.preventDefault();
              form.post(businessDevelopment.pitchSessions.store().url, {
                preserveScroll: true,
              });
            }}
          >
            <div className="grid gap-3 md:grid-cols-2">
              <div>
                <label className="mb-1 block text-sm font-medium">Session Title</label>
                <input
                  value={form.data.title}
                  onChange={(e) => form.setData("title", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
                {form.errors.title ? <p className="mt-1 text-sm text-red-600">{form.errors.title}</p> : null}
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Scheduled For</label>
                <input
                  type="datetime-local"
                  value={form.data.scheduled_for}
                  onChange={(e) => form.setData("scheduled_for", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
                {form.errors.scheduled_for ? (
                  <p className="mt-1 text-sm text-red-600">{form.errors.scheduled_for}</p>
                ) : null}
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Venue</label>
                <input
                  value={form.data.venue}
                  onChange={(e) => form.setData("venue", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium">Expected Prospects</label>
                <input
                  type="number"
                  min={1}
                  value={form.data.expected_prospect_count}
                  onChange={(e) => form.setData("expected_prospect_count", e.currentTarget.value)}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                />
              </div>
            </div>

            <div>
              <label className="mb-1 block text-sm font-medium">Session Notes</label>
              <textarea
                rows={3}
                value={form.data.notes}
                onChange={(e) => form.setData("notes", e.currentTarget.value)}
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
              />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <label className="mb-2 block text-sm font-medium">Panel Members</label>
                <div className="max-h-64 space-y-2 overflow-y-auto rounded-lg border p-3">
                  {panelists.map((panelist) => (
                    <label key={panelist.id} className="flex items-start gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={form.data.panelists.includes(panelist.id)}
                        onChange={() => toggleSelection("panelists", panelist.id)}
                      />
                      <span>
                        <span className="font-medium">{panelist.name}</span>
                        <span className="block text-xs text-muted-foreground">{panelist.email ?? "No email"}</span>
                      </span>
                    </label>
                  ))}
                </div>
                {form.errors.panelists ? <p className="mt-1 text-sm text-red-600">{form.errors.panelists}</p> : null}
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium">Prospects</label>
                <div className="max-h-64 space-y-2 overflow-y-auto rounded-lg border p-3">
                  {prospects.map((prospect) => (
                    <label key={prospect.id} className="flex items-start gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={form.data.prospects.includes(prospect.id)}
                        onChange={() => toggleSelection("prospects", prospect.id)}
                      />
                      <span>
                        <span className="font-medium">{prospect.company_name}</span>
                        <span className="block text-xs text-muted-foreground">{prospect.full_name}</span>
                      </span>
                    </label>
                  ))}
                </div>
                {form.errors.prospects ? <p className="mt-1 text-sm text-red-600">{form.errors.prospects}</p> : null}
              </div>
            </div>

            <button
              type="submit"
              disabled={form.processing}
              className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
            >
              {form.processing ? "Scheduling..." : "Create Session"}
            </button>
          </form>
        </section>

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left font-medium">Session</th>
                <th className="px-3 py-2 text-left font-medium">Status</th>
                <th className="px-3 py-2 text-left font-medium">Panel</th>
                <th className="px-3 py-2 text-left font-medium">Prospects</th>
                <th className="px-3 py-2 text-left font-medium">Scorecards</th>
                <th className="px-3 py-2 text-left font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              {sessions.data.length === 0 ? (
                <tr>
                  <td className="px-3 py-4 text-muted-foreground" colSpan={6}>
                    No pitch sessions scheduled yet.
                  </td>
                </tr>
              ) : (
                sessions.data.map((session) => (
                  <tr key={session.id} className="border-t">
                    <td className="px-3 py-2">
                      <div className="font-medium">{session.title}</div>
                      <div className="text-xs text-muted-foreground">
                        {session.scheduled_for ?? "Unscheduled"} | {session.venue ?? "Venue pending"}
                      </div>
                    </td>
                    <td className="px-3 py-2">{session.status_label}</td>
                    <td className="px-3 py-2">{session.summary.panelists_total}</td>
                    <td className="px-3 py-2">
                      {session.summary.prospects_total}
                      <div className="text-xs text-muted-foreground">
                        Consolidated: {session.summary.consolidated_prospects} | Decided: {session.summary.decided_prospects}
                      </div>
                    </td>
                    <td className="px-3 py-2">
                      {session.summary.scorecards_submitted}/{session.summary.scorecards_expected}
                      <div className="text-xs text-muted-foreground">
                        Pending: {session.summary.scorecards_pending}
                      </div>
                    </td>
                    <td className="px-3 py-2">
                      <Link
                        href={businessDevelopment.pitchSessions.show(session.id).url}
                        className="rounded-md border border-orange-500 px-3 py-1.5 text-xs text-orange-600 hover:bg-orange-500 hover:text-white"
                      >
                        Open Session
                      </Link>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </section>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-muted-foreground">
            Showing {sessions.data.length} of {sessions.meta?.total ?? sessions.data.length}
          </p>
          <div className="flex flex-wrap gap-2">
            {paginationLinks.map((link, index) =>
              link.url ? (
                <Link
                  key={`${link.label}-${index}`}
                  href={link.url}
                  preserveState
                  preserveScroll
                  className={`rounded-md border px-3 py-1.5 text-sm ${
                    link.active
                      ? "border-red-600 bg-red-600 text-white"
                      : "border-orange-500 text-orange-600 hover:bg-orange-500 hover:text-white"
                  }`}
                  dangerouslySetInnerHTML={{ __html: link.label }}
                />
              ) : (
                <span
                  key={`${link.label}-${index}`}
                  className="rounded-md border border-muted px-3 py-1.5 text-sm text-muted-foreground"
                  dangerouslySetInnerHTML={{ __html: link.label }}
                />
              )
            )}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
