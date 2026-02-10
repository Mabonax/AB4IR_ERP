import { CirclePlus } from "lucide-react";

export const StaffDepartmentModelFormConfig = {
  moduleTitle: "Staff Departments",
  title: "Department Form",
  description: "Fill in the details to add or edit a department.",

  addButton: {
    id: "add-department-button",
    label: "Add Department",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "department-name",
      name: "name",
      label: "Department Name",
      type: "text",
      placeholder: "Enter department name",
      autoFocus: true,
      required: true,
    },
    {
      id: "department-description",
      name: "description",
      label: "Description",
      type: "textarea",
      placeholder: "Enter description",
    },
  ],
};
