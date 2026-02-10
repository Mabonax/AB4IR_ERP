import { CirclePlus } from "lucide-react";

export const ProjectModelFormConfig = {
  moduleTitle: "Projects",
  title: "Project Form",
  description: "Fill in the details to add or edit a project.",

  addButton: {
    id: "add-project-button",
    label: "Add Project",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "project-name",
      name: "name",
      label: "Project Name",
      type: "text",
      placeholder: "Enter project name",
      autoFocus: true,
      required: true,
    },
    {
      id: "project-program",
      name: "program_id",
      label: "Program",
      type: "select",
      optionsSource: "programs",
      optionLabel: "title",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-sponsor",
      name: "sponsor_stakeholder_id",
      label: "Sponsor (Stakeholder)",
      type: "select",
      optionsSource: "stakeholders",
      optionLabel: "name",
      optionValue: "id",
    },
    {
      id: "project-manager",
      name: "project_manager_id",
      label: "Project Manager",
      type: "select",
      optionsSource: "staffMembers",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "project-description",
      name: "description",
      label: "Description",
      type: "textarea",
      placeholder: "Enter description",
    },
  ],
};
