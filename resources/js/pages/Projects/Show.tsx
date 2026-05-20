import { useState } from "react";
import { Head, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Project View", href: "#" },
];

const readinessTone = (ready: boolean) =>
  ready
    ? "border-emerald-200 bg-emerald-50 text-emerald-700"
    : "border-amber-200 bg-amber-50 text-amber-700";

export default function ProjectShow({
  project,
  milestones,
  progress,
  locations,
  closure,
  closureEvidence,
  history,
  reports,
}: {
  project: any;
  milestones: any[];
  progress: any;
  locations: any[];
  closure: any | null;
  closureEvidence: any[];
  history: any[];
  reports: any[];
}) {
  const projectData = project?.data ?? project;
  const statusSummary = projectData.status_summary;
  const summary = progress?.summary ?? {};
  const [closureForm, setClosureForm] = useState({
    closure_date: projectData.end_date ?? new Date().toISOString().slice(0, 10),
    signoff_notes: "",
    final_report_summary: "",
    report_title: "",
    key_findings: "",
    recommendations: "",
  });
  const [reportForm, setReportForm] = useState({
    report_type: projectData.status === "completed" ? "final" : "progress",
    report_date: new Date().toISOString().slice(0, 10),
    title: "",
    executive_summary: "",
    key_findings: "",
    recommendations: "",
  });
  const [evidenceForm, setEvidenceForm] = useState<{
    title: string;
    notes: string;
    file: File | null;
  }>({
    title: "",
    notes: "",
    file: null,
  });

  const handleSyncMilestones = (e: React.FormEvent) => {
    e.preventDefault();

    router.post(`/projects/${projectData.id}/milestones/sync`, {});
  };

  const handleConcludeProject = (e: React.FormEvent) => {
    e.preventDefault();

    router.post(`/projects/${projectData.id}/conclude`, closureForm);
  };

  const handleCreateReport = (e: React.FormEvent) => {
    e.preventDefault();

    router.post(`/projects/${projectData.id}/reports`, reportForm);
  };

  const handleUploadEvidence = (e: React.FormEvent) => {
    e.preventDefault();

    router.post(
      `/projects/${projectData.id}/closure-evidence`,
      {
        title: evidenceForm.title,
        notes: evidenceForm.notes,
        file: evidenceForm.file,
      },
      {
        forceFormData: true,
      }
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project View" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">{projectData.name}</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Status</CardTitle>
              <CardDescription>Current</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {projectData.status_label ?? projectData.status ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Start Date</CardTitle>
              <CardDescription>Project start</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {projectData.start_date ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Delivery sites</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.total_locations ?? locations.length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Milestones</CardTitle>
              <CardDescription>Delivery units</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.total_milestones ?? milestones.length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Beneficiaries</CardTitle>
              <CardDescription>In delivery</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.active_beneficiaries ?? 0}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Milestone Delivery</CardTitle>
              <CardDescription>Completed assessments</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.milestone_completion_rate ?? 0}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Beneficiary Completion</CardTitle>
              <CardDescription>Completed all milestones</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.beneficiary_completion_rate ?? 0}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Attendance Health</CardTitle>
              <CardDescription>Captured attendance</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.attendance_rate ?? 0}%
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Blocked Sites</CardTitle>
              <CardDescription>Need intervention</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {summary.blocked_locations ?? 0}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Project Manager</CardTitle>
              <CardDescription>Assigned lead</CardDescription>
            </CardHeader>
            <CardContent className="text-base font-semibold">
              {summary.project_manager_name ?? projectData.project_manager_name ?? "-"}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Commercial Structure</CardTitle>
              <CardDescription>Sponsor and implementation partners</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Sponsor
                </div>
                <div className="mt-1 font-medium text-slate-900">
                  {projectData.sponsor_name ?? "No sponsor assigned"}
                </div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Implementation partners
                </div>
                {projectData.partner_names?.length ? (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {projectData.partner_names.map((partnerName: string) => (
                      <span
                        key={partnerName}
                        className="rounded-full border border-slate-300 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700"
                      >
                        {partnerName}
                      </span>
                    ))}
                  </div>
                ) : (
                  <p className="mt-1 text-muted-foreground">No implementation partners assigned.</p>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Governance Metadata</CardTitle>
              <CardDescription>Funding and reporting obligations</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Contract Reference
                </div>
                <div className="mt-1 font-medium text-slate-900">
                  {projectData.contract_reference ?? "Not recorded"}
                </div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Funding Amount
                </div>
                <div className="mt-1 font-medium text-slate-900">
                  {projectData.funding_amount !== null && projectData.funding_amount !== undefined
                    ? Number(projectData.funding_amount).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                      })
                    : "Not recorded"}
                </div>
              </div>
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Reporting Cadence
                </div>
                <div className="mt-1 font-medium capitalize text-slate-900">
                  {projectData.reporting_cadence
                    ? String(projectData.reporting_cadence).replaceAll("_", " ")
                    : "Not recorded"}
                </div>
              </div>
              <div className="sm:col-span-2">
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Reporting Obligations
                </div>
                <div className="mt-1 whitespace-pre-wrap text-slate-700">
                  {projectData.reporting_obligations ?? "No reporting obligations recorded."}
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Transition Readiness</CardTitle>
              <CardDescription>Current workflow state and blockers</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Allowed transitions
                </div>
                {statusSummary?.allowed_transitions?.length ? (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {statusSummary.allowed_transitions.map((transition: any) => (
                      <span
                        key={transition.status}
                        className={`rounded-full border px-2.5 py-1 text-xs font-medium ${readinessTone(transition.ready)}`}
                      >
                        {transition.label}
                        {!transition.ready ? ` (${transition.blockers.length} blocker${transition.blockers.length === 1 ? "" : "s"})` : ""}
                      </span>
                    ))}
                  </div>
                ) : (
                  <p className="mt-2 text-sm text-muted-foreground">
                    No further transitions are allowed for this project.
                  </p>
                )}
              </div>

              <div className="grid gap-3">
                {(["active", "completed"] as const).map((statusKey) => {
                  const readiness = statusSummary?.readiness?.[statusKey];

                  if (!readiness) return null;

                  return (
                    <div
                      key={statusKey}
                      className={`rounded-lg border p-3 ${readinessTone(readiness.ready)}`}
                    >
                      <div className="text-xs font-semibold uppercase tracking-wide">
                        {statusKey === "active" ? "Activation readiness" : "Completion readiness"}
                      </div>
                      <div className="mt-1 text-sm font-medium">
                        {readiness.ready ? "Ready" : `${readiness.blockers.length} blocker${readiness.blockers.length === 1 ? "" : "s"}`}
                      </div>
                      {!readiness.ready && (
                        <ul className="mt-2 space-y-1 text-xs">
                          {readiness.blockers.map((blocker: string) => (
                            <li key={blocker}>{blocker}</li>
                          ))}
                        </ul>
                      )}
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Progress by site</CardDescription>
            </CardHeader>
            <CardContent>
              {locations.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No locations added yet.
                </p>
              ) : (
                <div className="space-y-4">
                  {locations.map((loc) => (
                    <div key={loc.id} className="rounded-md border p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-medium">{loc.location}</div>
                          <div className="text-xs text-muted-foreground">
                            Facilitator: {loc.facilitator_name ?? "-"}
                          </div>
                          <div className="text-xs text-muted-foreground">
                            Venue: {loc.training_venue_address ?? "-"}
                          </div>
                        </div>
                        <div className={`rounded-full border px-2.5 py-1 text-xs font-medium ${loc.is_blocked ? "border-amber-200 bg-amber-50 text-amber-700" : "border-emerald-200 bg-emerald-50 text-emerald-700"}`}>
                          {loc.is_blocked ? "Needs intervention" : "On track"}
                        </div>
                      </div>

                      <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-xs">
                        <div>
                          <div className="text-muted-foreground">Active beneficiaries</div>
                          <div className="font-semibold">{loc.active_beneficiaries ?? 0}</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Milestone delivery</div>
                          <div className="font-semibold">{loc.milestone_completion_rate ?? 0}%</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Beneficiary completion</div>
                          <div className="font-semibold">{loc.beneficiary_completion_rate ?? 0}%</div>
                        </div>
                        <div>
                          <div className="text-muted-foreground">Attendance health</div>
                          <div className="font-semibold">{loc.attendance_rate ?? 0}%</div>
                        </div>
                      </div>

                      {loc.blockers?.length ? (
                        <ul className="mt-3 space-y-1 text-xs text-amber-700">
                          {loc.blockers.map((blocker: string) => (
                            <li key={blocker}>{blocker}</li>
                          ))}
                        </ul>
                      ) : null}

                      <div className="mt-3 text-xs text-muted-foreground">
                        Completed assessments: {loc.completed_assessments ?? 0}/{loc.expected_assessments ?? 0}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Project Risks</CardTitle>
              <CardDescription>Current blockers and intervention points</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              {summary.blockers?.length ? (
                <ul className="space-y-2">
                  {summary.blockers.map((blocker: string) => (
                    <li key={blocker} className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800">
                      {blocker}
                    </li>
                  ))}
                </ul>
              ) : (
                <div className="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-800">
                  No project-level blockers are currently flagged.
                </div>
              )}

              <div className="rounded-md border p-3">
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Beneficiary movement
                </div>
                <div className="mt-2 grid gap-2 sm:grid-cols-3">
                  <div>
                    <div className="text-muted-foreground">Total</div>
                    <div className="font-semibold">{summary.total_beneficiaries ?? 0}</div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Completed</div>
                    <div className="font-semibold">{summary.completed_beneficiaries ?? 0}</div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Dropped</div>
                    <div className="font-semibold">{summary.dropped_beneficiaries ?? 0}</div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Closure Workflow</CardTitle>
              <CardDescription>Explicit conclusion and final sign-off</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              {closure ? (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                  <div className="font-semibold text-emerald-900">Project concluded</div>
                  <div className="mt-2 grid gap-2 sm:grid-cols-2">
                    <div>
                      <div className="text-xs uppercase tracking-wide text-emerald-700">Closure date</div>
                      <div>{closure.closure_date ?? "-"}</div>
                    </div>
                    <div>
                      <div className="text-xs uppercase tracking-wide text-emerald-700">Concluded by</div>
                      <div>{closure.concluded_by_name ?? "-"}</div>
                    </div>
                  </div>
                  {closure.signoff_notes ? (
                    <div className="mt-3">
                      <div className="text-xs uppercase tracking-wide text-emerald-700">Sign-off notes</div>
                      <p className="mt-1 whitespace-pre-wrap text-emerald-900">{closure.signoff_notes}</p>
                    </div>
                  ) : null}
                </div>
              ) : (
                <form onSubmit={handleConcludeProject} className="space-y-3">
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Closure date
                    </label>
                    <input
                      type="date"
                      value={closureForm.closure_date}
                      onChange={(e) => setClosureForm((current) => ({ ...current, closure_date: e.target.value }))}
                      className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Final summary
                    </label>
                    <textarea
                      value={closureForm.final_report_summary}
                      onChange={(e) => setClosureForm((current) => ({ ...current, final_report_summary: e.target.value }))}
                      className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                      placeholder="Summarize project outcomes, delivery highlights, and final decision context."
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Sign-off notes
                    </label>
                    <textarea
                      value={closureForm.signoff_notes}
                      onChange={(e) => setClosureForm((current) => ({ ...current, signoff_notes: e.target.value }))}
                      className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                      placeholder="Capture closure notes, final actions, or outstanding handover items."
                    />
                  </div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                      <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Final report title
                      </label>
                      <input
                        type="text"
                        value={closureForm.report_title}
                        onChange={(e) => setClosureForm((current) => ({ ...current, report_title: e.target.value }))}
                        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder={`${projectData.name} Final Report`}
                      />
                    </div>
                    <div>
                      <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Key findings
                      </label>
                      <input
                        type="text"
                        value={closureForm.key_findings}
                        onChange={(e) => setClosureForm((current) => ({ ...current, key_findings: e.target.value }))}
                        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Critical delivery findings"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Recommendations
                    </label>
                    <textarea
                      value={closureForm.recommendations}
                      onChange={(e) => setClosureForm((current) => ({ ...current, recommendations: e.target.value }))}
                      className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                      placeholder="Recommended follow-up, scale-up, or improvement actions."
                    />
                  </div>
                  <button
                    type="submit"
                    className="rounded-md bg-emerald-600 px-3 py-2 text-sm text-white hover:bg-emerald-700"
                  >
                    Conclude project
                  </button>
                </form>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Project Reports</CardTitle>
              <CardDescription>Generate progress and final reporting outputs</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <form onSubmit={handleCreateReport} className="space-y-3">
                <div className="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Report type
                    </label>
                    <select
                      value={reportForm.report_type}
                      onChange={(e) => setReportForm((current) => ({ ...current, report_type: e.target.value }))}
                      className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    >
                      <option value="progress">Progress</option>
                      <option value="final" disabled={projectData.status !== "completed"}>
                        Final
                      </option>
                    </select>
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Report date
                    </label>
                    <input
                      type="date"
                      value={reportForm.report_date}
                      onChange={(e) => setReportForm((current) => ({ ...current, report_date: e.target.value }))}
                      className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                </div>
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Title
                  </label>
                  <input
                    type="text"
                    value={reportForm.title}
                    onChange={(e) => setReportForm((current) => ({ ...current, title: e.target.value }))}
                    className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    placeholder={`${projectData.name} Progress Report`}
                  />
                </div>
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Executive summary
                  </label>
                  <textarea
                    value={reportForm.executive_summary}
                    onChange={(e) => setReportForm((current) => ({ ...current, executive_summary: e.target.value }))}
                    className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Summarize the current delivery state and decision context."
                  />
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Key findings
                    </label>
                    <textarea
                      value={reportForm.key_findings}
                      onChange={(e) => setReportForm((current) => ({ ...current, key_findings: e.target.value }))}
                      className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Recommendations
                    </label>
                    <textarea
                      value={reportForm.recommendations}
                      onChange={(e) => setReportForm((current) => ({ ...current, recommendations: e.target.value }))}
                      className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                </div>
                <button
                  type="submit"
                  className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                >
                  Generate report
                </button>
              </form>

              {reports.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No project reports have been generated yet.
                </p>
              ) : (
                <div className="space-y-3">
                  {reports.map((report) => (
                    <div key={report.id} className="rounded-lg border p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-medium text-slate-900">{report.title}</div>
                          <div className="text-xs uppercase tracking-wide text-muted-foreground">
                            {report.report_type} report | {report.report_date ?? "-"}
                          </div>
                          <div className="mt-1 text-xs text-muted-foreground">
                            Created by: {report.created_by_name ?? "-"}
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => window.location.assign(`/projects/${projectData.id}/reports/${report.id}/pdf`)}
                          className="rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        >
                          Download PDF
                        </button>
                      </div>
                      {report.executive_summary ? (
                        <p className="mt-3 text-sm text-slate-700">{report.executive_summary}</p>
                      ) : null}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Closure Evidence</CardTitle>
              <CardDescription>Supporting documents for project sign-off and audit</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <form onSubmit={handleUploadEvidence} className="space-y-3">
                <div className="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      Evidence title
                    </label>
                    <input
                      type="text"
                      value={evidenceForm.title}
                      onChange={(e) => setEvidenceForm((current) => ({ ...current, title: e.target.value }))}
                      className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                      placeholder="Final attendance export"
                    />
                  </div>
                  <div>
                    <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                      File
                    </label>
                    <input
                      type="file"
                      onChange={(e) => setEvidenceForm((current) => ({ ...current, file: e.target.files?.[0] ?? null }))}
                      className="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    />
                  </div>
                </div>
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Notes
                  </label>
                  <textarea
                    value={evidenceForm.notes}
                    onChange={(e) => setEvidenceForm((current) => ({ ...current, notes: e.target.value }))}
                    className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Explain what this document proves or supports."
                  />
                </div>
                <button
                  type="submit"
                  className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                  Upload evidence
                </button>
              </form>

              {closureEvidence.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No closure evidence has been uploaded yet.
                </p>
              ) : (
                <div className="space-y-3">
                  {closureEvidence.map((item) => (
                    <div key={item.id} className="rounded-lg border p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-medium text-slate-900">{item.title}</div>
                          <div className="text-xs text-muted-foreground">
                            {item.file_name} | {item.uploaded_by_name ?? "-"} | {item.created_at ?? "-"}
                          </div>
                          {item.notes ? (
                            <p className="mt-2 text-sm text-slate-700">{item.notes}</p>
                          ) : null}
                        </div>
                        <div className="flex flex-wrap gap-2">
                          <button
                            type="button"
                            onClick={() => window.location.assign(`/projects/${projectData.id}/closure-evidence/${item.id}`)}
                            className="rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                          >
                            Download
                          </button>
                          <button
                            type="button"
                            onClick={() => router.delete(`/projects/${projectData.id}/closure-evidence/${item.id}`)}
                            className="rounded-md border border-rose-200 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50"
                          >
                            Remove
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Milestone Register</CardTitle>
              <CardDescription>Attached to project</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSyncMilestones} className="mb-4">
                <button
                  type="submit"
                  className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                >
                  Attach program milestones
                </button>
              </form>

              {milestones.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No milestones attached yet.
                </p>
              ) : (
                <ul className="space-y-2 text-sm">
                  {milestones.map((m) => (
                    <li key={m.id} className="flex items-center justify-between">
                      <span>{m.title}</span>
                      <span className="text-muted-foreground">
                        Max: {m.max_score ?? "-"}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Project History</CardTitle>
              <CardDescription>Audit trail for governance and delivery actions</CardDescription>
            </CardHeader>
            <CardContent>
              {history.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No project history has been recorded yet.
                </p>
              ) : (
                <div className="space-y-3">
                  {history.map((item) => (
                    <div key={item.id} className="rounded-lg border p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-medium text-slate-900">{item.summary}</div>
                          <div className="text-xs uppercase tracking-wide text-muted-foreground">
                            {String(item.action).replaceAll("_", " ")}
                          </div>
                        </div>
                        <div className="text-right text-xs text-muted-foreground">
                          <div>{item.actor_name ?? "System"}</div>
                          <div>{item.created_at ?? "-"}</div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
