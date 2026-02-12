import { KeyRound, Lock, Palette, UserCircle, CalendarDays } from "lucide-react";
import { type DomainNavItem } from "@/components/domain-nav";

export const settingsNavItems: DomainNavItem[] = [
  { label: "Profile", href: "/settings/profile", icon: <UserCircle className="h-4 w-4" />, requiredPermissions: ["domain.settings.view", "domain.settings.manage"] },
  { label: "Password", href: "/settings/password", icon: <KeyRound className="h-4 w-4" />, requiredPermissions: ["domain.settings.view", "domain.settings.manage"] },
  { label: "Two-Factor Auth", href: "/settings/two-factor", icon: <Lock className="h-4 w-4" />, requiredPermissions: ["domain.settings.view", "domain.settings.manage"] },
  { label: "Appearance", href: "/settings/appearance", icon: <Palette className="h-4 w-4" />, requiredPermissions: ["domain.settings.view", "domain.settings.manage"] },
  { label: "Leave", href: "/settings/leave", icon: <CalendarDays className="h-4 w-4" />, requiredPermissions: ["domain.leave.view", "domain.leave.manage"] },
];
