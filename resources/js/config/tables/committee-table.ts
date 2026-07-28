export const CommitteeTableConfig = {
  columns: [
    { label: "Committee", key: "name", className: "px-4 py-2 text-left" },
    { label: "Organisation", key: "organisation_name", className: "px-4 py-2 text-left" },
    { label: "Chairperson", key: "chairperson_name", className: "px-4 py-2 text-left" },
    { label: "Secretary", key: "secretary_name", className: "px-4 py-2 text-left" },
    { label: "Members", key: "members_count", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
