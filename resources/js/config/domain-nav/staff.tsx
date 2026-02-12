import { Building2, Briefcase, UserCircle } from "lucide-react";

export const staffNavItems = [
  { label: "HR Dashboard", href: "/human-resources", icon: <Briefcase className="h-4 w-4" /> },
  { label: "Dashboard", href: "/staff", icon: <UserCircle className="h-4 w-4" /> },,
  { label: "Staff List", href: "/staff/list" },
  { label: "Departments", href: "/staff-departments", icon: <Building2 className="h-4 w-4" /> },
];
