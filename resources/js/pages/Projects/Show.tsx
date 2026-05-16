import { Head, router } from "@inertiajs/react";

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
  { title: "Project View", href: "#" },
];

const readinessTone = (ready: boolean) =>
  ready
    ? "border-emerald-200 bg-emerald-50 text-emerald-700"
    : "border-amber-200 bg-amber-50 text-amber-700";

export default function ProjectShow({
  project,
  milestones,
  locations,
}: {
  project: any;
  milestones: any[];
  locations: any[];
}) {
  const projectData = project?.data ?? project;
  const statusSummary = projectData.status_summary;
  const handleSyncMilestones = (e: React.FormEvent) => {
    e.preventDefault();

    router.post(`/projects/${projectData.id}/milestones/sync`, {});
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project View" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">{projectData.name}</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Card>
            <CardHeader>
              <CardTitle>Status</CardTitle>
              <CardDescription>Current</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {projectData.status_label ?? projectData.status ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Start Date</CardTitle>
              <CardDescription>Project start</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {projectData.start_date ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Active sites</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {locations.length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Milestones</CardTitle>
              <CardDescription>Unit standards</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {milestones.length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Beneficiaries</CardTitle>
              <CardDescription>Total</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">
              {locations.reduce(
                (sum, loc) => sum + (loc.total_beneficiaries ?? 0),
                0
              )}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Transition Readiness</CardTitle>
              <CardDescription>Current workflow state and blockers</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  Allowed transitions
                </div>
                {statusSummary?.allowed_transitions?.length ? (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {statusSummary.allowed_transitions.map((transition: any) => (
                      <span
                        key={transition.status}
                        className={`rounded-full border px-2.5 py-1 text-xs font-medium ${readinessTone(transition.ready)}`}
                      >
                        {transition.label}
                        {!transition.ready ? ` (${transition.blockers.length} blocker${transition.blockers.length === 1 ? "" : "s"})` : ""}
                      </span>
                    ))}
                  </div>
                ) : (
                  <p className="mt-2 text-sm text-muted-foreground">
                    No further transitions are allowed for this project.
                  </p>
                )}
              </div>

              <div className="grid gap-3">
                {(["active", "completed"] as const).map((statusKey) => {
                  const readiness = statusSummary?.readiness?.[statusKey];

                  if (!readiness) return null;

                  return (
                    <div
                      key={statusKey}
                      className={`rounded-lg border p-3 ${readinessTone(readiness.ready)}`}
                    >
                      <div className="text-xs font-semibold uppercase tracking-wide">
                        {statusKey === "active" ? "Activation readiness" : "Completion readiness"}
                      </div>
                      <div className="mt-1 text-sm font-medium">
                        {readiness.ready ? "Ready" : `${readiness.blockers.length} blocker${readiness.blockers.length === 1 ? "" : "s"}`}
                      </div>
                      {!readiness.ready && (
                        <ul className="mt-2 space-y-1 text-xs">
                          {readiness.blockers.map((blocker: string) => (
                            <li key={blocker}>{blocker}</li>
                          ))}
                        </ul>
                      )}
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Milestones</CardTitle>
              <CardDescription>Attached to project</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSyncMilestones} className="mb-4">
                <button
                  type="submit"
                  className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                >
                  Attach program milestones
                </button>
              </form>

              {milestones.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No milestones attached yet.
                </p>
              ) : (
                <ul className="space-y-2 text-sm">
                  {milestones.map((m) => (
                    <li key={m.id} className="flex items-center justify-between">
                      <span>{m.title}</span>
                      <span className="text-muted-foreground">
                        Max: {m.max_score ?? "-"}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Locations</CardTitle>
              <CardDescription>Progress by site</CardDescription>
            </CardHeader>
            <CardContent>
              {locations.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  No locations added yet.
                </p>
              ) : (
                <div className="space-y-4">
                  {locations.map((loc) => (
                    <div key={loc.id} className="rounded-md border p-3">
                      <div className="font-medium">{loc.location}</div>
                      <div className="text-xs text-muted-foreground">
                        Facilitator: {loc.facilitator_name ?? "-"}
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        Beneficiaries: {loc.total_beneficiaries ?? 0}
                      </div>
                      <div className="mt-2 space-y-1 text-xs">
                        {loc.milestones.map((m: any) => (
                          <div key={m.id} className="flex justify-between">
                            <span>{m.title}</span>
                            <span>
                              {m.assessed}/{m.total}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
