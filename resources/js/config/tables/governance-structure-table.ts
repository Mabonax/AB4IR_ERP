export const GovernanceStructureTableConfig = {
  columns: [
    { label: "Structure", key: "name", className: "px-4 py-2 text-left" },
    { label: "Organisation", key: "organisation_name", className: "px-4 py-2 text-left" },
    { label: "Description", key: "description", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
