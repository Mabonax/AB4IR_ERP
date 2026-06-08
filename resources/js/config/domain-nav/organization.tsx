import { Building2, FolderOpen } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

export const organizationNavItems: DomainNavItem[] = [
  {
    label: "Organization Profile",
    href: "/organization",
    icon: <Building2 className="h-4 w-4" />,
    requiredPermissions: ["domain.organization.view", "domain.organization.manage"],
  },
  {
    label: "Document Vault",
    href: "/organization/documents",
    icon: <FolderOpen className="h-4 w-4" />,
    requiredPermissions: [],
  },
];
