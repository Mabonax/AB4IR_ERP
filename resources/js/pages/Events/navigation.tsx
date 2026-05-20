import {
  CalendarRange,
  ClipboardList,
  ClipboardSignature,
  FileText,
  RadioTower,
  Users,
} from "lucide-react";

import { type DomainNavItem } from "@/components/domain-nav";

export function eventWorkflowNav(eventId: number): DomainNavItem[] {
  return [
    { label: "Events", href: "/events", icon: <CalendarRange className="h-4 w-4" /> },
    { label: "Overview", href: `/events/${eventId}`, icon: <FileText className="h-4 w-4" /> },
    { label: "Participants", href: `/events/${eventId}/participants`, icon: <Users className="h-4 w-4" /> },
    { label: "Registers", href: `/events/${eventId}/registers`, icon: <ClipboardSignature className="h-4 w-4" /> },
    { label: "Event Day", href: `/events/${eventId}/event-day`, icon: <RadioTower className="h-4 w-4" /> },
  ];
}

export function eventSeriesNav(seriesKey: string): DomainNavItem[] {
  return [
    { label: "Events", href: "/events", icon: <CalendarRange className="h-4 w-4" /> },
    { label: "Series", href: `/events/series/${seriesKey}`, icon: <ClipboardList className="h-4 w-4" /> },
  ];
}
