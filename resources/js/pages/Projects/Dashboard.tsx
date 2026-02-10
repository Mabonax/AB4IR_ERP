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
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
];

export default function ProjectsDashboard({
  stats,
}: {
  stats: {
    totalProjects: number;
    activeProjects: number;
    completedProjects: number;
    totalBeneficiaries: number;
    totalLocations: number;
  };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Projects Dashboard" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Projects Dashboard</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Total Projects</CardTitle>
              <CardDescription>All projects</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalProjects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active</CardTitle>
              <CardDescription>In progress</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.activeProjects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Completed</CardTitle>
              <CardDescription>Finished</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.completedProjects}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Beneficiaries</CardTitle>
              <CardDescription>Enrolled</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalBeneficiaries}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Active sites</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {stats.totalLocations}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
