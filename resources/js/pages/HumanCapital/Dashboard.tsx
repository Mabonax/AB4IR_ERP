import { Head } from "@inertiajs/react";

import { HorizontalBarChart, StackedCompositionChart } from "@/components/charts/dashboard-charts";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { humanCapitalNavItems } from "@/config/domain-nav/human-capital";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Human Capital", href: "/human-capital/dashboard" }];

export default function HumanCapitalDashboard({
  stats,
  province_distribution,
  township_distribution,
  branch_distribution,
  qualification_distribution,
  skill_distribution,
  employment_distribution,
  gender_distribution,
  youth_statistics,
  report_cards,
}: {
  stats: Record<string, number>;
  province_distribution: Array<{ label: string; value: number }>;
  township_distribution: Array<{ label: string; value: number }>;
  branch_distribution: Array<{ label: string; value: number }>;
  qualification_distribution: Array<{ label: string; value: number }>;
  skill_distribution: Array<{ label: string; value: number }>;
  employment_distribution: Array<{ label: string; value: number }>;
  gender_distribution: Array<{ label: string; value: number }>;
  youth_statistics: Record<string, number>;
  report_cards: Array<{ township_name: string; population_registered: number }>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Human Capital Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Human Capital Dashboard</h1>
            <p className="text-sm text-muted-foreground">Live intelligence across township member registration, qualifications, skills, and employment readiness.</p>
          </div>
          <DomainNav items={humanCapitalNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
          <MetricCard title="Registered Members" value={stats.total_members} description="Total people profiled" />
          <MetricCard title="Volunteers" value={stats.total_volunteers} description="Volunteer records" />
          <MetricCard title="Graduates" value={stats.total_graduates} description="Graduate members" />
          <MetricCard title="Unemployed" value={stats.total_unemployed} description="Employment pressure" />
          <MetricCard title="Skills" value={stats.total_skills} description="Skills captured" />
          <MetricCard title="Qualifications" value={stats.total_qualifications} description="Qualification records" />
        </div>

        <div className="grid gap-6 xl:grid-cols-2">
          <HorizontalBarChart title="Members by Province" description="Highest registered populations by province." items={province_distribution} emptyMessage="No province registration data yet." />
          <HorizontalBarChart title="Members by Township" description="Townships with the strongest captured population." items={township_distribution} emptyMessage="No township registration data yet." />
          <HorizontalBarChart title="Qualification Distribution" description="Most common fields of study in the registry." items={qualification_distribution} emptyMessage="No qualification data yet." />
          <HorizontalBarChart title="Skills Distribution" description="Most common practical skills in the registry." items={skill_distribution} emptyMessage="No skills data yet." />
        </div>

        <div className="grid gap-6 xl:grid-cols-[1.2fr,1fr,1fr]">
          <StackedCompositionChart
            title="Employment Distribution"
            description="Current employment pressure across registered members."
            segments={employment_distribution.map((segment, index) => ({
              label: segment.label,
              value: segment.value,
              colorClass: ["bg-red-500", "bg-red-600", "bg-amber-500", "bg-emerald-500", "bg-sky-500", "bg-blue-500"][index % 6],
            }))}
            emptyMessage="No employment data yet."
          />
          <StackedCompositionChart
            title="Gender Distribution"
            description="Current gender mix of the registry."
            segments={gender_distribution.map((segment, index) => ({
              label: segment.label,
              value: segment.value,
              colorClass: ["bg-sky-500", "bg-red-500", "bg-amber-500", "bg-emerald-500"][index % 4],
            }))}
            emptyMessage="No gender data yet."
          />
          <Card>
            <CardHeader>
              <CardTitle>Youth and Inclusion</CardTitle>
              <CardDescription>Priority indicators for township programming.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <StatLine label="Youth Members" value={youth_statistics.youth_members ?? 0} />
              <StatLine label="Veterans" value={youth_statistics.veterans ?? 0} />
              <StatLine label="Members with Disability" value={youth_statistics.members_with_disability ?? 0} />
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Township Intelligence Focus</CardTitle>
            <CardDescription>Highest registered township populations to prioritise for programme design and placement strategy.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {report_cards.slice(0, 8).map((card) => (
              <div key={card.township_name} className="rounded-xl border p-4">
                <div className="text-sm text-muted-foreground">Township</div>
                <div className="mt-1 font-semibold">{card.township_name}</div>
                <div className="mt-4 text-3xl font-semibold">{card.population_registered}</div>
                <div className="text-sm text-muted-foreground">registered profiles</div>
              </div>
            ))}
          </CardContent>
        </Card>

        <HorizontalBarChart title="Members by Branch" description="Branch-level distribution for mobilisation and reporting." items={branch_distribution} emptyMessage="No branch-level assignments yet." />
      </div>
    </AppLayout>
  );
}

function MetricCard({ title, value, description }: { title: string; value: number; description: string }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent className="text-2xl font-semibold">{value}</CardContent>
    </Card>
  );
}

function StatLine({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-lg border p-3">
      <div className="text-sm text-muted-foreground">{label}</div>
      <div className="text-2xl font-semibold">{value}</div>
    </div>
  );
}
