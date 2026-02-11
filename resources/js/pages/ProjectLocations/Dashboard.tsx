import { Head } from "@inertiajs/react";

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
import { CustomTable } from "@/components/custom-table";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Facilitator Dashboard", href: "/project-locations/dashboard" },
];

export default function ProjectLocationsDashboard({
  stats,
  locations,
}: {
  stats: {
    total_locations: number;
    total_beneficiaries: number;
    completed_assessments: number;
    total_assessments: number;
  };
  locations: Array<{
    id: number;
    project_name: string | null;
    province: string | null;
    facilitator_name: string | null;
    beneficiaries: number;
    completed_assessments: number;
    total_assessments: number;
  }>;
}) {
  const columns = [
    { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
    { label: "Location", key: "province", className: "px-4 py-2 text-left" },
    { label: "Facilitator", key: "facilitator_name", className: "px-4 py-2 text-left" },
    { label: "Beneficiaries", key: "beneficiaries", className: "px-4 py-2 text-left" },
    {
      label: "Assessments",
      key: "assessments",
      className: "px-4 py-2 text-left",
      render: (row: any) => (
        <span>
          {row.completed_assessments}/{row.total_assessments}
        </span>
      ),
    },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Facilitator Dashboard" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Facilitator Dashboard</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Total</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.total_locations}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Beneficiaries</CardTitle>
              <CardDescription>Total</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.total_beneficiaries}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Assessments</CardTitle>
              <CardDescription>Assessed</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.completed_assessments}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Assessment Total</CardTitle>
              <CardDescription>Recorded</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.total_assessments}
            </CardContent>
          </Card>
        </div>

        <CustomTable
          columns={columns}
          data={locations}
          actions={[
            {
              icon: "ClipboardCheck",
              onClick: (row) => {
                window.location.href = `/project-locations/${row.id}/progress`;
              },
            },
          ]}
        />

        <div className="text-sm text-muted-foreground">
          Tip: Use the progress button to assess beneficiaries per milestone.
        </div>
      </div>
    </AppLayout>
  );
}
