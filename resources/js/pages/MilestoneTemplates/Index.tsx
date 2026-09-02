import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Milestone Templates", href: "/milestone-templates" },
];

export default function MilestoneTemplatesIndex({
  programs,
}: {
  programs: { id: number; title: string; milestone_count: number }[];
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Milestone Templates" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Milestone Templates</h1>
          <DomainNav items={projectNavItems} />
        </div>

        {programs.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No programs available yet.
          </p>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {programs.map((program) => (
              <Link
                key={program.id}
                href={`/milestone-templates/programs/${program.id}`}
                className="rounded-lg border bg-card p-4 shadow-sm transition hover:border-red-300 hover:shadow"
              >
                <div className="text-sm text-muted-foreground">Program</div>
                <div className="mt-1 text-lg font-semibold">
                  {program.title}
                </div>
                <div className="mt-2 text-xs text-muted-foreground">
                  Milestones: {program.milestone_count}
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
