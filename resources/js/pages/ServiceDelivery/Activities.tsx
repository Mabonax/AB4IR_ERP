import { Head } from "@inertiajs/react";
import { CirclePlus } from "lucide-react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { ServiceDeliveryNav } from "@/components/service-delivery-nav";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Service Delivery", href: "/service-delivery" },
  { title: "Activities", href: "/service-delivery/activities" },
];

const activityFields = [
  { id: "activity-project", name: "project_id", label: "Project", type: "select", optionsSource: "projects", optionLabel: "name", optionValue: "id", required: true },
  { id: "activity-name", name: "name", label: "Activity Name", type: "text", required: true },
  { id: "activity-description", name: "description", label: "Description", type: "textarea" },
  { id: "activity-planned", name: "planned_date", label: "Planned Date", type: "date" },
  { id: "activity-actual", name: "actual_date", label: "Actual Date", type: "date" },
  { id: "activity-status", name: "status", label: "Status", type: "select", options: [
    { label: "Planned", value: "planned" },
    { label: "In Progress", value: "in_progress" },
    { label: "Completed", value: "completed" },
    { label: "Cancelled", value: "cancelled" },
  ] },
  { id: "activity-team", name: "assigned_team", label: "Assigned Team", type: "text" },
];

const activityColumns = [
  { label: "Activity", key: "name", className: "px-4 py-2 text-left" },
  { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
  { label: "Planned", key: "planned_date", className: "px-4 py-2 text-left" },
  { label: "Actual", key: "actual_date", className: "px-4 py-2 text-left" },
  { label: "Status", key: "status", className: "px-4 py-2 text-left" },
  { label: "Team", key: "assigned_team", className: "px-4 py-2 text-left" },
  { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
];

export default function ActivitiesPage({ projects, activities }: { projects: any[]; activities: any[] }) {
  const [selected, setSelected] = useState<any | null>(null);
  const [open, setOpen] = useState(false);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Project Activities" />
      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Project activities</h1>
            <p className="mt-2 text-sm text-slate-600">Manage planned and completed service-delivery activities per project.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <CustomModelForm
              addButton={{ label: "Add Activity", icon: CirclePlus, className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700" }}
              title="Add Project Activity"
              description="Capture operational delivery activity against a project."
              fields={activityFields}
              submitRoute={() => ({ url: "/service-delivery/activities", method: "post" })}
              options={{ projects }}
            />
            <ServiceDeliveryNav />
          </div>
        </div>

        <CustomTable
          columns={activityColumns}
          data={activities}
          actions={[
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelected(row);
                setOpen(true);
              },
            },
          ]}
        />

        {selected ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Project Activity"
            fields={activityFields}
            initialData={selected}
            submitRoute={() => ({ url: `/service-delivery/activities/${selected.id}`, method: "put" })}
            options={{ projects }}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
