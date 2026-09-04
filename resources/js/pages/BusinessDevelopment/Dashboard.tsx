import { Head } from "@inertiajs/react";
import { BriefcaseBusiness, CalendarClock, CheckCircle2, CircleDollarSign, FileText, ListChecks, TrendingUp, UserRoundCheck, XCircle } from "lucide-react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
];

type ActivityRow = {
  type: string;
  title: string;
  entity: string;
  entity_type: "application" | "incubatee";
  entity_id: number;
  status: string;
  details: string | null;
  actor: string | null;
  occurred_at: string | null;
};

export default function BusinessDevelopmentDashboard({
  stats,
  activities,
}: {
  stats: {
    totalApplications: number;
    pendingApplications: number;
    acceptedApplications: number;
    rejectedApplications: number;
    scheduledPitches: number;
    totalIncubatees: number;
    activeIncubatees: number;
    inactiveIncubatees: number;
    baselinePending: number;
    diagnosticsInProgress: number;
    activeDevelopmentPlans: number;
    highPriorityDevelopmentNeeds: number;
    complianceAttentionRequired: number;
  };
  activities: ActivityRow[];
}) {
  const chartTotal = Math.max(stats.totalApplications + stats.totalIncubatees + stats.scheduledPitches, 1);
  const activityBars = [
    { label: "Applications", value: stats.totalApplications, color: "bg-red-500" },
    { label: "Pitches", value: stats.scheduledPitches, color: "bg-orange-500" },
    { label: "Incubatees", value: stats.totalIncubatees, color: "bg-blue-500" },
  ];
  const acceptedPct = stats.totalApplications > 0 ? Math.round((stats.acceptedApplications / stats.totalApplications) * 100) : 0;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Business Development Dashboard" />

      <div className="space-y-6 bg-white p-4 text-slate-950 md:p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-3xl font-semibold tracking-normal">Business Development Dashboard</h1>
            <p className="mt-1 text-sm text-slate-500">Pipeline, pitch, and incubation performance overview.</p>
          </div>
          <DomainNav items={businessDevelopmentNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {[
            { label: "Applications", sub: "Total submitted", value: stats.totalApplications, icon: FileText, tone: "bg-red-50 text-red-600" },
            { label: "Pending", sub: "Awaiting assessment", value: stats.pendingApplications, icon: CalendarClock, tone: "bg-orange-50 text-orange-600" },
            { label: "Accepted", sub: "Passed assessment", value: stats.acceptedApplications, icon: CheckCircle2, tone: "bg-emerald-50 text-emerald-600" },
            { label: "Rejected", sub: "Did not qualify", value: stats.rejectedApplications, icon: XCircle, tone: "bg-rose-50 text-rose-600" },
            { label: "Scheduled Pitches", sub: "Applications with pitch dates", value: stats.scheduledPitches, icon: BriefcaseBusiness, tone: "bg-blue-50 text-blue-600" },
            { label: "Incubatees", sub: "Total records", value: stats.totalIncubatees, icon: UserRoundCheck, tone: "bg-violet-50 text-violet-600" },
            { label: "Active Incubatees", sub: "Currently active", value: stats.activeIncubatees, icon: TrendingUp, tone: "bg-lime-50 text-lime-600" },
            { label: "Inactive Incubatees", sub: "Currently inactive", value: stats.inactiveIncubatees, icon: CircleDollarSign, tone: "bg-slate-100 text-slate-600" },
            { label: "Baseline Pending", sub: "Incubatees without baseline", value: stats.baselinePending, icon: ListChecks, tone: "bg-amber-50 text-amber-700" },
            { label: "Diagnostics In Progress", sub: "Draft or in-progress", value: stats.diagnosticsInProgress, icon: ListChecks, tone: "bg-blue-50 text-blue-700" },
            { label: "Active Plans", sub: "Development plans", value: stats.activeDevelopmentPlans, icon: ListChecks, tone: "bg-emerald-50 text-emerald-700" },
            { label: "High Priority Needs", sub: "Open/planned/in progress", value: stats.highPriorityDevelopmentNeeds, icon: ListChecks, tone: "bg-red-50 text-red-700" },
            { label: "Compliance Attention", sub: "Completed diagnostics with gaps", value: stats.complianceAttentionRequired, icon: ListChecks, tone: "bg-orange-50 text-orange-700" },
          ].map((item) => (
            <section key={item.label} className="rounded-lg border bg-white p-5 shadow-sm">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="text-sm font-semibold text-slate-900">{item.label}</p>
                  <p className="mt-1 text-xs text-slate-500">{item.sub}</p>
                  <p className="mt-4 text-3xl font-semibold">{item.value}</p>
                </div>
                <span className={`inline-flex h-11 w-11 items-center justify-center rounded-full ${item.tone}`}>
                  <item.icon className="h-5 w-5" />
                </span>
              </div>
            </section>
          ))}
        </div>

        <div className="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
          <section className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Domain Activities</h2>
            <p className="text-sm text-slate-500">Activity volume across the development pipeline.</p>
            <div className="mt-8 space-y-5">
              {activityBars.map((item) => {
                const width = Math.max(6, Math.round((item.value / chartTotal) * 100));

                return (
                  <div key={item.label}>
                    <div className="mb-2 flex items-center justify-between text-sm">
                      <span className="font-medium">{item.label}</span>
                      <span className="text-slate-500">{item.value}</span>
                    </div>
                    <div className="h-3 rounded-full bg-slate-100">
                      <div className={`h-3 rounded-full ${item.color}`} style={{ width: `${width}%` }} />
                    </div>
                  </div>
                );
              })}
            </div>
          </section>

          <section className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Pipeline Status</h2>
            <p className="text-sm text-slate-500">Application conversion and incubation mix.</p>
            <div className="mt-6 flex flex-col items-center gap-5 sm:flex-row sm:justify-center xl:flex-col">
              <div
                className="grid h-48 w-48 place-items-center rounded-full"
                style={{
                  background: `conic-gradient(#16a34a 0 ${acceptedPct}%, #ef4444 ${acceptedPct}% ${acceptedPct + (stats.totalApplications ? Math.round((stats.rejectedApplications / stats.totalApplications) * 100) : 0)}%, #f97316 0 100%)`,
                }}
              >
                <div className="grid h-28 w-28 place-items-center rounded-full bg-white text-center shadow-inner">
                  <div>
                    <div className="text-3xl font-semibold">{acceptedPct}%</div>
                    <div className="text-xs text-slate-500">Accepted</div>
                  </div>
                </div>
              </div>
              <div className="w-full max-w-xs space-y-3 text-sm">
                {[
                  ["Pending", stats.pendingApplications, "bg-orange-500"],
                  ["Accepted", stats.acceptedApplications, "bg-emerald-500"],
                  ["Rejected", stats.rejectedApplications, "bg-red-500"],
                  ["Active incubatees", stats.activeIncubatees, "bg-blue-500"],
                ].map(([label, value, color]) => (
                  <div key={label} className="flex items-center justify-between gap-4">
                    <span className="inline-flex items-center gap-2"><span className={`h-2.5 w-2.5 rounded-full ${color}`} />{label}</span>
                    <span className="font-semibold">{value}</span>
                  </div>
                ))}
              </div>
            </div>
          </section>
        </div>

        <section className="overflow-hidden rounded-lg border bg-white shadow-sm">
          <div className="border-b px-4 py-3">
            <h2 className="text-lg font-semibold">Recent Activities</h2>
            <p className="text-sm text-slate-500">
              Recent activity across applications and incubatees.
            </p>
          </div>
          <div className="overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3 text-left font-medium">Date/Time</th>
                <th className="px-4 py-3 text-left font-medium">Activity</th>
                <th className="px-4 py-3 text-left font-medium">Entity</th>
                <th className="px-4 py-3 text-left font-medium">Status</th>
                <th className="px-4 py-3 text-left font-medium">Actor</th>
                <th className="px-4 py-3 text-left font-medium">Details</th>
              </tr>
            </thead>
            <tbody>
              {activities.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-3 py-4 text-muted-foreground">
                    No activity yet.
                  </td>
                </tr>
              ) : (
                activities.map((activity, index) => (
                  <tr key={`${activity.type}-${activity.entity_id}-${index}`} className="border-t">
                    <td className="px-4 py-3">{activity.occurred_at ?? "-"}</td>
                    <td className="px-4 py-3 font-medium">{activity.title}</td>
                    <td className="px-4 py-3">
                      {activity.entity_type === "application" ? (
                        <a
                          href={`/business-development/applications/${activity.entity_id}`}
                          className="text-orange-600 hover:underline"
                        >
                          {activity.entity}
                        </a>
                      ) : (
                        activity.entity
                      )}
                    </td>
                    <td className="px-4 py-3 capitalize"><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium">{activity.status}</span></td>
                    <td className="px-4 py-3">{activity.actor ?? "-"}</td>
                    <td className="px-4 py-3">{activity.details ?? "-"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
