import { CirclePlus } from "lucide-react";

export const ProgramModelFormConfig = {
  moduleTitle: "Programs",
  title: "Program Form",
  description: "Fill in the details to add or edit a program.",

  addButton: {
    id: "add-program-button",
    label: "Add Program",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "program-title",
      name: "title",
      label: "Title",
      type: "text",
      placeholder: "Enter program title",
      autoFocus: true,
      required: true,
    },
    {
      id: "program-description",
      name: "description",
      label: "Description",
      type: "textarea",
      rows: 3,
      placeholder: "Enter description",
      required: true,
    },
    {
      id: "program-slug",
      name: "slug",
      label: "Slug",
      type: "text",
      placeholder: "Optional slug",
    },
  ],
};