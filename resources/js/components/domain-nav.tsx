import { Link, usePage } from "@inertiajs/react";
import { type ReactNode } from "react";

import { hasAnyPermission, hasAnyRole } from "@/lib/access";
import { cn } from "@/lib/utils";
import { type SharedData } from "@/types";

export type DomainNavItem = {
  label: string;
  href: string;
  icon?: ReactNode;
  requiredPermissions?: string[];
  requiredRoles?: string[];
};

export function DomainNav({
  items,
}: {
  items: DomainNavItem[];
}) {
  const { url, props } = usePage<SharedData>();
  const user = props.auth?.user;
  const visibleItems = items.filter(
    (item) =>
      hasAnyRole(user, item.requiredRoles ?? []) &&
      hasAnyPermission(user, item.requiredPermissions ?? [])
  );

  return (
    <div className="flex flex-wrap items-center gap-2">
      {visibleItems.map((item) => {
        const isActive = url === item.href || url.startsWith(`${item.href}/`);

        return (
          <Link
            key={item.href}
            href={item.href}
            className={cn(
              "inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm transition",
              isActive
                ? "border-red-600 bg-red-600 text-white"
                : "border-orange-500 text-orange-600 hover:bg-orange-500 hover:text-white"
            )}
          >
            {item.icon}
            {item.label}
          </Link>
        );
      })}
    </div>
  );
}
