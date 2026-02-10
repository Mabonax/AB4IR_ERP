export const ProjectTableConfig = {
  columns: [
    { label: "Project", key: "name", className: "px-4 py-2 text-left" },
    { label: "Start Date", key: "start_date", className: "px-4 py-2 text-left" },
    { label: "Program", key: "program_title", className: "px-4 py-2 text-left" },
    { label: "Sponsor", key: "sponsor_name", className: "px-4 py-2 text-left" },
    { label: "Project Manager", key: "project_manager_name", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ],
};
