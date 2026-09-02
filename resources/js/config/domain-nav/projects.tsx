import { Briefcase, CalendarCheck2, ClipboardEdit, MapPin, NotebookPen, Users } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

const isProjectDetailUrl = (url: string) => /^\/projects\/\d+(?:\/.*)?$/.test(url);

export const projectNavItems: DomainNavItem[] = [
  {
    label: "Overview",
    href: "/projects",
    icon: <Briefcase className="h-4 w-4" />,
    requiredPermissions: ["domain.projects.view", "domain.projects.manage"],
    isActive: (url) => url === "/projects",
  },
  {
    label: "Projects",
    href: "/projects/list",
    icon: <ClipboardEdit className="h-4 w-4" />,
    requiredPermissions: ["domain.projects.view", "domain.projects.manage"],
    isActive: (url) => url === "/projects/list" || url === "/projects/create" || isProjectDetailUrl(url),
  },
  { label: "Locations", href: "/project-locations", icon: <MapPin className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
  { label: "Enrollments", href: "/project-enrollments", icon: <Users className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
  { label: "Attendance", href: "/projects/attendance-summary", icon: <CalendarCheck2 className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
  { label: "Milestones", href: "/milestone-templates", icon: <NotebookPen className="h-4 w-4" />, requiredPermissions: ["domain.projects.view", "domain.projects.manage"] },
];
