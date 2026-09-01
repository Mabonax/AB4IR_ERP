import { CirclePlus } from "lucide-react";

export const ProgramModelFormConfig = {
  moduleTitle: "Programs",
  title: "Program Form",
  description: "Fill in the details to add a new program to the system.",

  addButton: {
    id: "add-program-button",
    label: "Add Program",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
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
      placeholder: "Enter a brief description of the program",
      required: true,
    },
    {
      id: "program-slug",
      name: "slug",
      label: "Slug",
      type: "text",
      placeholder: "Enter program slug (URL-friendly)",
      required: true,
    },
  ],
};
