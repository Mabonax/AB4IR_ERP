import { BarChart3, FilePlus2, FileStack, FolderOpenDot, LayoutGrid, Library, Send, Workflow } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const marketingNavItems: DomainNavItem[] = [
  {
    label: "Dashboard",
    href: "/marketing",
    icon: <LayoutGrid className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Requests",
    href: "/marketing/requests",
    icon: <FileStack className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Create Request",
    href: "/marketing/requests/create",
    icon: <FilePlus2 className="h-4 w-4" />,
    requiredPermissions: ["marketing.requests.create"],
  },
  {
    label: "Workspace",
    href: "/marketing/deliverables/workspace",
    icon: <Workflow className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Approvals",
    href: "/marketing/approvals",
    icon: <BarChart3 className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Assets",
    href: "/marketing/assets",
    icon: <Library className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Publications",
    href: "/marketing/publications",
    icon: <Send className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
  {
    label: "Jobs",
    href: "/marketing/jobs",
    icon: <FolderOpenDot className="h-4 w-4" />,
    requiredPermissions: ["domain.marketing.view", "domain.marketing.manage"],
  },
];
