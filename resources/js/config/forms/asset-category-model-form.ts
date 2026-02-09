import { CirclePlus } from "lucide-react";

export const AssetCategoryModelFormConfig = {
  moduleTitle: "Asset Categories",
  title: "Asset Category Form",
  description: "Fill in the details to add or edit an asset category.",

  addButton: {
    id: "add-asset-category-button",
    label: "Add Category",
    className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "asset-category-name",
      name: "name",
      label: "Category Name",
      type: "text",
      placeholder: "Enter category name",
      autoFocus: true,
      required: true,
    },
    {
      id: "asset-category-description",
      name: "description",
      label: "Description",
      type: "textarea",
      placeholder: "Enter description",
    },
  ],
};
