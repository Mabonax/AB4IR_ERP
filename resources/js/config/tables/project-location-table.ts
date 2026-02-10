export const ProjectLocationTableConfig = {
  columns: [
    { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
    { label: "Province", key: "location", className: "px-4 py-2 text-left" },
    { label: "Facilitator", key: "facilitator_name", className: "px-4 py-2 text-left" },
    { label: "Beneficiaries", key: "beneficiary_count", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
