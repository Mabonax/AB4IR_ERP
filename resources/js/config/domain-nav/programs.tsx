import { BarChart3 } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

export const programNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/programs",
    icon: <BarChart3 className="h-4 w-4" />,
    requiredPermissions: ["domain.programs.view", "domain.programs.manage"],
    isActive: (url) => url === "/programs",
  },
];
