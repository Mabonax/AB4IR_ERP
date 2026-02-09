import { CirclePlus } from "lucide-react";

export const FacilitatorModelFormConfig = {
  moduleTitle: "Facilitators",
  title: "Facilitator Form",
  description: "Fill in the details to add or edit a facilitator.",

  addButton: {
    id: "add-facilitator-button",
    label: "Add Facilitator",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "facilitator-name",
      name: "name",
      label: "First Name",
      type: "text",
      placeholder: "Enter first name",
      autoFocus: true,
      required: true,
    },
    {
      id: "facilitator-surname",
      name: "surname",
      label: "Surname",
      type: "text",
      placeholder: "Enter surname",
      required: true,
    },
    {
      id: "facilitator-dob",
      name: "dob",
      label: "Date of Birth",
      type: "date",
      required: true,
    },
    {
      id: "facilitator-id-number",
      name: "id_number",
      label: "ID Number",
      type: "text",
      placeholder: "Enter ID number",
      required: true,
    },
    {
      id: "facilitator-address",
      name: "address",
      label: "Address",
      type: "textarea",
      rows: 2,
      required: true,
    },
    {
      id: "facilitator-email",
      name: "email",
      label: "Email",
      type: "email",
      placeholder: "Enter email address",
      required: true,
    },
    {
      id: "facilitator-cell",
      name: "cell",
      label: "Cell",
      type: "tel",
      placeholder: "Enter cell number",
      required: true,
    },
    {
      id: "facilitator-specialization",
      name: "specialization",
      label: "Specialization",
      type: "text",
      placeholder: "Enter specialization",
      required: true,
    },
  ],
};
