import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";

import { ProjectEnrollmentTableConfig } from "@/config/tables/project-enrollment-table";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";

import { type BreadcrumbItem } from "@/types";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Enrollments", href: "/project-enrollments" },
];

/* =========================================================
| PAGE
========================================================= */

export default function ProjectEnrollmentIndex({
  projects,
}: {
  projects: {
    id: number;
    name: string;
    start_date: string | null;
    status: string | null;
    locations_count: number;
    beneficiary_count: number;
    locations: Array<{
      id: number;
      location: string | null;
      facilitator_name: string | null;
      beneficiary_count: number;
      beneficiaries: Array<{ id: number; name: string | null }>;
    }>;
  }[];
}) {
  const [open, setOpen] = useState(false);
  const [selectedProject, setSelectedProject] = useState<any | null>(null);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project Enrollments" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-start gap-3">
          <h1 className="text-xl font-semibold">Project Enrollments</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <CustomTable
          columns={ProjectEnrollmentTableConfig.columns}
          data={projects}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedProject(row);
                setOpen(true);
              },
            },
          ]}
        />

        {selectedProject && (
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>Project Details</DialogTitle>
                <DialogDescription>
                  {selectedProject.name ?? "-"}
                </DialogDescription>
              </DialogHeader>

              <div className="rounded-lg border bg-white p-4 text-sm">
                <div className="font-semibold">Project Summary</div>
                <div className="mt-2 space-y-1 text-gray-700">
                  <div>Project: {selectedProject.name ?? "-"}</div>
                  <div>Start Date: {selectedProject.start_date ?? "-"}</div>
                  <div>Locations: {selectedProject.locations_count ?? 0}</div>
                  <div>Beneficiaries: {selectedProject.beneficiary_count ?? 0}</div>
                </div>

                <div className="mt-4 font-semibold">Locations</div>
                <div className="mt-2 space-y-3">
                  {(selectedProject.locations ?? []).map((loc: any) => (
                    <div key={loc.id} className="rounded-md border p-3">
                      <div className="font-medium">{loc.location ?? "-"}</div>
                      <div className="text-xs text-gray-600">
                        Facilitator: {loc.facilitator_name ?? "-"}
                      </div>
                      <div className="mt-1 text-xs text-gray-600">
                        Beneficiaries: {loc.beneficiary_count ?? 0}
                      </div>
                      {loc.beneficiaries?.length > 0 && (
                        <ul className="mt-2 list-disc pl-5 text-xs text-gray-700">
                          {loc.beneficiaries.map((b: any) => (
                            <li key={b.id}>{b.name}</li>
                          ))}
                        </ul>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <DialogFooter>
                <Button variant="outline" onClick={() => setOpen(false)}>
                  Close
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        )}
      </div>
    </AppLayout>
  );
}
