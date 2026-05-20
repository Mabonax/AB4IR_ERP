import { ReceiptText, Wallet } from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export const financeNavItems: DomainNavItem[] = [
  {
    label: "Travel Claims",
    href: "/finance/travel-claims",
    icon: <Wallet className="h-4 w-4" />,
    requiredPermissions: ["domain.finance.view", "domain.finance.manage", "travel-claims.submit"],
  },
  {
    label: "New Claim",
    href: "/finance/travel-claims/create",
    icon: <ReceiptText className="h-4 w-4" />,
    requiredPermissions: ["travel-claims.submit"],
  },
];
