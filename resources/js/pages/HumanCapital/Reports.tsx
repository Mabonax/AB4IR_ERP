import { Head } from "@inertiajs/react";

import { HorizontalBarChart } from "@/components/charts/dashboard-charts";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { humanCapitalNavItems } from "@/config/domain-nav/human-capital";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Township Intelligence Reports", href: "/human-capital/reports" }];

type TownshipReport = {
  township_name: string;
  population_registered: number;
  qualifications: Array<{ label: string; value: number }>;
  skills: Array<{ label: string; value: number }>;
  employment: Array<{ label: string; value: number }>;
};

export default function HumanCapitalReports({ townships }: { townships: TownshipReport[] }) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Township Intelligence Reports" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Township Intelligence Reports</h1>
            <p className="text-sm text-muted-foreground">Summary intelligence cards for township population, qualifications, employment, and skills readiness.</p>
          </div>
          <DomainNav items={humanCapitalNavItems} />
        </div>

        {townships.length === 0 ? (
          <Card>
            <CardContent className="p-6 text-sm text-muted-foreground">No township intelligence can be produced until member profiles include township-linked registration data.</CardContent>
          </Card>
        ) : (
          townships.map((township) => (
            <Card key={township.township_name}>
              <CardHeader>
                <CardTitle>{township.township_name}</CardTitle>
                <CardDescription>{township.population_registered} registered members currently inform this township intelligence profile.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="grid gap-4 md:grid-cols-3">
                  <Metric title="Population Registered" value={township.population_registered} />
                  <Metric title="Qualification Signals" value={township.qualifications.reduce((sum, item) => sum + item.value, 0)} />
                  <Metric title="Skills Signals" value={township.skills.reduce((sum, item) => sum + item.value, 0)} />
                </div>
                <div className="grid gap-6 xl:grid-cols-3">
                  <HorizontalBarChart title="Qualifications" description="Most common fields of study in this township." items={township.qualifications} emptyMessage="No qualification data captured for this township." />
                  <HorizontalBarChart title="Skills" description="Most common practical skills available in this township." items={township.skills} emptyMessage="No skills data captured for this township." />
                  <HorizontalBarChart title="Employment" description="Employment pressure and readiness in this township." items={township.employment} emptyMessage="No employment data captured for this township." />
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </AppLayout>
  );
}

function Metric({ title, value }: { title: string; value: number }) {
  return (
    <div className="rounded-xl border p-4">
      <div className="text-sm text-muted-foreground">{title}</div>
      <div className="mt-2 text-3xl font-semibold">{value}</div>
    </div>
  );
}
