import { KeyRound, Lock, Palette, UserCircle, CalendarDays } from "lucide-react";

export const settingsNavItems = [
  { label: "Profile", href: "/settings/profile", icon: <UserCircle className="h-4 w-4" /> },
  { label: "Password", href: "/settings/password", icon: <KeyRound className="h-4 w-4" /> },
  { label: "Two-Factor Auth", href: "/settings/two-factor", icon: <Lock className="h-4 w-4" /> },
  { label: "Appearance", href: "/settings/appearance", icon: <Palette className="h-4 w-4" /> },
  { label: "Leave", href: "/settings/leave", icon: <CalendarDays className="h-4 w-4" /> },
];
