import { CirclePlus } from "lucide-react";

export const CommitteeModelFormConfig = {
  description: "Maintain committee leadership and governance working groups.",
  addButton: {
    id: "add-committee-button",
    label: "Add Committee",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },
  fields: [
    {
      id: "committee-organisation-id",
      name: "organisation_id",
      label: "Organisation",
      type: "select",
      required: true,
      optionsSource: "organisations",
      optionLabel: "name",
      optionValue: "id",
    },
    {
      id: "committee-name",
      name: "name",
      label: "Committee Name",
      type: "text",
      required: true,
      autoFocus: true,
    },
    {
      id: "committee-chairperson-id",
      name: "chairperson_id",
      label: "Chairperson",
      type: "select",
      optionsSource: "users",
      optionLabel: "name",
      optionValue: "id",
    },
    {
      id: "committee-secretary-id",
      name: "secretary_id",
      label: "Secretary",
      type: "select",
      optionsSource: "users",
      optionLabel: "name",
      optionValue: "id",
    },
    {
      id: "committee-status",
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
      id: "committee-description",
      name: "description",
      label: "Description",
      type: "textarea",
    },
  ],
};
