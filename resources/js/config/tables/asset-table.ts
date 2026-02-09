export const AssetTableConfig = {
  columns: [
    { label: "Asset Name", key: "name", className: "px-4 py-2 text-left" },
    { label: "Category", key: "category_name", className: "px-4 py-2 text-left" },
    { label: "Type", key: "type", className: "px-4 py-2 text-left" },
    { label: "Serial Number", key: "serial_number", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Assigned To", key: "assigned_to", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
