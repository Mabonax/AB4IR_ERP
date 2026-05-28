import { FolderOpenDot, LayoutGrid } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const marketingNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/marketing",
    icon: <LayoutGrid className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Jobs",
    href: "/marketing/jobs",
    icon: <FolderOpenDot className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
];
