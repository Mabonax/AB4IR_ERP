export const ProjectEnrollmentTableConfig = {
  columns: [
    { label: "Project", key: "name", className: "px-4 py-2 text-left" },
    { label: "Start Date", key: "start_date", className: "px-4 py-2 text-left" },
    { label: "Locations", key: "locations_count", className: "px-4 py-2 text-left" },
    { label: "Beneficiaries", key: "beneficiary_count", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
