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
  { title: "Attendance", href: "/service-delivery/attendance" },
];

const fields = [
  { id: "attendance-member", name: "member_id", label: "Member", type: "select", optionsSource: "members", optionLabel: "name", optionValue: "id", required: true },
  { id: "attendance-beneficiary", name: "beneficiary_id", label: "Beneficiary", type: "select", optionsSource: "beneficiaries", optionLabel: "name", optionValue: "id" },
  { id: "attendance-program", name: "program_id", label: "Programme", type: "select", optionsSource: "programs", optionLabel: "title", optionValue: "id" },
  { id: "attendance-project", name: "project_id", label: "Project", type: "select", optionsSource: "projects", optionLabel: "name", optionValue: "id" },
  { id: "attendance-activity", name: "project_activity_id", label: "Activity", type: "select", optionsSource: "activities", optionLabel: "name", optionValue: "id" },
  { id: "attendance-type", name: "attendance_type", label: "Attendance Type", type: "select", options: [
    { label: "Workshop", value: "workshop" },
    { label: "Training", value: "training" },
    { label: "Event", value: "event" },
    { label: "Meeting", value: "meeting" },
  ] },
  { id: "attendance-date", name: "attendance_date", label: "Date", type: "date", required: true },
  { id: "attendance-status", name: "attendance_status", label: "Attendance Status", type: "text", required: true },
];

const columns = [
  { label: "Member", key: "member_name", className: "px-4 py-2 text-left" },
  { label: "Programme", key: "program_title", className: "px-4 py-2 text-left" },
  { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
  { label: "Activity", key: "project_activity_name", className: "px-4 py-2 text-left" },
  { label: "Type", key: "attendance_type", className: "px-4 py-2 text-left" },
  { label: "Date", key: "attendance_date", className: "px-4 py-2 text-left" },
  { label: "Status", key: "attendance_status", className: "px-4 py-2 text-left" },
  { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
];

export default function AttendancePage(props: any) {
  const [selected, setSelected] = useState<any | null>(null);
  const [open, setOpen] = useState(false);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Attendance" />
      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Service attendance</h1>
            <p className="mt-2 text-sm text-slate-600">Capture workshop, training, event, and meeting attendance linked to programmes and projects.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <CustomModelForm
              addButton={{ label: "Add Attendance", icon: CirclePlus, className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700" }}
              title="Add Attendance Record"
              description="Capture service delivery attendance for a member or beneficiary."
              fields={fields}
              submitRoute={() => ({ url: "/service-delivery/attendance", method: "post" })}
              options={props}
            />
            <ServiceDeliveryNav />
          </div>
        </div>
        <CustomTable
          columns={columns}
          data={props.attendance}
          actions={[{ icon: "PencilIcon", onClick: (row) => { setSelected(row); setOpen(true); } }]}
        />
        {selected ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Attendance Record"
            fields={fields}
            initialData={selected}
            submitRoute={() => ({ url: `/service-delivery/attendance/${selected.id}`, method: "put" })}
            options={props}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
