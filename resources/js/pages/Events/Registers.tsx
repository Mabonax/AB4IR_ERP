import { Head } from "@inertiajs/react";
import { useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem } from "@/types";

export default function EventRegisters({
  event,
}: {
  event: any;
}) {
  const initialRegister = event.registers?.[0] ?? null;
  const [selectedRegisterKey, setSelectedRegisterKey] = useState(initialRegister?.key ?? "");
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: "Registers", href: `/events/${event.id}/registers` },
  ];
  const activeRegister =
    (event.registers ?? []).find((register: any) => register.key === selectedRegisterKey) ??
    initialRegister;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`${event.title} Registers`} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{event.title} Registers</h1>
            <p className="text-sm text-muted-foreground">
              Download printable participant registers by category and inspect the exact lists that will be used on event day.
            </p>
          </div>
          <DomainNav items={eventWorkflowNav(event.id)} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {[
            ["Total Register", event.event_day_summary?.total_register ?? 0],
            ["Confirmed", event.event_day_summary?.confirmed ?? 0],
            ["Checked In", event.event_day_summary?.checked_in ?? 0],
            ["Attended", event.event_day_summary?.attended ?? 0],
          ].map(([label, value]) => (
            <Card key={String(label)} className="border-slate-200 shadow-sm">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm">{label}</CardTitle>
              </CardHeader>
              <CardContent className="text-3xl font-semibold text-slate-950">{String(value)}</CardContent>
            </Card>
          ))}
        </div>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle>Category Registers</CardTitle>
            <CardDescription>
              Open one register at a time so event-day packs stay focused and easier to inspect.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex flex-wrap gap-2">
              {(event.registers ?? []).map((register: any) => (
                <button
                  key={register.key}
                  type="button"
                  onClick={() => setSelectedRegisterKey(register.key)}
                  className={`inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition ${
                    selectedRegisterKey === register.key
                      ? "border-red-200 bg-red-50 text-red-700"
                      : "border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50"
                  }`}
                >
                  <span>{register.label}</span>
                  <span className="rounded-full bg-white/80 px-2 py-0.5 text-xs">{register.count}</span>
                </button>
              ))}
            </div>

            {activeRegister ? (
              <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="text-lg font-semibold text-slate-950">{activeRegister.label}</div>
                    <div className="mt-1 text-sm text-slate-500">{activeRegister.count} entries in this register</div>
                  </div>
                  <div className="flex gap-2">
                    <a
                      href={`/events/${event.id}/registers/${activeRegister.key}/pdf`}
                      className="rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    >
                      PDF
                    </a>
                    <a
                      href={`/events/${event.id}/registers/${activeRegister.key}/csv`}
                      className="rounded-md border border-red-500 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-500 hover:text-white"
                    >
                      CSV
                    </a>
                  </div>
                </div>

                <div className="mt-4 grid grid-cols-3 gap-3 text-sm">
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Count</div>
                    <div className="mt-1 font-semibold text-slate-900">{activeRegister.count}</div>
                  </div>
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Checked In</div>
                    <div className="mt-1 font-semibold text-slate-900">{activeRegister.checked_in}</div>
                  </div>
                  <div>
                    <div className="text-xs uppercase tracking-wide text-slate-500">Attended</div>
                    <div className="mt-1 font-semibold text-slate-900">{activeRegister.attended}</div>
                  </div>
                </div>

                <div className="mt-4 overflow-hidden rounded-xl border border-slate-200">
                  <table className="min-w-full text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                      <tr>
                        <th className="px-3 py-2 font-medium">Name</th>
                        <th className="px-3 py-2 font-medium">Organization</th>
                        <th className="px-3 py-2 font-medium">Attendance Type</th>
                        <th className="px-3 py-2 font-medium">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {(activeRegister.items ?? []).length === 0 ? (
                        <tr>
                          <td colSpan={4} className="px-3 py-3 text-sm text-slate-500">
                            No entries in this register yet.
                          </td>
                        </tr>
                      ) : (
                        activeRegister.items.map((item: any) => (
                          <tr key={item.id}>
                            <td className="px-3 py-3">
                              <div className="font-medium text-slate-900">{[item.name, item.surname].filter(Boolean).join(" ")}</div>
                              <div className="text-xs text-slate-500">{item.title ?? item.role ?? item.topic ?? "-"}</div>
                            </td>
                            <td className="px-3 py-3 text-slate-700">{item.organization_name ?? "-"}</td>
                            <td className="px-3 py-3 text-slate-700">{item.attendance_type ?? "-"}</td>
                            <td className="px-3 py-3 capitalize text-slate-700">{String(item.attendance_status ?? "-").replaceAll("_", " ")}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            ) : (
              <div className="rounded-md border border-dashed bg-white px-3 py-4 text-sm text-muted-foreground">
                No registers are available for this event yet.
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
