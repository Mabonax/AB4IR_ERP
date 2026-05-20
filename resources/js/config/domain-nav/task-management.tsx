import { ClipboardList, LayoutGrid, LifeBuoy } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const taskManagementNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/task-management",
    icon: <LayoutGrid className="h-4 w-4" />,
    requiredPermissions: ["domain.task-management.view", "domain.task-management.manage"],
  },
  {
    label: "Tasks",
    href: "/task-management/tasks",
    icon: <ClipboardList className="h-4 w-4" />,
    requiredPermissions: ["domain.task-management.view", "domain.task-management.manage"],
  },
  {
    label: "Support Tickets",
    href: "/task-management/tickets",
    icon: <LifeBuoy className="h-4 w-4" />,
    requiredPermissions: ["domain.task-management.view", "domain.task-management.manage"],
  },
];
