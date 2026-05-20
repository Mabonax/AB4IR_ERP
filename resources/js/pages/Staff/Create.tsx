import { StaffMemberFormPage } from "@/components/staff-member-form-page";
import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff", href: "/staff" },
  { title: "Create", href: "/staff/create" },
];

export default function StaffCreate({
  departments,
  managers,
  selectedDepartmentId,
}: {
  departments: { id: number; name: string; description?: string | null }[];
  managers: { id: number; name: string; department_id?: number | null }[];
  selectedDepartmentId: number | null;
}) {
  return (
    <StaffMemberFormPage
      mode="create"
      pageTitle="Create Staff Member"
      title="Create Staff Member"
      description="Register a new staff member with the correct department, reporting line, and next-of-kin details."
      breadcrumbs={breadcrumbs}
      submitRoute={staff.store}
      departments={departments}
      managers={managers}
      initialData={{
        "staff.first_name": "",
        "staff.last_name": "",
        "staff.email": "",
        "staff.phone": "",
        "staff.employee_number": "",
        "staff.start_date": "",
        "staff.manager_id": "",
        "staff.is_ceo": "0",
        "staff.is_board_member": "0",
        "staff.is_manager": "0",
        "staff.is_intern": "0",
        "staff.intern_sponsor_name": "",
        "staff.internship_start_date": "",
        "staff.internship_end_date": "",
        "staff.department_id": selectedDepartmentId ? String(selectedDepartmentId) : "",
        "staff.status": "active",
        "next_of_kin.full_name": "",
        "next_of_kin.relationship": "",
        "next_of_kin.phone": "",
        "next_of_kin.email": "",
      }}
    />
  );
}
