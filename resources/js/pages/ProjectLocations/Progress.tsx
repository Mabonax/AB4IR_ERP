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
  { title: "Locations", href: "/project-locations" },
  { title: "Progress", href: "#" },
];

export default function ProjectLocationProgress({
  location,
  milestones,
  beneficiaries,
}: {
  location: {
    project_name: string | null;
    province: string | null;
    facilitator_name: string | null;
  };
  milestones: Array<{ id: number; title: string; total: number; completed: number }>;
  beneficiaries: Array<{ id: number; name: string; completed_milestones: number }>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Location Progress" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Location Progress</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Project</CardTitle>
              <CardDescription>Current</CardDescription>
            </CardHeader>
            <CardContent className="text-lg font-semibold">
              {location.project_name ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Location</CardTitle>
              <CardDescription>Province</CardDescription>
            </CardHeader>
            <CardContent className="text-lg font-semibold">
              {location.province ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Facilitator</CardTitle>
              <CardDescription>Assigned</CardDescription>
            </CardHeader>
            <CardContent className="text-lg font-semibold">
              {location.facilitator_name ?? "-"}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Milestone Progress</CardTitle>
              <CardDescription>Completed per milestone</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {milestones.length === 0 ? (
                <p className="text-muted-foreground">No milestones.</p>
              ) : (
                milestones.map((m) => (
                  <div key={m.id} className="flex justify-between">
                    <span>{m.title}</span>
                    <span>
                      {m.completed}/{m.total}
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Beneficiaries</CardTitle>
              <CardDescription>Completed milestones</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {beneficiaries.length === 0 ? (
                <p className="text-muted-foreground">No beneficiaries.</p>
              ) : (
                beneficiaries.map((b) => (
                  <div key={b.id} className="flex justify-between">
                    <span>{b.name}</span>
                    <span>{b.completed_milestones}</span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
