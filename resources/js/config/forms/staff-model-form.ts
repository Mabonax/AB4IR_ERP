import { CirclePlus } from "lucide-react";

export const StaffModelFormConfig = {
  moduleTitle: "Staff",
  title: "Staff Form",
  description: "Fill in the details to add or edit a staff member.",

  addButton: {
    id: "add-staff-button",
    label: "Add Staff Member",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    // =========================
    // STAFF DETAILS
    // =========================
    {
      id: "staff-first-name",
      name: "staff.first_name",
      label: "First Name",
      type: "text",
      placeholder: "Enter first name",
      autoFocus: true,
      required: true,
    },
    {
      id: "staff-last-name",
      name: "staff.last_name",
      label: "Last Name",
      type: "text",
      placeholder: "Enter last name",
      required: true,
    },
    {
      id: "staff-email",
      name: "staff.email",
      label: "Email",
      type: "email",
      placeholder: "Enter email address",
      required: true,
    },
    {
      id: "staff-phone",
      name: "staff.phone",
      label: "Phone",
      type: "tel",
      placeholder: "Enter phone number",
    },
    {
      id: "staff-employee-number",
      name: "staff.employee_number",
      label: "Employee Number",
      type: "text",
      placeholder: "Enter employee number",
      required: true,
    },
    {
      id: "staff-department",
      name: "staff.department_id",
      label: "Department",
      type: "select",
      optionsSource: "departments",
      optionLabel: "name",
      optionValue: "id",
    },
    {
      id: "staff-status",
      name: "staff.status",
      label: "Status",
      type: "select",
      required: true,
      options: [
        { label: "Active", value: "active" },
        { label: "Inactive", value: "inactive" },
      ],
    },

    // =========================
    // NEXT OF KIN DETAILS
    // =========================
    {
      id: "next-of-kin-full-name",
      name: "next_of_kin.full_name",
      label: "Next of Kin Full Name",
      type: "text",
      placeholder: "Enter full name",
      required: true,
    },
    {
      id: "next-of-kin-relationship",
      name: "next_of_kin.relationship",
      label: "Relationship",
      type: "text",
      placeholder: "Enter relationship",
      required: true,
    },
    {
      id: "next-of-kin-phone",
      name: "next_of_kin.phone",
      label: "Next of Kin Phone",
      type: "tel",
      placeholder: "Enter phone number",
      required: true,
    },
    {
      id: "next-of-kin-email",
      name: "next_of_kin.email",
      label: "Next of Kin Email",
      type: "email",
      placeholder: "Enter email address",
    },
  ],
};
