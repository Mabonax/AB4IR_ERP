import { BarChart3, MapPinned, Users, Warehouse } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const humanCapitalNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/human-capital/dashboard",
    icon: <BarChart3 className="h-4 w-4" />,
    requiredPermissions: ["domain.human-capital.view", "domain.human-capital.manage", "domain.members.view", "domain.members.manage"],
  },
  {
    label: "Members",
    href: "/members",
    icon: <Users className="h-4 w-4" />,
    requiredPermissions: ["domain.members.view", "domain.members.manage"],
  },
  {
    label: "Geography",
    href: "/geography",
    icon: <MapPinned className="h-4 w-4" />,
    requiredPermissions: ["domain.geography.view", "domain.geography.manage"],
  },
  {
    label: "Reports",
    href: "/human-capital/reports",
    icon: <Warehouse className="h-4 w-4" />,
    requiredPermissions: ["domain.human-capital.view", "domain.human-capital.manage", "domain.reporting.view", "domain.reporting.manage"],
  },
];
