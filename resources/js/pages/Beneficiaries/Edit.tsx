import { BeneficiaryFormPage } from "@/components/beneficiary-form-page";
import beneficiaries from "@/routes/beneficiaries";
import { type BreadcrumbItem } from "@/types";

export default function BeneficiaryEdit({
  beneficiary,
  programs,
  members,
  projects,
  provinces,
  projectLocations,
}: {
  beneficiary: any;
  programs: { id: number; title: string }[];
  members: { id: number; name: string; member_type?: string | null; email?: string | null }[];
  projects: { id: number; name: string; program_id?: number | null }[];
  provinces: { id: number; name: string }[];
  projectLocations: { id: number; project_id: number; name: string }[];
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Beneficiaries", href: beneficiaries.index() },
    { title: beneficiary.full_name ?? "Beneficiary File", href: beneficiaries.show(beneficiary.id) },
    { title: "Edit", href: beneficiaries.edit(beneficiary.id) },
  ];

  return (
    <BeneficiaryFormPage
      mode="edit"
      pageTitle="Edit Beneficiary"
      title={`Edit ${beneficiary.full_name ?? "Beneficiary"}`}
      description="Update the beneficiary record, current placement, and any available contact or next-of-kin information."
      breadcrumbs={breadcrumbs}
      submitRoute={beneficiaries.update(beneficiary.id)}
      programs={programs}
      members={members}
      projects={projects}
      provinces={provinces}
      projectLocations={projectLocations}
      backHref={beneficiaries.show(beneficiary.id).url}
      initialData={{
        member_id:
          beneficiary.member_id !== null && beneficiary.member_id !== undefined
            ? String(beneficiary.member_id)
            : "",
        name: beneficiary.name ?? "",
        surname: beneficiary.surname ?? "",
        dob: beneficiary.dob ?? "",
        age:
          beneficiary.age !== null && beneficiary.age !== undefined
            ? String(beneficiary.age)
            : "",
        id_number: beneficiary.id_number ?? "",
        email: beneficiary.email ?? "",
        phone: beneficiary.phone ?? "",
        gender: beneficiary.gender ?? "",
        program_id:
          beneficiary.program_id !== null && beneficiary.program_id !== undefined
            ? String(beneficiary.program_id)
            : "",
        project_id:
          beneficiary.project_id !== null && beneficiary.project_id !== undefined
            ? String(beneficiary.project_id)
            : "",
        project_location_id:
          beneficiary.project_location_id !== null && beneficiary.project_location_id !== undefined
            ? String(beneficiary.project_location_id)
            : "",
        enrolment_date: beneficiary.enrolment_date ?? "",
        exit_date: beneficiary.exit_date ?? "",
        participation_status: beneficiary.participation_status ?? "registered",
        placement_status: beneficiary.placement_status ?? "",
        member_type: beneficiary.member_type ?? "Beneficiary",
        street_address: beneficiary.street_address ?? "",
        address_line_2: beneficiary.address_line_2 ?? "",
        city: beneficiary.city ?? "",
        province_id:
          beneficiary.province_id !== null && beneficiary.province_id !== undefined
            ? String(beneficiary.province_id)
            : "",
        postal_code: beneficiary.postal_code ?? "",
        highest_qualification: beneficiary.highest_qualification ?? "",
        attendance_status: beneficiary.attendance_status ?? "active",
        nok_name: beneficiary.next_of_kin?.name ?? "",
        nok_surname: beneficiary.next_of_kin?.surname ?? "",
        nok_relationship: beneficiary.next_of_kin?.relationship ?? "",
        nok_phone: beneficiary.next_of_kin?.phone ?? "",
        nok_email: beneficiary.next_of_kin?.email ?? "",
      }}
    />
  );
}
