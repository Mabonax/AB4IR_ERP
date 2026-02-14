import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
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
  };
  activities: ActivityRow[];
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Business Development Dashboard" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Business Development Dashboard</h1>
          <DomainNav items={businessDevelopmentNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Applications</CardTitle>
              <CardDescription>Total submitted</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.totalApplications}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Pending</CardTitle>
              <CardDescription>Awaiting assessment</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.pendingApplications}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Accepted</CardTitle>
              <CardDescription>Passed assessment</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.acceptedApplications}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Rejected</CardTitle>
              <CardDescription>Did not qualify</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.rejectedApplications}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Scheduled Pitches</CardTitle>
              <CardDescription>Applications with pitch dates</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.scheduledPitches}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Incubatees</CardTitle>
              <CardDescription>Total records</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.totalIncubatees}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Incubatees</CardTitle>
              <CardDescription>Currently active</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.activeIncubatees}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Inactive Incubatees</CardTitle>
              <CardDescription>Currently inactive</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.inactiveIncubatees}</CardContent>
          </Card>
        </div>

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <div className="border-b px-4 py-3">
            <h2 className="text-base font-semibold">Domain Activities</h2>
            <p className="text-sm text-muted-foreground">
              Recent activity across applications and incubatees.
            </p>
          </div>
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left font-medium">Date/Time</th>
                <th className="px-3 py-2 text-left font-medium">Activity</th>
                <th className="px-3 py-2 text-left font-medium">Entity</th>
                <th className="px-3 py-2 text-left font-medium">Status</th>
                <th className="px-3 py-2 text-left font-medium">Actor</th>
                <th className="px-3 py-2 text-left font-medium">Details</th>
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
                    <td className="px-3 py-2">{activity.occurred_at ?? "-"}</td>
                    <td className="px-3 py-2">{activity.title}</td>
                    <td className="px-3 py-2">
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
                    <td className="px-3 py-2 capitalize">{activity.status}</td>
                    <td className="px-3 py-2">{activity.actor ?? "-"}</td>
                    <td className="px-3 py-2">{activity.details ?? "-"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </section>
      </div>
    </AppLayout>
  );
}
