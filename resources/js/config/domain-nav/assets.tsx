import { Package, Tag } from "lucide-react";

export const assetNavItems = [
  { label: "Dashboard", href: "/assets", icon: <Package className="h-4 w-4" /> },
  { label: "Assets List", href: "/assets/list" },
  { label: "Categories", href: "/asset-categories", icon: <Tag className="h-4 w-4" /> },
];
