import { Head } from "@inertiajs/react";

import { ServiceDeliveryNav } from "@/components/service-delivery-nav";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Service Delivery", href: "/service-delivery" },
];

function StatCard({ label, value, detail }: { label: string; value: number; detail: string }) {
  return (
    <div className="rounded-2xl border border-red-100 bg-white p-5 shadow-sm">
      <p className="text-xs font-semibold uppercase tracking-[0.24em] text-red-600">{label}</p>
      <p className="mt-3 text-3xl font-semibold text-slate-900">{value}</p>
      <p className="mt-2 text-sm text-slate-600">{detail}</p>
    </div>
  );
}

function GeoTable({ title, rows }: { title: string; rows: Array<{ name: string; total: number; active: number; completed: number }> }) {
  return (
    <div className="rounded-2xl border border-red-100 bg-white p-5 shadow-sm">
      <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
      <div className="mt-4 overflow-hidden rounded-xl border">
        <table className="min-w-full">
          <thead className="bg-gradient-to-r from-red-600 to-red-500 text-white">
            <tr>
              <th className="px-4 py-2 text-left text-sm font-semibold">Area</th>
              <th className="px-4 py-2 text-left text-sm font-semibold">Total</th>
              <th className="px-4 py-2 text-left text-sm font-semibold">Active</th>
              <th className="px-4 py-2 text-left text-sm font-semibold">Completed</th>
            </tr>
          </thead>
          <tbody className="divide-y bg-white">
            {rows.map((row) => (
              <tr key={row.name}>
                <td className="px-4 py-2 text-sm text-slate-900">{row.name}</td>
                <td className="px-4 py-2 text-sm text-slate-700">{row.total}</td>
                <td className="px-4 py-2 text-sm text-slate-700">{row.active}</td>
                <td className="px-4 py-2 text-sm text-slate-700">{row.completed}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default function ServiceDeliveryDashboard({ dashboard }: { dashboard: any }) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Service Delivery" />

      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.28em] text-red-600">Phase 4</p>
            <h1 className="mt-2 text-3xl font-semibold text-slate-900">Programme and service delivery platform</h1>
            <p className="mt-2 max-w-3xl text-sm text-slate-600">
              Delivery intelligence across programmes, projects, beneficiaries, attendance, placements, outcomes, and geographic reach.
            </p>
          </div>
          <ServiceDeliveryNav />
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <StatCard label="Programmes" value={dashboard.programmes.total} detail={`${dashboard.programmes.active} active and ${dashboard.programmes.completed} completed`} />
          <StatCard label="Projects" value={dashboard.projects.total} detail={`${dashboard.projects.active} currently active`} />
          <StatCard label="Beneficiaries" value={dashboard.beneficiaries.active} detail={`${dashboard.beneficiaries.registered} registered and ${dashboard.beneficiaries.completed} completed`} />
          <StatCard label="Placements" value={dashboard.placements.employment} detail={`${dashboard.placements.internships} internships and ${dashboard.placements.learnerships} learnerships`} />
        </div>

        <div className="grid gap-4 md:grid-cols-3">
          <StatCard label="Activities" value={dashboard.activities.total} detail={`${dashboard.activities.in_progress} in progress and ${dashboard.activities.completed} completed`} />
          <StatCard label="Attendance" value={dashboard.attendance.records} detail={`${dashboard.attendance.present} present records and ${dashboard.attendance.absent} absent records`} />
          <StatCard label="Outcomes" value={dashboard.outcomes.actual_total} detail={`${dashboard.outcomes.tracked} tracked outcomes against ${dashboard.outcomes.target_total} target`} />
        </div>

        <div className="grid gap-4 xl:grid-cols-3">
          <GeoTable title="Beneficiaries by Province" rows={dashboard.geography.provinces} />
          <GeoTable title="Beneficiaries by Township" rows={dashboard.geography.townships} />
          <GeoTable title="Beneficiaries by Branch" rows={dashboard.geography.branches} />
        </div>
      </div>
    </AppLayout>
  );
}
