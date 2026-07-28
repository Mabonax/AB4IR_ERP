export const OrganisationTableConfig = {
  columns: [
    { label: "Organisation", key: "name", className: "px-4 py-2 text-left" },
    { label: "Type", key: "organisation_type", className: "px-4 py-2 text-left" },
    { label: "Registration", key: "registration_number", className: "px-4 py-2 text-left" },
    { label: "Compliance Refs", key: "compliance_refs", className: "px-4 py-2 text-left" },
    { label: "Contact", key: "contact", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
