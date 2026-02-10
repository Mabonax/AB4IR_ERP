import { CirclePlus } from "lucide-react";

export const MilestoneTemplateModelFormConfig = {
  moduleTitle: "Milestone Templates",
  title: "Milestone Template Form",
  description: "Create or edit milestone templates.",

  addButton: {
    id: "add-milestone-template-button",
    label: "Add Milestone",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "milestone-title",
      name: "title",
      label: "Title",
      type: "text",
      placeholder: "Unit standard title",
      autoFocus: true,
      required: true,
    },
    {
      id: "milestone-description",
      name: "description",
      label: "Description",
      type: "textarea",
      placeholder: "Describe the milestone",
    },
    {
      id: "milestone-order",
      name: "sort_order",
      label: "Order",
      type: "number",
      placeholder: "0",
    },
    {
      id: "milestone-max-score",
      name: "max_score",
      label: "Max Score",
      type: "number",
      placeholder: "100",
    },
  ],
};
