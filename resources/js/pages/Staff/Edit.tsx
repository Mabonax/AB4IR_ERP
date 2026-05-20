import { StaffMemberFormPage } from "@/components/staff-member-form-page";
import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

export default function StaffEdit({
  staffMember,
  departments,
  managers,
}: {
  staffMember: { data?: any; id?: number };
  departments: { id: number; name: string; description?: string | null }[];
  managers: { id: number; name: string; department_id?: number | null }[];
}) {
  const data = staffMember?.data ?? staffMember;

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Staff", href: "/staff" },
    { title: `${data.first_name} ${data.last_name}`, href: `/staff/${data.id}/profile` },
    { title: "Edit", href: `/staff/${data.id}/edit` },
  ];

  return (
    <StaffMemberFormPage
      mode="edit"
      pageTitle="Edit Staff Member"
      title={`Edit ${data.first_name} ${data.last_name}`}
      description="Update the staff profile, assignment details, and emergency contact information."
      breadcrumbs={breadcrumbs}
      submitRoute={staff.update}
      routeParams={data.id}
      currentStaffId={data.id}
      departments={departments}
      managers={managers}
      backHref={`/staff/${data.id}/profile`}
      initialData={{
        "staff.first_name": data.first_name ?? "",
        "staff.last_name": data.last_name ?? "",
        "staff.email": data.email ?? "",
        "staff.phone": data.phone ?? "",
        "staff.employee_number": data.employee_number ?? "",
        "staff.start_date": data.start_date ?? "",
        "staff.manager_id":
          data.manager_id !== null && data.manager_id !== undefined
            ? String(data.manager_id)
            : "",
        "staff.is_ceo": data.is_ceo ? "1" : "0",
        "staff.is_board_member": data.is_board_member ? "1" : "0",
        "staff.is_manager": data.is_manager ? "1" : "0",
        "staff.is_intern": data.is_intern ? "1" : "0",
        "staff.intern_sponsor_name": data.intern_sponsor_name ?? "",
        "staff.internship_start_date": data.internship_start_date ?? "",
        "staff.internship_end_date": data.internship_end_date ?? "",
        "staff.department_id":
          data.department_id !== null && data.department_id !== undefined
            ? String(data.department_id)
            : "",
        "staff.status": data.status ?? "active",
        "next_of_kin.full_name": data.next_of_kin?.full_name ?? "",
        "next_of_kin.relationship": data.next_of_kin?.relationship ?? "",
        "next_of_kin.phone": data.next_of_kin?.phone ?? "",
        "next_of_kin.email": data.next_of_kin?.email ?? "",
      }}
    />
  );
}
