import { BeneficiaryFormPage } from "@/components/beneficiary-form-page";
import beneficiaries from "@/routes/beneficiaries";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Beneficiaries", href: beneficiaries.index() },
  { title: "Create", href: beneficiaries.create() },
];

export default function BeneficiaryCreate({
  programs,
  projects,
  provinces,
  projectLocations,
}: {
  programs: { id: number; title: string }[];
  projects: { id: number; name: string; program_id?: number | null }[];
  provinces: { id: number; name: string }[];
  projectLocations: { id: number; project_id: number; name: string }[];
}) {
  return (
    <BeneficiaryFormPage
      mode="create"
      pageTitle="Create Beneficiary"
      title="Create Beneficiary"
      description="Register a beneficiary record with placement details, contact information, and optional next-of-kin support data."
      breadcrumbs={breadcrumbs}
      submitRoute={beneficiaries.store()}
      programs={programs}
      projects={projects}
      provinces={provinces}
      projectLocations={projectLocations}
      initialData={{
        name: "",
        surname: "",
        dob: "",
        age: "",
        id_number: "",
        email: "",
        phone: "",
        gender: "",
        project_id: "",
        project_location_id: "",
        street_address: "",
        address_line_2: "",
        city: "",
        province_id: "",
        postal_code: "",
        highest_qualification: "",
        attendance_status: "active",
        nok_name: "",
        nok_surname: "",
        nok_relationship: "",
        nok_phone: "",
        nok_email: "",
      }}
    />
  );
}
