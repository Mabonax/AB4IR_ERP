import { Link } from "@inertiajs/react";

const items = [
  { label: "Dashboard", href: "/service-delivery" },
  { label: "Activities", href: "/service-delivery/activities" },
  { label: "Attendance", href: "/service-delivery/attendance" },
  { label: "Placements", href: "/service-delivery/placements" },
  { label: "Partnerships", href: "/service-delivery/partnerships" },
  { label: "Outcomes", href: "/service-delivery/outcomes" },
  { label: "Documents", href: "/service-delivery/documents" },
];

export function ServiceDeliveryNav() {
  return (
    <div className="flex flex-wrap gap-2">
      {items.map((item) => (
        <Link
          key={item.href}
          href={item.href}
          className="rounded-full border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:border-red-600 hover:bg-red-50"
        >
          {item.label}
        </Link>
      ))}
    </div>
  );
}
