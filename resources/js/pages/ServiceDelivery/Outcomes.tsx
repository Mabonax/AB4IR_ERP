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
  { title: "Outcomes", href: "/service-delivery/outcomes" },
];

const fields = [
  { id: "outcome-program", name: "program_id", label: "Programme", type: "select", optionsSource: "programs", optionLabel: "title", optionValue: "id", required: true },
  { id: "outcome-name", name: "name", label: "Outcome", type: "text", required: true },
  { id: "outcome-target", name: "target", label: "Target", type: "number", required: true },
  { id: "outcome-actual", name: "actual", label: "Actual", type: "number", required: true },
  { id: "outcome-period", name: "reporting_period", label: "Reporting Period", type: "text", required: true },
];

const columns = [
  { label: "Programme", key: "program_title", className: "px-4 py-2 text-left" },
  { label: "Outcome", key: "name", className: "px-4 py-2 text-left" },
  { label: "Target", key: "target", className: "px-4 py-2 text-left" },
  { label: "Actual", key: "actual", className: "px-4 py-2 text-left" },
  { label: "Period", key: "reporting_period", className: "px-4 py-2 text-left" },
  { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
];

export default function OutcomesPage({ programs, outcomes }: { programs: any[]; outcomes: any[] }) {
  const [selected, setSelected] = useState<any | null>(null);
  const [open, setOpen] = useState(false);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Programme Outcomes" />
      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Programme outcomes</h1>
            <p className="mt-2 text-sm text-slate-600">Track targets, actuals, and reporting periods for programme achievements.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <CustomModelForm
              addButton={{ label: "Add Outcome", icon: CirclePlus, className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700" }}
              title="Add Programme Outcome"
              description="Capture a programme achievement target and actual performance."
              fields={fields}
              submitRoute={() => ({ url: "/service-delivery/outcomes", method: "post" })}
              options={{ programs }}
            />
            <ServiceDeliveryNav />
          </div>
        </div>
        <CustomTable
          columns={columns}
          data={outcomes}
          actions={[{ icon: "PencilIcon", onClick: (row) => { setSelected(row); setOpen(true); } }]}
        />
        {selected ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Programme Outcome"
            fields={fields}
            initialData={selected}
            submitRoute={() => ({ url: `/service-delivery/outcomes/${selected.id}`, method: "put" })}
            options={{ programs }}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
