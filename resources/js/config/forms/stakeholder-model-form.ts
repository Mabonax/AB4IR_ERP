import { CirclePlus } from "lucide-react";

export const StakeholderModelFormConfig = {
  moduleTitle: "Stakeholders",
  title: "Stakeholder Form",
  description: "Fill in the details to add or edit a stakeholder.",

  addButton: {
    id: "add-stakeholder-button",
    label: "Add Stakeholder",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    // =========================
    // STAKEHOLDER DETAILS
    // =========================
    {
      id: "stakeholder-organization-name",
      name: "stakeholder.organization_name",
      label: "Organization",
      type: "text",
      placeholder: "Enter organization name",
      autoFocus: true,
      required: true,
    },
    {
      id: "stakeholder-name",
      name: "stakeholder.name",
      label: "Stakeholder Name",
      type: "text",
      placeholder: "Enter stakeholder name",
      required: true,
    },
    {
      id: "stakeholder-email",
      name: "stakeholder.email",
      label: "Email",
      type: "email",
      placeholder: "Enter email address",
      required: true,
    },
    {
      id: "stakeholder-contact-number",
      name: "stakeholder.contact_number",
      label: "Contact Number",
      type: "tel",
      placeholder: "Enter contact number",
      required: true,
    },
    {
      id: "stakeholder-status",
      name: "stakeholder.status",
      label: "Status",
      type: "select",
      required: true,
      options: [
        { label: "Active", value: "active" },
        { label: "Inactive", value: "inactive" },
      ],
    },

    // =========================
    // CONTACT PERSON DETAILS
    // =========================
    {
      id: "contact-full-name",
      name: "contact.full_name",
      label: "Contact Full Name",
      type: "text",
      placeholder: "Enter full name",
      required: true,
    },
    {
      id: "contact-email",
      name: "contact.email",
      label: "Contact Email",
      type: "email",
      placeholder: "Enter contact email",
    },
    {
      id: "contact-contact-number",
      name: "contact.contact_number",
      label: "Contact Number",
      type: "tel",
      placeholder: "Enter contact number",
      required: true,
    },
    {
      id: "contact-position",
      name: "contact.position",
      label: "Position",
      type: "text",
      placeholder: "Enter position",
    },
  ],
};
