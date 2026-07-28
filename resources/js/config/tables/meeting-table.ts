export const MeetingTableConfig = {
  columns: [
    { label: "Meeting", key: "title", className: "px-4 py-2 text-left" },
    { label: "Number", key: "meeting_number", className: "px-4 py-2 text-left" },
    { label: "Organisation", key: "organisation_name", className: "px-4 py-2 text-left" },
    { label: "Committee", key: "committee_name", className: "px-4 py-2 text-left" },
    { label: "Date", key: "meeting_date", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
