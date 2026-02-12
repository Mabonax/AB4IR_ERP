import { KeyRound, ShieldCheck, Users } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

export const accessControlNavItems: DomainNavItem[] = [
  {
    label: "Roles",
    href: "/access-control/roles",
    icon: <ShieldCheck className="h-4 w-4" />,
    requiredPermissions: ["roles.view", "roles.create", "roles.update", "roles.delete"],
  },
  {
    label: "Permissions",
    href: "/access-control/permissions",
    icon: <KeyRound className="h-4 w-4" />,
    requiredPermissions: ["permissions.view", "permissions.create", "permissions.update", "permissions.delete"],
  },
  {
    label: "Assignments",
    href: "/access-control/assignments",
    icon: <Users className="h-4 w-4" />,
    requiredPermissions: ["assignments.manage"],
  },
];
