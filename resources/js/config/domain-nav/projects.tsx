import { Briefcase, ClipboardCheck, MapPin, NotebookPen, Users } from "lucide-react";

export const projectNavItems = [
  { label: "Dashboard", href: "/projects", icon: <Briefcase className="h-4 w-4" /> },
  { label: "Projects List", href: "/projects/list" },
  { label: "Facilitator Dashboard", href: "/project-locations/dashboard", icon: <ClipboardCheck className="h-4 w-4" /> },
  { label: "Locations", href: "/project-locations", icon: <MapPin className="h-4 w-4" /> },
  { label: "Enrollments", href: "/project-enrollments", icon: <Users className="h-4 w-4" /> },
  { label: "Milestones", href: "/milestone-templates", icon: <NotebookPen className="h-4 w-4" /> },
];
