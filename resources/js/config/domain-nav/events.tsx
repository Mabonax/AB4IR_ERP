import { CalendarRange } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const eventNavItems: DomainNavItem[] = [
  {
    label: "Events",
    href: "/events",
    icon: <CalendarRange className="h-4 w-4" />,
    requiredPermissions: ["domain.events.view", "domain.events.manage"],
  },
];
