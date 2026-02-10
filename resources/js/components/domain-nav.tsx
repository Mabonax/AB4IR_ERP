import { Link, usePage } from "@inertiajs/react";
import { type ReactNode } from "react";

import { cn } from "@/lib/utils";

export type DomainNavItem = {
  label: string;
  href: string;
  icon?: ReactNode;
};

export function DomainNav({
  items,
}: {
  items: DomainNavItem[];
}) {
  const { url } = usePage();

  return (
    <div className="flex flex-wrap items-center gap-2">
      {items.map((item) => {
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
