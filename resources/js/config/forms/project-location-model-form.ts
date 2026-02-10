import { CirclePlus } from "lucide-react";

export const ProjectLocationModelFormConfig = {
  moduleTitle: "Project Locations",
  title: "Project Location Form",
  description: "Fill in the details to add or edit a project location.",

  addButton: {
    id: "add-project-location-button",
    label: "Add Project Location",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "project-location-project",
      name: "project_id",
      label: "Project",
      type: "select",
      optionsSource: "projects",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-location-facilitator",
      name: "facilitator_id",
      label: "Facilitator",
      type: "select",
      optionsSource: "facilitators",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-location-province",
      name: "province_id",
      label: "Province",
      type: "select",
      optionsSource: "provinces",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
  ],
};
