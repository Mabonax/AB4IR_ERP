import { CirclePlus } from "lucide-react";

export const GovernanceStructureModelFormConfig = {
  description: "Track formal governance bodies across the organisation.",
  addButton: {
    id: "add-governance-structure-button",
    label: "Add Governance Structure",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },
  fields: [
    {
      id: "governance-organisation-id",
      name: "organisation_id",
      label: "Organisation",
      type: "select",
      required: true,
      optionsSource: "organisations",
      optionLabel: "name",
      optionValue: "id",
    },
    {
      id: "governance-name",
      name: "name",
      label: "Structure Name",
      type: "text",
      required: true,
      autoFocus: true,
    },
    {
      id: "governance-status",
      name: "status",
      label: "Status",
      type: "select",
      required: true,
      options: [
        { label: "Active", value: "active" },
        { label: "Inactive", value: "inactive" },
        { label: "Draft", value: "draft" },
      ],
    },
    {
      id: "governance-description",
      name: "description",
      label: "Description",
      type: "textarea",
    },
  ],
};
