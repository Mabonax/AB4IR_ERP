import { CirclePlus } from "lucide-react";

export const ProjectEnrollmentModelFormConfig = {
  moduleTitle: "Project Enrollments",
  title: "Project Enrollment Form",
  description: "Enroll beneficiaries into projects.",

  addButton: {
    id: "add-project-enrollment-button",
    label: "Enroll Beneficiary",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "project-enrollment-project",
      name: "project_id",
      label: "Project",
      type: "select",
      optionsSource: "projects",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-enrollment-location",
      name: "project_location_id",
      label: "Project Location",
      type: "select",
      optionsSource: "locations",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-enrollment-beneficiary",
      name: "beneficiary_id",
      label: "Beneficiary",
      type: "select",
      optionsSource: "beneficiaries",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-enrollment-status",
      name: "status",
      label: "Status",
      type: "select",
      required: true,
      options: [
        { label: "Enrolled", value: "enrolled" },
        { label: "Completed", value: "completed" },
        { label: "Dropped", value: "dropped" },
      ],
    },
    {
      id: "project-enrollment-date",
      name: "enrolled_at",
      label: "Enrollment Date",
      type: "date",
    },
  ],
};
