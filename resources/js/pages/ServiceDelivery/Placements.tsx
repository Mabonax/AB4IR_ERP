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
  { title: "Placements", href: "/service-delivery/placements" },
];

const fields = [
  { id: "placement-beneficiary", name: "beneficiary_id", label: "Beneficiary", type: "select", optionsSource: "beneficiaries", optionLabel: "name", optionValue: "id", required: true },
  { id: "placement-employer", name: "employer", label: "Employer", type: "text", required: true },
  { id: "placement-type", name: "opportunity_type", label: "Opportunity Type", type: "select", options: [
    { label: "Internship", value: "internship" },
    { label: "Learnership", value: "learnership" },
    { label: "Apprenticeship", value: "apprenticeship" },
    { label: "Employment", value: "employment" },
    { label: "Volunteer Placement", value: "volunteer_placement" },
  ] },
  { id: "placement-date", name: "placement_date", label: "Placement Date", type: "date" },
  { id: "placement-completion", name: "completion_date", label: "Completion Date", type: "date" },
  { id: "placement-status", name: "status", label: "Placement Status", type: "text" },
  { id: "placement-notes", name: "notes", label: "Notes", type: "textarea" },
];

const columns = [
  { label: "Beneficiary", key: "beneficiary_name", className: "px-4 py-2 text-left" },
  { label: "Number", key: "beneficiary_number", className: "px-4 py-2 text-left" },
  { label: "Employer", key: "employer", className: "px-4 py-2 text-left" },
  { label: "Type", key: "opportunity_type", className: "px-4 py-2 text-left" },
  { label: "Placed", key: "placement_date", className: "px-4 py-2 text-left" },
  { label: "Status", key: "status", className: "px-4 py-2 text-left" },
  { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
];

export default function PlacementsPage({ beneficiaries, placements }: { beneficiaries: any[]; placements: any[] }) {
  const [selected, setSelected] = useState<any | null>(null);
  const [open, setOpen] = useState(false);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Placements" />
      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Beneficiary placements</h1>
            <p className="mt-2 text-sm text-slate-600">Track internships, learnerships, apprenticeships, employment, and volunteer placements.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <CustomModelForm
              addButton={{ label: "Add Placement", icon: CirclePlus, className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700" }}
              title="Add Placement"
              description="Link a placement opportunity to a beneficiary."
              fields={fields}
              submitRoute={() => ({ url: "/service-delivery/placements", method: "post" })}
              options={{ beneficiaries }}
            />
            <ServiceDeliveryNav />
          </div>
        </div>
        <CustomTable
          columns={columns}
          data={placements}
          actions={[{ icon: "PencilIcon", onClick: (row) => { setSelected(row); setOpen(true); } }]}
        />
        {selected ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Placement"
            fields={fields}
            initialData={selected}
            submitRoute={() => ({ url: `/service-delivery/placements/${selected.id}`, method: "put" })}
            options={{ beneficiaries }}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
