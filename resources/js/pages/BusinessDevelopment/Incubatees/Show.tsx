import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type KpiReview = {
  id: number;
  review_date: string | null;
  actual_value: string | number | null;
  progress_percent: number;
  status: string;
  evidence_notes: string | null;
  mentor_comments: string | null;
  reviewed_by: { id: number | null; name: string | null };
  created_at: string | null;
};

type IncubateeKpi = {
  id: number;
  status: string;
  target_value: string | number | null;
  baseline_value: string | number | null;
  start_date: string | null;
  due_date: string | null;
  definition: {
    id: number | null;
    name: string | null;
    category: string | null;
    measurement_type: string | null;
    unit: string | null;
    weight: number | null;
    description: string | null;
  };
  progress: {
    latest_progress_percent: number;
    latest_actual_value: string | number | null;
    latest_status: string | null;
    risk_state: "healthy" | "warning" | "critical" | "unknown";
  };
  latest_review: KpiReview | null;
  reviews: KpiReview[];
};

type Incubatee = {
  id: number;
  full_name: string;
  id_number: string;
  gender: string;
  mobile_number: string;
  email: string;
  company_name: string;
  company_registration_number: string;
  position_in_company: string | null;
  majority_shareholding: string | null;
  current_number_of_employees: number;
  physical_address: string | null;
  website_address: string | null;
  years_in_operation: number;
  province_name: string | null;
  has_business_plan: boolean;
  relevant_skill_set: string;
  technology_product_service: string;
  technology_stage_of_development: string;
  status: "active" | "inactive";
  incubated_date: string | null;
  intake?: {
    type: string | null;
    source: string | null;
    justification: string | null;
    approved_at: string | null;
    approved_by: { id: number | null; name: string | null };
  };
  kpi_summary?: {
    total: number;
    active: number;
    completed: number;
    warnings: number;
    critical: number;
    overdue: number;
    health: "healthy" | "warning" | "critical" | "unassigned";
  };
  kpis?: IncubateeKpi[];
  created_at: string | null;
  updated_at: string | null;
};

const healthClasses: Record<string, string> = {
  healthy: "border-emerald-200 bg-emerald-50 text-emerald-700",
  warning: "border-amber-200 bg-amber-50 text-amber-700",
  critical: "border-red-200 bg-red-50 text-red-700",
  unassigned: "border-slate-200 bg-slate-50 text-slate-600",
  unknown: "border-slate-200 bg-slate-50 text-slate-600",
};

function formatLabel(value: string | null | undefined): string {
  if (!value) return "-";
  return value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
}

function riskClass(value: string | null | undefined): string {
  return healthClasses[value ?? "unknown"] ?? healthClasses.unknown;
}

export default function BdsIncubateeShow({
  incubatee,
}: {
  incubatee: Incubatee | { data: Incubatee };
}) {
  const incubateeData: Incubatee =
    incubatee && typeof incubatee === "object" && "data" in incubatee
      ? incubatee.data
      : (incubatee as Incubatee);

  const kpiSummary = incubateeData.kpi_summary ?? {
    total: 0,
    active: 0,
    completed: 0,
    warnings: 0,
    critical: 0,
    overdue: 0,
    health: "unassigned" as const,
  };
  const kpis = incubateeData.kpis ?? [];
  const latestReviews = kpis
    .map((kpi) => ({ kpi, review: kpi.latest_review }))
    .filter((item): item is { kpi: IncubateeKpi; review: KpiReview } => Boolean(item.review))
    .slice(0, 5);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Incubatees", href: "/business-development/incubatees" },
    { title: incubateeData.full_name, href: `/business-development/incubatees/${incubateeData.id}` },
  ];

  const detailRows: Array<{ label: string; value: string | number | null | undefined }> = [
    { label: "Full Name", value: incubateeData.full_name },
    { label: "ID Number", value: incubateeData.id_number },
    { label: "Gender", value: incubateeData.gender },
    { label: "Mobile Number", value: incubateeData.mobile_number },
    { label: "Email", value: incubateeData.email },
    { label: "Company Name", value: incubateeData.company_name },
    { label: "Company Registration Number", value: incubateeData.company_registration_number },
    { label: "Position in Company", value: incubateeData.position_in_company },
    { label: "Majority Shareholding", value: incubateeData.majority_shareholding },
    { label: "Current Employees", value: incubateeData.current_number_of_employees },
    { label: "Physical Address", value: incubateeData.physical_address },
    { label: "Website Address", value: incubateeData.website_address },
    { label: "Years in Operation", value: incubateeData.years_in_operation },
    { label: "Province", value: incubateeData.province_name },
    { label: "Has Business Plan", value: incubateeData.has_business_plan ? "Yes" : "No" },
    { label: "Relevant Skill Set", value: incubateeData.relevant_skill_set },
    { label: "Technology/Product/Service", value: incubateeData.technology_product_service },
    { label: "Stage of Development", value: incubateeData.technology_stage_of_development },
    { label: "Status", value: formatLabel(incubateeData.status) },
    { label: "Incubated Date", value: incubateeData.incubated_date },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Incubatee: ${incubateeData.full_name}`} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p className="text-sm text-muted-foreground">Incubation Command Center</p>
            <h1 className="text-2xl font-semibold">{incubateeData.company_name}</h1>
            <p className="text-sm text-muted-foreground">{incubateeData.full_name}</p>
          </div>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <Link
              href="/business-development/incubatees"
              className="rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Back to List
            </Link>
          </div>
        </div>

        <section className="grid gap-3 md:grid-cols-4">
          <div className={`rounded-xl border p-4 ${riskClass(kpiSummary.health)}`}>
            <div className="text-xs uppercase tracking-wide">Portfolio Health</div>
            <div className="mt-2 text-2xl font-semibold">{formatLabel(kpiSummary.health)}</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Active KPIs</div>
            <div className="mt-2 text-2xl font-semibold">{kpiSummary.active}</div>
            <div className="text-xs text-muted-foreground">{kpiSummary.completed} completed</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Risk Flags</div>
            <div className="mt-2 text-2xl font-semibold">{kpiSummary.critical + kpiSummary.warnings}</div>
            <div className="text-xs text-muted-foreground">{kpiSummary.critical} critical, {kpiSummary.warnings} warning</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-muted-foreground">Overdue</div>
            <div className="mt-2 text-2xl font-semibold">{kpiSummary.overdue}</div>
            <div className="text-xs text-muted-foreground">KPI reviews or targets</div>
          </div>
        </section>

        <section className="grid gap-4 lg:grid-cols-3">
          <div className="rounded-xl border bg-card p-4 shadow-sm lg:col-span-1">
            <h2 className="text-base font-semibold">Intake Governance</h2>
            <div className="mt-4 space-y-3 text-sm">
              <div>
                <div className="text-xs uppercase tracking-wide text-muted-foreground">Intake Type</div>
                <div className="font-medium">{formatLabel(incubateeData.intake?.type)}</div>
              </div>
              <div>
                <div className="text-xs uppercase tracking-wide text-muted-foreground">Source</div>
                <div className="font-medium">{incubateeData.intake?.source ?? "-"}</div>
              </div>
              <div>
                <div className="text-xs uppercase tracking-wide text-muted-foreground">Approved By</div>
                <div className="font-medium">{incubateeData.intake?.approved_by?.name ?? "-"}</div>
              </div>
              <div>
                <div className="text-xs uppercase tracking-wide text-muted-foreground">Justification</div>
                <p className="text-muted-foreground">{incubateeData.intake?.justification ?? "No intake justification captured."}</p>
              </div>
            </div>
          </div>

          <div className="rounded-xl border bg-card p-4 shadow-sm lg:col-span-2">
            <div className="flex items-center justify-between gap-3">
              <div>
                <h2 className="text-base font-semibold">KPI Portfolio</h2>
                <p className="text-sm text-muted-foreground">Assigned performance indicators and latest review state.</p>
              </div>
              <span className="rounded-full border px-3 py-1 text-xs text-muted-foreground">{kpiSummary.total} total</span>
            </div>

            <div className="mt-4 space-y-3">
              {kpis.length === 0 ? (
                <div className="rounded-lg border border-dashed p-5 text-sm text-muted-foreground">
                  No KPIs have been assigned to this incubatee yet.
                </div>
              ) : (
                kpis.map((kpi) => (
                  <div key={kpi.id} className="rounded-lg border p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <div className="font-semibold">{kpi.definition.name ?? "KPI"}</div>
                        <div className="text-xs text-muted-foreground">
                          {formatLabel(kpi.definition.category)} • Target: {kpi.target_value ?? "-"} {kpi.definition.unit ?? ""}
                        </div>
                      </div>
                      <span className={`rounded-full border px-2.5 py-1 text-xs ${riskClass(kpi.progress.risk_state)}`}>
                        {formatLabel(kpi.progress.risk_state)}
                      </span>
                    </div>
                    <div className="mt-3 h-2 overflow-hidden rounded-full bg-muted">
                      <div
                        className="h-full rounded-full bg-orange-500"
                        style={{ width: `${Math.min(Math.max(kpi.progress.latest_progress_percent, 0), 100)}%` }}
                      />
                    </div>
                    <div className="mt-2 flex flex-wrap justify-between gap-2 text-xs text-muted-foreground">
                      <span>{kpi.progress.latest_progress_percent}% progress</span>
                      <span>Due: {kpi.due_date ?? "No due date"}</span>
                      <span>Status: {formatLabel(kpi.status)}</span>
                    </div>
                    {kpi.latest_review && (
                      <p className="mt-3 text-sm text-muted-foreground">
                        Latest review: {kpi.latest_review.mentor_comments ?? kpi.latest_review.evidence_notes ?? "No review notes captured."}
                      </p>
                    )}
                  </div>
                ))
              )}
            </div>
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Review Timeline</h2>
          <div className="mt-4 space-y-3">
            {latestReviews.length === 0 ? (
              <div className="rounded-lg border border-dashed p-5 text-sm text-muted-foreground">
                No KPI reviews have been recorded yet.
              </div>
            ) : (
              latestReviews.map(({ kpi, review }) => (
                <div key={`${kpi.id}-${review.id}`} className="rounded-lg border p-3 text-sm">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="font-medium">{kpi.definition.name}</div>
                    <span className={`rounded-full border px-2.5 py-1 text-xs ${riskClass(review.status)}`}>
                      {formatLabel(review.status)}
                    </span>
                  </div>
                  <div className="mt-1 text-xs text-muted-foreground">
                    {review.review_date ?? review.created_at ?? "No date"} • Reviewed by {review.reviewed_by?.name ?? "-"}
                  </div>
                  <p className="mt-2 text-muted-foreground">{review.mentor_comments ?? review.evidence_notes ?? "No notes captured."}</p>
                </div>
              ))
            )}
          </div>
        </section>

        <section className="rounded-xl border bg-card shadow-sm">
          <div className="grid gap-0 md:grid-cols-2">
            {detailRows.map((row) => (
              <div key={row.label} className="border-b p-3 md:border-r [&:nth-child(2n)]:md:border-r-0">
                <div className="text-xs uppercase tracking-wide text-muted-foreground">{row.label}</div>
                <div className="mt-1 text-sm font-medium">{row.value ?? "-"}</div>
              </div>
            ))}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
