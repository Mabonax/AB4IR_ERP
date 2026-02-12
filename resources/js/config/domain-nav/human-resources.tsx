import { Briefcase, UserCircle } from "lucide-react";

export const humanResourcesNavItems = [
  { label: "HR Dashboard", href: "/human-resources", icon: <Briefcase className="h-4 w-4" /> },
  { label: "Staff", href: "/staff/list", icon: <UserCircle className="h-4 w-4" /> },
  { label: "Leave Management", href: "/leave-requests", icon: <Briefcase className="h-4 w-4" /> },
];
