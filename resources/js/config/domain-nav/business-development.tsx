import { LayoutGrid, FileText, Users, ClipboardCheck } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const businessDevelopmentNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/business-development",
    icon: <LayoutGrid className="h-4 w-4" />,
    requiredPermissions: ["domain.business-development.view", "domain.business-development.manage"],
  },
  {
    label: "Applications",
    href: "/business-development/applications",
    icon: <FileText className="h-4 w-4" />,
    requiredPermissions: ["domain.business-development.view", "domain.business-development.manage"],
  },
  {
    label: "Incubatees",
    href: "/business-development/incubatees",
    icon: <Users className="h-4 w-4" />,
    requiredPermissions: ["domain.business-development.view", "domain.business-development.manage"],
  },
  {
    label: "Adjudications",
    href: "/business-development/adjudications",
    icon: <ClipboardCheck className="h-4 w-4" />,
    requiredPermissions: ["domain.business-development.view", "domain.business-development.manage"],
  },
];
