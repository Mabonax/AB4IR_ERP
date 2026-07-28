import { Building2, CalendarDays, FileCheck2, Landmark } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const governanceNavItems: DomainNavItem[] = [
  {
    label: "Governance",
    href: "/governance",
    icon: <Landmark className="h-4 w-4" />,
    requiredPermissions: ["domain.governance.view", "domain.governance.manage"],
  },
  {
    label: "Committees",
    href: "/committees",
    icon: <Building2 className="h-4 w-4" />,
    requiredPermissions: ["domain.committees.view", "domain.committees.manage"],
  },
  {
    label: "Meetings",
    href: "/meetings",
    icon: <CalendarDays className="h-4 w-4" />,
    requiredPermissions: ["domain.meetings.view", "domain.meetings.manage"],
  },
  {
    label: "Resolutions",
    href: "/resolutions",
    icon: <FileCheck2 className="h-4 w-4" />,
    requiredPermissions: ["domain.resolutions.view", "domain.resolutions.manage"],
  },
];
