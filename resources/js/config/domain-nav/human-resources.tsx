import { Briefcase, UserCircle } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

export const humanResourcesNavItems: DomainNavItem[] = [
  { label: "HR Dashboard", href: "/human-resources", icon: <Briefcase className="h-4 w-4" />, requiredPermissions: ["domain.human-resources.view", "domain.human-resources.manage"] },
  { label: "Staff", href: "/staff", icon: <UserCircle className="h-4 w-4" />, requiredPermissions: ["domain.staff.view", "domain.staff.manage"] },
  { label: "Leave Management", href: "/leave-requests", icon: <Briefcase className="h-4 w-4" />, requiredPermissions: ["domain.leave.view", "domain.leave.manage"] },
];
