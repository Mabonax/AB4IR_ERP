import { useState } from "react";
import { Head } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { ProjectLocationModelFormConfig } from "@/config/forms/project-location-model-form";
import { ProjectLocationTableConfig } from "@/config/tables/project-location-table";

import projectLocations from "@/routes/project-locations";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Project Locations", href: projectLocations.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function ProjectLocationIndex({
  locations,
  projects,
  facilitators,
}: {
  locations: { data: any[] };
  projects: { id: number; name: string }[];
  facilitators: { id: number; name: string }[];
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
        location: selectedLocation.location ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project Locations" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Project Locations</h1>

          <CustomModelForm
            addButton={ProjectLocationModelFormConfig.addButton}
            title="Add Project Location"
            description={ProjectLocationModelFormConfig.description}
            fields={ProjectLocationModelFormConfig.fields}
            submitRoute={projectLocations.store}
            options={{ projects, facilitators }}
          />
        </div>

        <CustomTable
          columns={ProjectLocationTableConfig.columns}
          data={locations.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedLocation(row);
                setMode("view");
                setOpen(true);
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
            options={{ projects, facilitators }}
          />
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
