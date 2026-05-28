import { Briefcase, CalendarCheck2, ClipboardCheck, MapPin, NotebookPen, Users } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

const isProjectDetailUrl = (url: string) => /^\/projects\/\d+(?:\/.*)?$/.test(url);

export const projectNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/projects",
    icon: <Briefcase className="h-4 w-4" />,
    requiredPermissions: ["domain.projects.view", "domain.projects.manage"],
    isActive: (url) => url === "/projects",
  },
  {
    label: "Projects List",
    href: "/projects/list",
    requiredPermissions: ["domain.projects.view", "domain.projects.manage"],
    isActive: (url) => url === "/projects/list" || url === "/projects/create" || isProjectDetailUrl(url),
  },
  { label: "Facilitator Dashboard", href: "/project-locations/dashboard", icon: <ClipboardCheck className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage", "project-activities.view"] },
  { label: "Locations", href: "/project-locations", icon: <MapPin className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
  { label: "Enrollments", href: "/project-enrollments", icon: <Users className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
  { label: "Attendance Summary", href: "/projects/attendance-summary", icon: <CalendarCheck2 className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
  { label: "Milestones", href: "/milestone-templates", icon: <NotebookPen className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
];
