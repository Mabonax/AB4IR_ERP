import { Bot, Database, FileText, Route, ShieldCheck, Wrench } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const intelligenceNavItems: DomainNavItem[] = [
  {
    label: "Overview",
    href: "/intelligence",
    icon: <Bot className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
  {
    label: "Agents",
    href: "/intelligence/agents",
    icon: <Bot className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
  {
    label: "Prompts",
    href: "/intelligence/prompts",
    icon: <FileText className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
  {
    label: "Memory",
    href: "/intelligence/memory",
    icon: <Database className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
  {
    label: "Tools",
    href: "/intelligence/tools",
    icon: <Wrench className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
  {
    label: "Logs",
    href: "/intelligence/tool-logs",
    icon: <ShieldCheck className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
  {
    label: "Routing",
    href: "/intelligence/model-routing",
    icon: <Route className="h-4 w-4" />,
    requiredPermissions: ["domain.intelligence.view", "domain.intelligence.manage"],
  },
];
