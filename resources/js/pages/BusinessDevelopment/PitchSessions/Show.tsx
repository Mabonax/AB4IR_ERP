import { Head, Link, router } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import businessDevelopment from "@/routes/business-development";
import { type BreadcrumbItem } from "@/types";

type SessionDetail = {
  id: number;
  title: string;
  scheduled_for: string | null;
  venue: string | null;
  notes: string | null;
  status: string;
  status_label: string;
  started_at: string | null;
  consolidated_at: string | null;
  approved_at: string | null;
  summary: {
    panelists_total: number;
    prospects_total: number;
    scorecards_expected: number;
    scorecards_submitted: number;
    scorecards_pending: number;
    consolidated_prospects: number;
    decided_prospects: number;
  };
  panelists: Array<{
    id: number;
    user_id: number;
    name: string | null;
    email: string | null;
    panel_role: string;
    is_chair: boolean;
  }>;
  prospects: Array<{
    id: number;
    bds_application_id: number;
    sequence_number: number | null;
    company_name: string | null;
    applicant_name: string | null;
    consolidated_total_score: number;
    submitted_assessments_count: number;
    required_panel_submissions: number;
    missing_panel_submissions: number;
    manager_decision: "incubated" | "rejected" | null;
    has_current_user_submitted: boolean;
    submitted_panelists: Array<{
      assessment_id: number;
      judge_name: string | null;
      total_score: number;
      submitted_at: string | null;
    }>;
    workflow: {
      can_consolidate: boolean;
      can_approve: boolean;
      needs_more_panel_scores: boolean;
    };
  }>;
};

export default function PitchSessionShow({
  session,
  can,
}: {
  session: SessionDetail | { data: SessionDetail };
  can: { start: boolean; consolidate: boolean; approve: boolean };
}) {
  const sessionData: SessionDetail =
    session && typeof session === "object" && "data" in session ? session.data : (session as SessionDetail);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Pitch Sessions", href: businessDevelopment.pitchSessions.index().url },
    { title: sessionData.title, href: businessDevelopment.pitchSessions.show(sessionData.id).url },
  ];

  const submitDecision = (prospectId: number, decision: "incubated" | "rejected") => {
    router.post(
      businessDevelopment.pitchSessions.prospects.approve([sessionData.id, prospectId]).url,
      {
        manager_decision: decision,
        manager_notes: "",
      },
      { preserveScroll: true }
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Pitch Session: ${sessionData.title}`} />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{sessionData.title}</h1>
            <p className="text-sm text-muted-foreground">
              {sessionData.scheduled_for ?? "Unscheduled"} | {sessionData.venue ?? "Venue pending"} | {sessionData.status_label}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <Link
              href={businessDevelopment.pitchSessions.index().url}
              className="rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Back to Sessions
            </Link>
          </div>
        </div>

        <section className="grid gap-3 md:grid-cols-4">
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Panel</div>
            <div className="mt-2 text-2xl font-semibold">{sessionData.summary.panelists_total}</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Prospects</div>
            <div className="mt-2 text-2xl font-semibold">{sessionData.summary.prospects_total}</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Scorecards</div>
            <div className="mt-2 text-2xl font-semibold">
              {sessionData.summary.scorecards_submitted}/{sessionData.summary.scorecards_expected}
            </div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Final Decisions</div>
            <div className="mt-2 text-2xl font-semibold">
              {sessionData.summary.decided_prospects}/{sessionData.summary.prospects_total}
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-base font-semibold">Session Controls</h2>
              <p className="text-sm text-muted-foreground">
                Start the session, track panel submissions, then consolidate and approve.
              </p>
            </div>
            {can.start && sessionData.status === "scheduled" ? (
              <button
                type="button"
                onClick={() =>
                  router.post(businessDevelopment.pitchSessions.start(sessionData.id).url, {}, { preserveScroll: true })
                }
                className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
              >
                Start Session
              </button>
            ) : null}
          </div>
          <div className="mt-4 grid gap-3 md:grid-cols-3">
            <div className="rounded-lg border p-3 text-sm">
              <div className="text-xs uppercase tracking-wide text-muted-foreground">Started</div>
              <div className="mt-1 font-medium">{sessionData.started_at ?? "-"}</div>
            </div>
            <div className="rounded-lg border p-3 text-sm">
              <div className="text-xs uppercase tracking-wide text-muted-foreground">Consolidated</div>
              <div className="mt-1 font-medium">{sessionData.consolidated_at ?? "-"}</div>
            </div>
            <div className="rounded-lg border p-3 text-sm">
              <div className="text-xs uppercase tracking-wide text-muted-foreground">Approved</div>
              <div className="mt-1 font-medium">{sessionData.approved_at ?? "-"}</div>
            </div>
          </div>
          <div className="mt-3 rounded-lg border p-3 text-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Notes</div>
            <div className="mt-1">{sessionData.notes ?? "No session notes."}</div>
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Panel Members</h2>
          <div className="mt-3 grid gap-3 md:grid-cols-2">
            {sessionData.panelists.map((panelist) => (
              <div key={panelist.id} className="rounded-lg border p-3">
                <div className="font-medium">{panelist.name ?? "Unknown user"}</div>
                <div className="text-sm text-muted-foreground">{panelist.email ?? "No email"}</div>
                <div className="mt-2 text-xs uppercase tracking-wide text-muted-foreground">
                  {panelist.panel_role}
                  {panelist.is_chair ? " | Chair" : ""}
                </div>
              </div>
            ))}
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Prospect Panel Tracker</h2>
          <div className="mt-4 space-y-4">
            {sessionData.prospects.map((prospect) => (
              <div key={prospect.id} className="rounded-xl border p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">
                      Prospect {prospect.sequence_number ?? "-"}
                    </div>
                    <div className="text-base font-semibold">{prospect.company_name ?? "Unknown company"}</div>
                    <div className="text-sm text-muted-foreground">{prospect.applicant_name ?? "-"}</div>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <Link
                      href={`/business-development/adjudications/create?smme_id=${prospect.bds_application_id}&pitch_session_id=${sessionData.id}`}
                      className="rounded-md border border-orange-500 px-3 py-1.5 text-xs text-orange-600 hover:bg-orange-500 hover:text-white"
                    >
                      {prospect.has_current_user_submitted ? "Open Scorecard Flow" : "Start Scorecard"}
                    </Link>
                    {can.consolidate && prospect.workflow.can_consolidate ? (
                      <button
                        type="button"
                        onClick={() =>
                          router.post(
                            businessDevelopment.pitchSessions.prospects.consolidate([sessionData.id, prospect.id]).url,
                            {},
                            { preserveScroll: true }
                          )
                        }
                        className="rounded-md border border-red-600 px-3 py-1.5 text-xs text-red-600 hover:bg-red-600 hover:text-white"
                      >
                        Consolidate
                      </button>
                    ) : null}
                  </div>
                </div>

                <div className="mt-3 grid gap-3 md:grid-cols-4">
                  <div className="rounded-lg border p-3 text-sm">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Submitted</div>
                    <div className="mt-1 font-medium">
                      {prospect.submitted_assessments_count}/{prospect.required_panel_submissions}
                    </div>
                  </div>
                  <div className="rounded-lg border p-3 text-sm">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Missing</div>
                    <div className="mt-1 font-medium">{prospect.missing_panel_submissions}</div>
                  </div>
                  <div className="rounded-lg border p-3 text-sm">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Consolidated Score</div>
                    <div className="mt-1 font-medium">{prospect.consolidated_total_score}</div>
                  </div>
                  <div className="rounded-lg border p-3 text-sm">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Decision</div>
                    <div className="mt-1 font-medium">{prospect.manager_decision ?? "Pending"}</div>
                  </div>
                </div>

                <div className="mt-3 rounded-lg border bg-muted/30 p-3">
                  <div className="text-xs uppercase tracking-wide text-muted-foreground">Submitted Scorecards</div>
                  {prospect.submitted_panelists.length === 0 ? (
                    <p className="mt-2 text-sm text-muted-foreground">No submitted scorecards yet.</p>
                  ) : (
                    <div className="mt-2 space-y-2">
                      {prospect.submitted_panelists.map((panelScore) => (
                        <div key={panelScore.assessment_id} className="flex flex-wrap items-center justify-between gap-2 text-sm">
                          <span>{panelScore.judge_name ?? "Unknown judge"}</span>
                          <span className="text-muted-foreground">
                            Score: {panelScore.total_score} | Submitted: {panelScore.submitted_at ?? "-"}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {can.approve && prospect.workflow.can_approve ? (
                  <div className="mt-3 flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      onClick={() => submitDecision(prospect.id, "incubated")}
                      className="rounded-md bg-green-600 px-3 py-1.5 text-xs text-white hover:bg-green-700"
                    >
                      Approve For Incubation
                    </button>
                    <button
                      type="button"
                      onClick={() => submitDecision(prospect.id, "rejected")}
                      className="rounded-md bg-slate-700 px-3 py-1.5 text-xs text-white hover:bg-slate-800"
                    >
                      Reject Prospect
                    </button>
                  </div>
                ) : null}
              </div>
            ))}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
