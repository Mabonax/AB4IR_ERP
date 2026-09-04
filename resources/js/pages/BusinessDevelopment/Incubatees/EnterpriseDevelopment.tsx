import { Head, Link, router, useForm } from "@inertiajs/react";
import { FormEvent, useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type Incubatee = {
  id: number;
  full_name: string;
  company_name: string;
  current_number_of_employees: number;
  technology_stage_of_development: string;
  incubated_date: string | null;
};

type DimensionScore = {
  code: string;
  name: string;
  score: number | null;
  assessed: number;
  total: number;
  weighting: number;
};

type CriterionResult = {
  id: number;
  criterion_name: string;
  criterion_code: string;
  dimension_name: string;
  dimension_code: string;
  maturity_status: string;
  maturity_score: number | null;
  evidence_required: boolean;
  required: boolean;
  assessor_observation: string | null;
  evidence_document_file_id: number | null;
  evidence_label: string | null;
  verified_at: string | null;
  expires_at: string | null;
  evidence_file: { id: number; title: string; original_name: string | null } | null;
};

type Diagnostic = {
  id: number;
  assessment_type: "baseline" | "periodic" | "exit";
  assessment_date: string | null;
  status: "draft" | "in_progress" | "completed" | "locked";
  overall_score: number | null;
  dimension_scores: DimensionScore[];
  outcome_baseline: Record<string, string | number>;
  notes: string | null;
  completed_at: string | null;
  criteria: CriterionResult[];
  gaps: DevelopmentGap[];
};

type DevelopmentGap = {
  id: number;
  dimension_name: string;
  criterion_name: string;
  severity: "low" | "medium" | "high";
  reason: string | null;
  status: string;
};

type Priority = "low" | "medium" | "high";

type DevelopmentNeed = {
  id: number;
  title: string;
  dimension_name: string | null;
  priority: Priority;
  reason: string | null;
  status: string;
};

type DevelopmentPlan = {
  id: number;
  title: string;
  status: string;
  start_date: string | null;
  end_date: string | null;
  notes: string | null;
  items?: Array<{
    id: number;
    objective: string;
    priority: string;
    target_date: string | null;
    status: string;
  }>;
};

type HistoryEvent = {
  id: number;
  title: string;
  details: string | null;
  actor: string | null;
  occurred_at: string | null;
};

type Workspace = {
  overview: {
    baseline_score: number | null;
    current_score: number | null;
    change_points: number | null;
    dimension_scores: DimensionScore[];
  };
  diagnostics: Diagnostic[];
  open_gaps: DevelopmentGap[];
  needs: DevelopmentNeed[];
  plans: DevelopmentPlan[];
  history: HistoryEvent[];
};

type ResponsibleUser = { id: number; name: string; email: string };

const maturityOptions = [
  "not_assessed",
  "not_started",
  "emerging",
  "developing",
  "established",
  "verified",
  "not_applicable",
];

function label(value: string | null | undefined): string {
  if (!value) return "-";
  return value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
}

function scoreText(score: number | null): string {
  return score === null ? "Not measured" : `${Math.round(score)}%`;
}

function badgeClass(status: string): string {
  if (["verified", "established", "completed", "active", "addressed"].includes(status)) return "border-emerald-200 bg-emerald-50 text-emerald-700";
  if (["developing", "planned", "in_progress", "medium"].includes(status)) return "border-amber-200 bg-amber-50 text-amber-700";
  if (["not_started", "emerging", "high"].includes(status)) return "border-red-200 bg-red-50 text-red-700";
  return "border-slate-200 bg-slate-50 text-slate-700";
}

function priorityValue(value: string): Priority {
  return value === "high" || value === "low" ? value : "medium";
}

export default function EnterpriseDevelopment({
  incubatee,
  workspace,
  responsibleUsers,
}: {
  incubatee: Incubatee;
  workspace: Workspace | { data: Workspace };
  responsibleUsers: ResponsibleUser[];
}) {
  const data = "data" in workspace ? workspace.data : workspace;
  const [activeTab, setActiveTab] = useState("overview");
  const activeDiagnostic = data.diagnostics.find((diagnostic) => diagnostic.status !== "completed" && diagnostic.status !== "locked") ?? data.diagnostics[0] ?? null;
  const dimensions = useMemo(() => {
    const grouped = new Map<string, CriterionResult[]>();
    (activeDiagnostic?.criteria ?? []).forEach((criterion) => {
      grouped.set(criterion.dimension_code, [...(grouped.get(criterion.dimension_code) ?? []), criterion]);
    });
    return Array.from(grouped.entries()).map(([code, criteria]) => ({ code, name: criteria[0]?.dimension_name ?? code, criteria }));
  }, [activeDiagnostic]);
  const [activeDimension, setActiveDimension] = useState<string>(dimensions[0]?.code ?? "");
  const selectedDimension = dimensions.find((dimension) => dimension.code === activeDimension) ?? dimensions[0] ?? null;

  const diagnosticForm = useForm({
    assessment_type: data.diagnostics.some((diagnostic) => diagnostic.assessment_type === "baseline") ? "periodic" : "baseline",
    assessment_date: new Date().toISOString().slice(0, 10),
    notes: "",
    baseline_employees: incubatee.current_number_of_employees,
    baseline_turnover: "",
    baseline_markets_accessed: "",
    baseline_funding_accessed: "",
    baseline_customers: "",
  });

  const criteriaForm = useForm({
    criteria: (activeDiagnostic?.criteria ?? []).map((criterion) => ({
      id: criterion.id,
      maturity_status: criterion.maturity_status,
      assessor_observation: criterion.assessor_observation ?? "",
      evidence_document_file_id: criterion.evidence_document_file_id ? String(criterion.evidence_document_file_id) : "",
      evidence_label: criterion.evidence_label ?? "",
      verified_at: criterion.verified_at ?? "",
      expires_at: criterion.expires_at ?? "",
    })),
  });

  const planForm = useForm({
    title: `${incubatee.company_name} Development Plan`,
    baseline_diagnostic_id: data.diagnostics.find((diagnostic) => diagnostic.assessment_type === "baseline")?.id ?? "",
    start_date: new Date().toISOString().slice(0, 10),
    end_date: "",
    status: "active",
    notes: "",
    items: data.needs.slice(0, 5).map((need) => ({
      development_need_id: need.id,
      objective: need.title,
      priority: need.priority,
      target_date: "",
      responsible_user_id: "",
      status: "open",
      notes: need.reason ?? "",
    })),
  });

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Incubatees", href: "/business-development/incubatees" },
    { title: incubatee.company_name, href: `/business-development/incubatees/${incubatee.id}` },
    { title: "Enterprise Development", href: `/business-development/incubatees/${incubatee.id}/enterprise-development` },
  ];

  function submitDiagnostic(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    diagnosticForm.post(`/business-development/incubatees/${incubatee.id}/enterprise-development/diagnostics`, { preserveScroll: true });
  }

  function updateCriterion(id: number, field: string, value: string) {
    criteriaForm.setData(
      "criteria",
      criteriaForm.data.criteria.map((criterion) => (criterion.id === id ? { ...criterion, [field]: value } : criterion)),
    );
  }

  function saveCriteria(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!activeDiagnostic) return;
    criteriaForm.post(`/business-development/enterprise-development/diagnostics/${activeDiagnostic.id}/criteria`, { preserveScroll: true });
  }

  function completeDiagnostic() {
    if (!activeDiagnostic) return;
    router.post(`/business-development/enterprise-development/diagnostics/${activeDiagnostic.id}/complete`, {}, { preserveScroll: true });
  }

  function createNeed(gap: DevelopmentGap) {
    router.post(
      `/business-development/enterprise-development/gaps/${gap.id}/needs`,
      { title: gap.criterion_name, priority: gap.severity, reason: gap.reason },
      { preserveScroll: true },
    );
  }

  function submitPlan(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    planForm.post(`/business-development/incubatees/${incubatee.id}/enterprise-development/plans`, { preserveScroll: true });
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Enterprise Development: ${incubatee.company_name}`} />
      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p className="text-sm text-muted-foreground">Enterprise Development Journey</p>
            <h1 className="text-2xl font-semibold">{incubatee.company_name}</h1>
            <p className="text-sm text-muted-foreground">{incubatee.full_name}</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <Link href={`/business-development/incubatees/${incubatee.id}`} className="rounded-md border px-3 py-2 text-sm hover:bg-muted">Profile</Link>
          </div>
        </div>

        <section className="grid gap-3 md:grid-cols-4">
          <div className="rounded-lg border bg-card p-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Baseline</div>
            <div className="mt-2 text-2xl font-semibold">{scoreText(data.overview.baseline_score)}</div>
          </div>
          <div className="rounded-lg border bg-card p-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Current</div>
            <div className="mt-2 text-2xl font-semibold">{scoreText(data.overview.current_score)}</div>
          </div>
          <div className="rounded-lg border bg-card p-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Change</div>
            <div className="mt-2 text-2xl font-semibold">{data.overview.change_points === null ? "Not yet measurable" : `${data.overview.change_points > 0 ? "+" : ""}${data.overview.change_points} pts`}</div>
          </div>
          <div className="rounded-lg border bg-card p-4">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Open Needs</div>
            <div className="mt-2 text-2xl font-semibold">{data.needs.filter((need) => need.status !== "addressed" && need.status !== "cancelled").length}</div>
          </div>
        </section>

        <div className="flex flex-wrap gap-2 border-b">
          {["overview", "diagnostic", "compliance", "development_plan", "needs", "history"].map((tab) => (
            <button key={tab} type="button" onClick={() => setActiveTab(tab)} className={`border-b-2 px-3 py-2 text-sm ${activeTab === tab ? "border-orange-500 text-orange-600" : "border-transparent text-muted-foreground"}`}>
              {label(tab)}
            </button>
          ))}
        </div>

        {activeTab === "overview" && (
          <section className="rounded-lg border bg-card p-4">
            <h2 className="text-base font-semibold">Development Position</h2>
            <div className="mt-4 grid gap-3 md:grid-cols-2">
              {data.overview.dimension_scores.length === 0 ? (
                <div className="rounded-lg border border-dashed p-5 text-sm text-muted-foreground md:col-span-2">No completed diagnostic score is available yet.</div>
              ) : (
                data.overview.dimension_scores.map((dimension) => (
                  <div key={dimension.code}>
                    <div className="mb-1 flex items-center justify-between text-sm">
                      <span className="font-medium">{dimension.name}</span>
                      <span>{scoreText(dimension.score)}</span>
                    </div>
                    <div className="h-2 rounded-full bg-muted">
                      <div className="h-2 rounded-full bg-orange-500" style={{ width: `${dimension.score ?? 0}%` }} />
                    </div>
                  </div>
                ))
              )}
            </div>
          </section>
        )}

        {activeTab === "diagnostic" && (
          <section className="grid gap-4 xl:grid-cols-[240px_1fr_320px]">
            <div className="rounded-lg border bg-card p-4">
              <h2 className="text-base font-semibold">Diagnostics</h2>
              <form onSubmit={submitDiagnostic} className="mt-4 space-y-3">
                <select className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={diagnosticForm.data.assessment_type} onChange={(event) => diagnosticForm.setData("assessment_type", event.target.value)}>
                  <option value="baseline">Baseline</option>
                  <option value="periodic">Periodic</option>
                  <option value="exit">Exit</option>
                </select>
                <input className="w-full rounded-md border bg-background px-3 py-2 text-sm" type="date" value={diagnosticForm.data.assessment_date} onChange={(event) => diagnosticForm.setData("assessment_date", event.target.value)} />
                <input className="w-full rounded-md border bg-background px-3 py-2 text-sm" type="number" min="0" value={diagnosticForm.data.baseline_employees} onChange={(event) => diagnosticForm.setData("baseline_employees", Number(event.target.value))} placeholder="Employees baseline" />
                <input className="w-full rounded-md border bg-background px-3 py-2 text-sm" type="number" min="0" step="0.01" value={diagnosticForm.data.baseline_turnover} onChange={(event) => diagnosticForm.setData("baseline_turnover", event.target.value)} placeholder="Turnover baseline" />
                <button type="submit" disabled={diagnosticForm.processing} className="w-full rounded-md bg-orange-500 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">Create Diagnostic</button>
              </form>
              <div className="mt-4 space-y-2">
                {data.diagnostics.map((diagnostic) => (
                  <div key={diagnostic.id} className="rounded-md border p-2 text-sm">
                    <div className="font-medium">{label(diagnostic.assessment_type)}</div>
                    <div className="text-xs text-muted-foreground">{diagnostic.assessment_date} - {label(diagnostic.status)}</div>
                  </div>
                ))}
              </div>
            </div>

            <form onSubmit={saveCriteria} className="rounded-lg border bg-card p-4">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-base font-semibold">{activeDiagnostic ? `${label(activeDiagnostic.assessment_type)} Diagnostic` : "No Diagnostic"}</h2>
                <span className="text-sm text-muted-foreground">
                  {(activeDiagnostic?.criteria ?? []).filter((criterion) => criterion.maturity_status !== "not_assessed").length} / {activeDiagnostic?.criteria.length ?? 0} assessed
                </span>
              </div>
              <div className="mt-4 flex flex-wrap gap-2">
                {dimensions.map((dimension) => (
                  <button key={dimension.code} type="button" onClick={() => setActiveDimension(dimension.code)} className={`rounded-md border px-3 py-1.5 text-sm ${selectedDimension?.code === dimension.code ? "border-orange-500 bg-orange-50" : "hover:bg-muted"}`}>
                    {dimension.name}
                  </button>
                ))}
              </div>
              <div className="mt-4 space-y-3">
                {(selectedDimension?.criteria ?? []).map((criterion) => {
                  const formRow = criteriaForm.data.criteria.find((item) => item.id === criterion.id);
                  return (
                    <div key={criterion.id} className="rounded-lg border p-3">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-medium">{criterion.criterion_name}</div>
                          <div className="text-xs text-muted-foreground">{criterion.evidence_required ? "Evidence required" : "Observation accepted"}{criterion.required ? " - Required" : ""}</div>
                        </div>
                        <span className={`rounded-full border px-2.5 py-1 text-xs ${badgeClass(formRow?.maturity_status ?? criterion.maturity_status)}`}>{label(formRow?.maturity_status ?? criterion.maturity_status)}</span>
                      </div>
                      <div className="mt-3 grid gap-3 md:grid-cols-3">
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={formRow?.maturity_status ?? "not_assessed"} onChange={(event) => updateCriterion(criterion.id, "maturity_status", event.target.value)}>
                          {maturityOptions.map((option) => <option key={option} value={option}>{label(option)}</option>)}
                        </select>
                        <input className="rounded-md border bg-background px-3 py-2 text-sm" value={formRow?.evidence_label ?? ""} onChange={(event) => updateCriterion(criterion.id, "evidence_label", event.target.value)} placeholder="Evidence label or file note" />
                        <input className="rounded-md border bg-background px-3 py-2 text-sm" type="date" value={formRow?.expires_at ?? ""} onChange={(event) => updateCriterion(criterion.id, "expires_at", event.target.value)} />
                        <textarea className="min-h-20 rounded-md border bg-background px-3 py-2 text-sm md:col-span-3" value={formRow?.assessor_observation ?? ""} onChange={(event) => updateCriterion(criterion.id, "assessor_observation", event.target.value)} placeholder="Assessor observation" />
                      </div>
                    </div>
                  );
                })}
              </div>
              {activeDiagnostic && activeDiagnostic.status !== "completed" && activeDiagnostic.status !== "locked" ? (
                <div className="mt-4 flex flex-wrap gap-2">
                  <button type="submit" disabled={criteriaForm.processing} className="rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Save Draft</button>
                  <button type="button" onClick={completeDiagnostic} className="rounded-md border px-4 py-2 text-sm hover:bg-muted">Complete Assessment</button>
                </div>
              ) : null}
            </form>

            <div className="rounded-lg border bg-card p-4">
              <h2 className="text-base font-semibold">Assessment Summary</h2>
              <div className="mt-4 space-y-3">
                {(activeDiagnostic?.dimension_scores ?? []).map((dimension) => (
                  <div key={dimension.code} className="text-sm">
                    <div className="flex justify-between gap-3"><span>{dimension.name}</span><span>{scoreText(dimension.score)}</span></div>
                    <div className="text-xs text-muted-foreground">{dimension.assessed} of {dimension.total} assessed</div>
                  </div>
                ))}
              </div>
            </div>
          </section>
        )}

        {activeTab === "compliance" && (
          <section className="overflow-hidden rounded-lg border bg-card">
            <div className="border-b p-4">
              <h2 className="text-base font-semibold">Compliance Register</h2>
            </div>
            <table className="min-w-full text-sm">
              <thead className="bg-muted/50 text-xs uppercase text-muted-foreground">
                <tr>
                  <th className="px-3 py-2 text-left">Requirement</th>
                  <th className="px-3 py-2 text-left">Status</th>
                  <th className="px-3 py-2 text-left">Evidence</th>
                  <th className="px-3 py-2 text-left">Verified</th>
                  <th className="px-3 py-2 text-left">Expiry</th>
                </tr>
              </thead>
              <tbody>
                {(data.diagnostics[0]?.criteria ?? []).filter((criterion) => criterion.dimension_code === "compliance_governance").map((criterion) => (
                  <tr key={criterion.id} className="border-t">
                    <td className="px-3 py-2 font-medium">{criterion.criterion_name}</td>
                    <td className="px-3 py-2"><span className={`rounded-full border px-2.5 py-1 text-xs ${badgeClass(criterion.maturity_status)}`}>{label(criterion.maturity_status)}</span></td>
                    <td className="px-3 py-2">{criterion.evidence_file?.title ?? criterion.evidence_label ?? "-"}</td>
                    <td className="px-3 py-2">{criterion.verified_at ?? "-"}</td>
                    <td className="px-3 py-2">{criterion.expires_at ?? "-"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </section>
        )}

        {activeTab === "needs" && (
          <section className="grid gap-4 lg:grid-cols-2">
            <div className="rounded-lg border bg-card p-4">
              <h2 className="text-base font-semibold">Development Gaps</h2>
              <div className="mt-4 space-y-3">
                {data.open_gaps.map((gap) => (
                  <div key={gap.id} className="rounded-lg border p-3 text-sm">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <div className="font-medium">{gap.criterion_name}</div>
                        <div className="text-xs text-muted-foreground">{gap.dimension_name}</div>
                      </div>
                      <button type="button" onClick={() => createNeed(gap)} className="rounded-md border px-2.5 py-1 text-xs hover:bg-muted">Create Need</button>
                    </div>
                    <p className="mt-2 text-muted-foreground">{gap.reason ?? "Below established maturity."}</p>
                  </div>
                ))}
              </div>
            </div>
            <div className="rounded-lg border bg-card p-4">
              <h2 className="text-base font-semibold">Development Needs</h2>
              <div className="mt-4 space-y-3">
                {data.needs.map((need) => (
                  <div key={need.id} className="rounded-lg border p-3 text-sm">
                    <div className="flex justify-between gap-3">
                      <span className="font-medium">{need.title}</span>
                      <span className={`rounded-full border px-2.5 py-1 text-xs ${badgeClass(need.priority)}`}>{label(need.priority)}</span>
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">{need.dimension_name ?? "-"} - {label(need.status)}</div>
                  </div>
                ))}
              </div>
            </div>
          </section>
        )}

        {activeTab === "development_plan" && (
          <section className="grid gap-4 xl:grid-cols-[1fr_.9fr]">
            <form onSubmit={submitPlan} className="rounded-lg border bg-card p-4">
              <h2 className="text-base font-semibold">Create Development Plan</h2>
              <div className="mt-4 grid gap-3 md:grid-cols-2">
                <input className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2" value={planForm.data.title} onChange={(event) => planForm.setData("title", event.target.value)} required />
                <input className="rounded-md border bg-background px-3 py-2 text-sm" type="date" value={planForm.data.start_date} onChange={(event) => planForm.setData("start_date", event.target.value)} />
                <input className="rounded-md border bg-background px-3 py-2 text-sm" type="date" value={planForm.data.end_date} onChange={(event) => planForm.setData("end_date", event.target.value)} />
              </div>
              <div className="mt-4 space-y-3">
                {planForm.data.items.length === 0 ? (
                  <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">Create development needs before building a plan.</div>
                ) : (
                  planForm.data.items.map((item, index) => (
                    <div key={`${item.development_need_id}-${index}`} className="rounded-lg border p-3">
                      <input className="w-full rounded-md border bg-background px-3 py-2 text-sm" value={item.objective} onChange={(event) => planForm.setData("items", planForm.data.items.map((row, rowIndex) => rowIndex === index ? { ...row, objective: event.target.value } : row))} />
                      <div className="mt-3 grid gap-3 md:grid-cols-3">
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={item.priority} onChange={(event) => planForm.setData("items", planForm.data.items.map((row, rowIndex) => rowIndex === index ? { ...row, priority: priorityValue(event.target.value) } : row))}>
                          <option value="high">High</option>
                          <option value="medium">Medium</option>
                          <option value="low">Low</option>
                        </select>
                        <input className="rounded-md border bg-background px-3 py-2 text-sm" type="date" value={item.target_date} onChange={(event) => planForm.setData("items", planForm.data.items.map((row, rowIndex) => rowIndex === index ? { ...row, target_date: event.target.value } : row))} />
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={item.responsible_user_id} onChange={(event) => planForm.setData("items", planForm.data.items.map((row, rowIndex) => rowIndex === index ? { ...row, responsible_user_id: event.target.value } : row))}>
                          <option value="">Responsible BDS person</option>
                          {responsibleUsers.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}
                        </select>
                      </div>
                    </div>
                  ))
                )}
              </div>
              <button type="submit" disabled={planForm.processing || planForm.data.items.length === 0} className="mt-4 rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Create Plan</button>
            </form>
            <div className="rounded-lg border bg-card p-4">
              <h2 className="text-base font-semibold">Plans</h2>
              <div className="mt-4 space-y-3">
                {data.plans.map((plan) => (
                  <div key={plan.id} className="rounded-lg border p-3 text-sm">
                    <div className="font-medium">{plan.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">{label(plan.status)} - {plan.start_date ?? "-"} to {plan.end_date ?? "-"}</div>
                    <div className="mt-2 text-xs text-muted-foreground">{plan.items?.length ?? 0} items</div>
                  </div>
                ))}
              </div>
            </div>
          </section>
        )}

        {activeTab === "history" && (
          <section className="rounded-lg border bg-card p-4">
            <h2 className="text-base font-semibold">Development History</h2>
            <div className="mt-4 space-y-3">
              {data.history.map((event) => (
                <div key={event.id} className="rounded-lg border p-3 text-sm">
                  <div className="flex flex-wrap justify-between gap-3">
                    <span className="font-medium">{event.title}</span>
                    <span className="text-xs text-muted-foreground">{event.occurred_at ?? "-"}</span>
                  </div>
                  <div className="mt-1 text-xs text-muted-foreground">{event.actor ?? "-"} - {event.details ?? ""}</div>
                </div>
              ))}
            </div>
          </section>
        )}
      </div>
    </AppLayout>
  );
}
