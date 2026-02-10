import { Briefcase, MapPin, Users } from "lucide-react";

export const projectNavItems = [
  { label: "Dashboard", href: "/projects", icon: <Briefcase className="h-4 w-4" /> },
  { label: "Projects List", href: "/projects/list" },
  { label: "Locations", href: "/project-locations", icon: <MapPin className="h-4 w-4" /> },
  { label: "Enrollments", href: "/project-enrollments", icon: <Users className="h-4 w-4" /> },
];
