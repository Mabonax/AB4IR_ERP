import { useState } from "react";
import { Head, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { ProjectLocationModelFormConfig } from "@/config/forms/project-location-model-form";
import { ProjectLocationTableConfig } from "@/config/tables/project-location-table";

import projectLocations from "@/routes/project-locations";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Locations", href: "/project-locations" },
];

/* =========================================================
| PAGE
========================================================= */

export default function ProjectLocationIndex({
  locations,
  projects,
  facilitators,
  provinces,
}: {
  locations: { data: any[] };
  projects: { id: number; name: string }[];
  facilitators: { id: number; name: string }[];
  provinces: { id: number; name: string }[];
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedLocation, setSelectedLocation] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [locationToDelete, setLocationToDelete] = useState<any | null>(null);

  const mappedLocationData = selectedLocation
    ? {
        project_id:
          selectedLocation.project_id !== null &&
          selectedLocation.project_id !== undefined
            ? String(selectedLocation.project_id)
            : "",
        facilitator_id:
          selectedLocation.facilitator_id !== null &&
          selectedLocation.facilitator_id !== undefined
            ? String(selectedLocation.facilitator_id)
            : "",
        province_id:
          selectedLocation.province_id !== null &&
          selectedLocation.province_id !== undefined
            ? String(selectedLocation.province_id)
            : "",
        training_venue_address: selectedLocation.training_venue_address ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project Locations" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Project Locations</h1>
          <DomainNav items={projectNavItems} />

          <CustomModelForm
            addButton={ProjectLocationModelFormConfig.addButton}
            title="Add Project Location"
            description={ProjectLocationModelFormConfig.description}
            fields={ProjectLocationModelFormConfig.fields}
            submitRoute={projectLocations.store}
            options={{ projects, facilitators, provinces }}
          />
        </div>

        <CustomTable
          columns={ProjectLocationTableConfig.columns}
          data={locations.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                router.visit(`/project-locations/${row.id}/progress`);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedLocation(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setLocationToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedLocation && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Location Details" : "Edit Location"}
            fields={ProjectLocationModelFormConfig.fields}
            mode={mode}
            initialData={mappedLocationData}
            submitRoute={projectLocations.update}
            routeParams={selectedLocation.id}
            options={{ projects, facilitators, provinces }}
          >
            {mode === "view" && (
              <div className="rounded-lg border bg-card p-4 text-sm">
                <div className="font-semibold">Beneficiaries</div>
                <div className="mt-1 text-xs text-gray-600">
                  Total: {selectedLocation.beneficiary_count ?? 0}
                </div>
                {selectedLocation.beneficiaries?.length > 0 ? (
                  <ul className="mt-2 list-disc pl-5 text-xs text-gray-700">
                    {selectedLocation.beneficiaries.map((b: any) => (
                      <li key={b.id}>{b.name}</li>
                    ))}
                  </ul>
                ) : (
                  <div className="mt-2 text-xs text-gray-500">No beneficiaries</div>
                )}
              </div>
            )}
          </CustomModelForm>
        )}

        {locationToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Project Location"
            submitRoute={projectLocations.destroy}
            routeParams={locationToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
