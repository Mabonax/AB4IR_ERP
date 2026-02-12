import { Package, Tag } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

export const assetNavItems: DomainNavItem[] = [
  { label: "Dashboard", href: "/assets", icon: <Package className="h-4 w-4" />, requiredPermissions: ["domain.assets.view", "domain.assets.manage"] },
  { label: "Assets List", href: "/assets/list", requiredPermissions: ["domain.assets.view", "domain.assets.manage"] },
  { label: "Categories", href: "/asset-categories", icon: <Tag className="h-4 w-4" />, requiredPermissions: ["domain.assets.view", "domain.assets.manage"] },
];
