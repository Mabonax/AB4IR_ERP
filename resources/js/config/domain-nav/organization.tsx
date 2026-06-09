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
    label: "Official Vault",
    href: "/organization/documents",
    icon: <FolderOpen className="h-4 w-4" />,
    requiredPermissions: ["domain.organization.view", "domain.organization.manage"],
  },
  {
    label: "Working Library",
    href: "/organization/document-library",
    icon: <FolderOpen className="h-4 w-4" />,
    requiredPermissions: [
      "domain.organization.view",
      "domain.organization.manage",
      "domain.programs.view",
      "domain.programs.manage",
      "domain.projects.view",
      "domain.projects.manage",
      "domain.beneficiaries.view",
      "domain.beneficiaries.manage",
      "domain.stakeholders.view",
      "domain.stakeholders.manage",
      "domain.human-resources.view",
      "domain.human-resources.manage",
    ],
  },
];
