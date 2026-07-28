export const ComplianceRecordTableConfig = {
  columns: [
    { label: "Organisation", key: "organisation_name", className: "px-4 py-2 text-left" },
    { label: "Title", key: "title", className: "px-4 py-2 text-left" },
    { label: "Area", key: "compliance_area", className: "px-4 py-2 text-left" },
    { label: "Cycle", key: "filing_frequency_label", className: "px-4 py-2 text-left" },
    { label: "Due", key: "due_date", className: "px-4 py-2 text-left" },
    { label: "Owner", key: "owner_name", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status_label", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
