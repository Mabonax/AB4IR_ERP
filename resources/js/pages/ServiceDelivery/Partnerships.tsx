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
  { title: "Partnerships", href: "/service-delivery/partnerships" },
];

const fields = [
  { id: "partner-organisation", name: "organisation", label: "Organisation", type: "text", required: true },
  { id: "partner-contact-person", name: "contact_person", label: "Contact Person", type: "text" },
  { id: "partner-contact-email", name: "contact_email", label: "Contact Email", type: "email" },
  { id: "partner-contact-phone", name: "contact_phone", label: "Contact Details", type: "text" },
  { id: "partner-type", name: "partnership_type", label: "Partnership Type", type: "select", options: [
    { label: "Government", value: "government" },
    { label: "Private Sector", value: "private_sector" },
    { label: "NGO", value: "ngo" },
    { label: "Academic Institution", value: "academic_institution" },
    { label: "Donor", value: "donor" },
  ] },
  { id: "partner-programs", name: "program_ids", label: "Programmes Supported", type: "select", multiple: true, optionsSource: "programs", optionLabel: "title", optionValue: "id" },
  { id: "partner-status", name: "status", label: "Status", type: "text" },
];

const columns = [
  { label: "Organisation", key: "organisation", className: "px-4 py-2 text-left" },
  { label: "Contact", key: "contact_person", className: "px-4 py-2 text-left" },
  { label: "Type", key: "partnership_type", className: "px-4 py-2 text-left" },
  { label: "Programmes", key: "program_names", className: "px-4 py-2 text-left", render: (row: any) => row.program_names?.join(", ") || "-" },
  { label: "Status", key: "status", className: "px-4 py-2 text-left" },
  { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
];

export default function PartnershipsPage({ programs, partnerships }: { programs: any[]; partnerships: any[] }) {
  const [selected, setSelected] = useState<any | null>(null);
  const [open, setOpen] = useState(false);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Partnerships" />
      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Programme partnerships</h1>
            <p className="mt-2 text-sm text-slate-600">Track supporting organisations and the programmes they contribute to.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <CustomModelForm
              addButton={{ label: "Add Partnership", icon: CirclePlus, className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700" }}
              title="Add Partnership"
              description="Capture a delivery partner and supported programmes."
              fields={fields}
              submitRoute={() => ({ url: "/service-delivery/partnerships", method: "post" })}
              options={{ programs }}
            />
            <ServiceDeliveryNav />
          </div>
        </div>
        <CustomTable
          columns={columns}
          data={partnerships}
          actions={[{ icon: "PencilIcon", onClick: (row) => { setSelected(row); setOpen(true); } }]}
        />
        {selected ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Partnership"
            fields={fields}
            initialData={selected}
            submitRoute={() => ({ url: `/service-delivery/partnerships/${selected.id}`, method: "put" })}
            options={{ programs }}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
