import { CirclePlus } from "lucide-react";

export const AssetModelFormConfig = {
  moduleTitle: "Assets",
  title: "Asset Form",
  description: "Fill in the details to add or edit an asset.",

  addButton: {
    id: "add-asset-button",
    label: "Add Asset",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },

  fields: [
    {
      id: "asset-name",
      name: "name",
      label: "Asset Name",
      type: "text",
      placeholder: "Enter asset name",
      autoFocus: true,
      required: true,
    },
    {
      id: "asset-category",
      name: "asset_category_id",
      label: "Category",
      type: "select",
      optionsSource: "categories",
      optionLabel: "name",
      optionValue: "id",
      required: true,
    },
    {
      id: "asset-type",
      name: "type",
      label: "Type",
      type: "text",
      placeholder: "Laptop, 3D printer, etc",
      required: true,
    },
    {
      id: "asset-model-name",
      name: "model_name",
      label: "Model",
      type: "text",
      placeholder: "ThinkPad T14, EliteBook 840, etc",
      required: true,
    },
    {
      id: "asset-serial",
      name: "serial_number",
      label: "Serial Number",
      type: "text",
      placeholder: "Enter serial number",
    },
    {
      id: "asset-serial-state",
      name: "serial_state",
      label: "Serial State",
      type: "select",
      required: true,
      options: [
        { label: "Recorded", value: "recorded" },
        { label: "Pending", value: "pending" },
        { label: "No Serial", value: "no_serial" },
      ],
    },
    {
      id: "asset-status",
      name: "status",
      label: "Status",
      type: "select",
      required: true,
      options: [
        { label: "Assigned", value: "assigned" },
        { label: "Unassigned", value: "unassigned" },
      ],
    },
  ],
};
