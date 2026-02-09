export const StakeholderTableConfig = {
  columns: [
    { label: "Organization", key: "organization_name", className: "px-4 py-2 text-left" },
    { label: "Stakeholder Name", key: "name", className: "px-4 py-2 text-left" },
    { label: "Email", key: "email", className: "px-4 py-2 text-left" },
    { label: "Contact Number", key: "contact_number", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
