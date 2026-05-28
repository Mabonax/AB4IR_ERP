import { FacilitatorFormPage } from "@/components/facilitator-form-page";
import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

export default function FacilitatorEdit({
  facilitator,
  provinces,
}: {
  facilitator: any;
  provinces: { id: number; name: string }[];
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Facilitators", href: facilitators.index() },
    { title: facilitator.full_name ?? "Facilitator", href: facilitators.show(facilitator.id) },
    { title: "Edit", href: facilitators.edit(facilitator.id) },
  ];

  return (
    <FacilitatorFormPage
      pageTitle="Edit Facilitator"
      title={`Edit ${facilitator.full_name ?? "Facilitator"}`}
      description="Update the facilitator profile and linked account details without reopening a modal workflow."
      breadcrumbs={breadcrumbs}
      submitLabel="Save Facilitator"
      submitRoute={facilitators.update(facilitator.id)}
      provinces={provinces}
      backHref={facilitators.show(facilitator.id).url}
      initialData={{
        name: facilitator.name ?? "",
        surname: facilitator.surname ?? "",
        email: facilitator.email ?? "",
        cell: facilitator.cell ?? "",
        specialization: facilitator.specialization ?? "",
        province_id: facilitator.province_id !== null && facilitator.province_id !== undefined ? String(facilitator.province_id) : "",
        dob: facilitator.dob ?? "",
        id_number: facilitator.id_number ?? "",
        address: facilitator.address ?? "",
      }}
    />
  );
}
