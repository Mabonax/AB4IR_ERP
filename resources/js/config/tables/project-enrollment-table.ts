export const ProjectEnrollmentTableConfig = {
  columns: [
    { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
    { label: "Beneficiary", key: "beneficiary_name", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Enrolled At", key: "enrolled_at", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
