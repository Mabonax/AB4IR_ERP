export const ResolutionTableConfig = {
  columns: [
    { label: "Resolution", key: "title", className: "px-4 py-2 text-left" },
    { label: "Number", key: "resolution_number", className: "px-4 py-2 text-left" },
    { label: "Meeting", key: "meeting_title", className: "px-4 py-2 text-left" },
    { label: "Owner", key: "owner_name", className: "px-4 py-2 text-left" },
    { label: "Due Date", key: "due_date", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
