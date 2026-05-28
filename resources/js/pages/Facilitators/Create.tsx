import { FacilitatorFormPage } from "@/components/facilitator-form-page";
import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Facilitators", href: facilitators.index() },
  { title: "Create", href: facilitators.create() },
];

export default function FacilitatorCreate({ provinces }: { provinces: { id: number; name: string }[] }) {
  return (
    <FacilitatorFormPage
      pageTitle="Create Facilitator"
      title="Create Facilitator"
      description="Create a facilitator profile without forcing all secondary identity fields up front."
      breadcrumbs={breadcrumbs}
      submitLabel="Create Facilitator"
      submitRoute={facilitators.store()}
      provinces={provinces}
      initialData={{
        name: "",
        surname: "",
        email: "",
        cell: "",
        specialization: "",
        province_id: "",
        dob: "",
        id_number: "",
        address: "",
      }}
    />
  );
}
