import { Head, useForm } from "@inertiajs/react";
import { useEffect, useState } from "react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { projectNavItems } from "@/config/domain-nav/projects";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Facilitator Dashboard", href: "/project-locations/dashboard" },
  { title: "Attendance Register", href: "#" },
];

type BeneficiaryRow = {
  id: number;
  name: string;
  status: "present" | "absent" | "excused";
  excused_reason?: string | null;
  attendance_status: "active" | "dropout";
};

export default function ProjectLocationAttendance({
  location,
  selectedDate,
  register,
  beneficiaries,
  dayStats,
  history,
  canManageRegister,
  canMarkHoliday,
}: {
  location: {
    id: number;
    project_id: number;
    project_name: string | null;
    province: string | null;
    training_venue_address: string | null;
    facilitator_name: string | null;
    start_date: string | null;
    end_date: string | null;
  };
  selectedDate: string;
  register: { id: number; is_holiday: boolean; holiday_reason?: string | null } | null;
  beneficiaries: BeneficiaryRow[];
  dayStats: { present: number; absent: number; excused: number; total: number };
  history: Array<{
    id: number;
    date: string;
    day_of_week: string;
    is_holiday: boolean;
    holiday_reason?: string | null;
    entries_count: number;
    entries: Array<{
      beneficiary_id: number;
      beneficiary_name: string;
      status: "present" | "absent" | "excused";
      excused_reason?: string | null;
    }>;
  }>;
  canManageRegister: boolean;
  canMarkHoliday: boolean;
}) {
  const [registerError, setRegisterError] = useState<string | null>(null);
  const [holidayError, setHolidayError] = useState<string | null>(null);
  const [detailOpen, setDetailOpen] = useState(false);
  const [selectedRegisterDetail, setSelectedRegisterDetail] = useState<{
    id: number;
    attendance_date: string | null;
    day_of_week: string | null;
    is_holiday: boolean;
    holiday_reason: string | null;
    project_name: string | null;
    location_name: string | null;
    training_venue_address: string | null;
    facilitator_name: string | null;
    entries: Array<{
      beneficiary_id: number;
      beneficiary_name: string;
      status: "present" | "absent" | "excused";
      excused_reason: string | null;
    }>;
  } | null>(null);

  const form = useForm({
    attendance_date: selectedDate,
    entries: beneficiaries.map((beneficiary) => ({
      beneficiary_id: beneficiary.id,
      status: beneficiary.status,
      excused_reason: beneficiary.excused_reason ?? "",
    })),
  });

  const holidayForm = useForm({
    attendance_date: selectedDate,
    holiday_reason: register?.holiday_reason ?? "",
  });

  useEffect(() => {
    setRegisterError(null);
    setHolidayError(null);
    form.setData("attendance_date", selectedDate);
    form.setData(
      "entries",
      beneficiaries.map((beneficiary) => ({
        beneficiary_id: beneficiary.id,
        status: beneficiary.status,
        excused_reason: beneficiary.excused_reason ?? "",
      }))
    );
    holidayForm.setData("attendance_date", selectedDate);
    holidayForm.setData("holiday_reason", register?.holiday_reason ?? "");
  }, [selectedDate, beneficiaries, register]);

  const openRegisterDetail = (registerId: number) => {
    const historyItem = history.find((item) => item.id === registerId);
    if (!historyItem) {
      return;
    }

    setSelectedRegisterDetail({
      id: historyItem.id,
      attendance_date: historyItem.date,
      day_of_week: historyItem.day_of_week,
      is_holiday: historyItem.is_holiday,
      holiday_reason: historyItem.holiday_reason ?? null,
      project_name: location.project_name,
      location_name: location.province,
      training_venue_address: location.training_venue_address,
      facilitator_name: location.facilitator_name,
      entries: historyItem.entries,
    });
    setDetailOpen(true);
  };

  const detailStats = selectedRegisterDetail
    ? (() => {
        const total = selectedRegisterDetail.entries.length;
        const absent = selectedRegisterDetail.entries.filter((entry) => entry.status === "absent").length;
        const present = selectedRegisterDetail.entries.filter((entry) => entry.status === "present").length;
        const excused = selectedRegisterDetail.entries.filter((entry) => entry.status === "excused").length;
        const attendanceRate = total > 0 ? Number((((total - absent) / total) * 100).toFixed(2)) : 0;

        return { total, absent, present, excused, attendanceRate };
      })()
    : null;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Attendance Register" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Daily Attendance Register</h1>
            <p className="text-sm text-muted-foreground">
              {location.project_name ?? "-"} | {location.province ?? "-"} | {location.facilitator_name ?? "-"}
            </p>
            <p className="text-xs text-muted-foreground">
              Venue: {location.training_venue_address || "-"}
            </p>
          </div>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="rounded-xl border bg-white p-4 shadow-sm">
          <div className="grid gap-3 md:grid-cols-3">
            <div>
              <label className="mb-1 block text-sm font-medium">Date</label>
              <input
                type="date"
                className="w-full rounded-md border px-3 py-2 text-sm"
                value={form.data.attendance_date}
                onChange={(e) => {
                  const date = e.target.value;
                  form.setData("attendance_date", date);
                  window.location.assign(`/project-locations/${location.id}/attendance?date=${date}`);
                }}
              />
            </div>
            <div className="text-sm text-muted-foreground md:col-span-2">
              Project window: {location.start_date ?? "-"} to {location.end_date ?? "ongoing"}
              <br />
              Weekend days are blocked. Excused entries require a reason.
            </div>
          </div>
        </div>

        {register?.is_holiday && (
          <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            This day is marked as a holiday. Reason: {register.holiday_reason || "-"}
          </div>
        )}

        {(registerError || form.errors.attendance_date || form.errors.entries) && (
          <div className="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
            {registerError ?? form.errors.attendance_date ?? form.errors.entries}
          </div>
        )}

        {(holidayError || holidayForm.errors.attendance_date || holidayForm.errors.holiday_reason) && (
          <div className="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
            {holidayError ?? holidayForm.errors.attendance_date ?? holidayForm.errors.holiday_reason}
          </div>
        )}

        <div className="rounded-xl border bg-white p-4 shadow-sm">
          <h2 className="text-lg font-semibold">Beneficiary Attendance</h2>
          <div className="mt-3 grid gap-3 sm:grid-cols-4">
            <div className="rounded-md border p-3">
              <div className="text-xs text-muted-foreground">Total</div>
              <div className="text-xl font-semibold">{dayStats.total}</div>
            </div>
            <div className="rounded-md border p-3">
              <div className="text-xs text-muted-foreground">Present</div>
              <div className="text-xl font-semibold text-green-700">{dayStats.present}</div>
            </div>
            <div className="rounded-md border p-3">
              <div className="text-xs text-muted-foreground">Absent</div>
              <div className="text-xl font-semibold text-red-700">{dayStats.absent}</div>
            </div>
            <div className="rounded-md border p-3">
              <div className="text-xs text-muted-foreground">Excused</div>
              <div className="text-xl font-semibold text-amber-700">{dayStats.excused}</div>
            </div>
          </div>
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="px-3 py-2 text-left">Beneficiary</th>
                  <th className="px-3 py-2 text-left">Status</th>
                  <th className="px-3 py-2 text-left">Excused Reason</th>
                </tr>
              </thead>
              <tbody>
                {form.data.entries.map((entry, idx) => (
                  <tr key={entry.beneficiary_id} className="border-b">
                    <td className="px-3 py-2">{beneficiaries[idx]?.name ?? "Unknown"}</td>
                    <td className="px-3 py-2">
                      <select
                        className="rounded-md border px-2 py-1"
                        value={entry.status}
                        disabled={!canManageRegister || !!register?.is_holiday}
                        onChange={(e) => {
                          const next = [...form.data.entries];
                          next[idx] = {
                            ...next[idx],
                            status: e.target.value as "present" | "absent" | "excused",
                            excused_reason: e.target.value === "excused" ? next[idx].excused_reason : "",
                          };
                          form.setData("entries", next);
                        }}
                      >
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="excused">Excused</option>
                      </select>
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="text"
                        className="w-full rounded-md border px-2 py-1"
                        placeholder="Reason (required for excused)"
                        value={entry.excused_reason}
                        disabled={!canManageRegister || !!register?.is_holiday || entry.status !== "excused"}
                        onChange={(e) => {
                          const next = [...form.data.entries];
                          next[idx] = {
                            ...next[idx],
                            excused_reason: e.target.value,
                          };
                          form.setData("entries", next);
                        }}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="mt-4 flex flex-wrap gap-2">
            {canManageRegister && !register?.is_holiday && (
              <Button
                type="button"
                disabled={form.processing}
                onClick={() => {
                  setRegisterError(null);
                  form.post(`/project-locations/${location.id}/attendance`, {
                    preserveScroll: true,
                    onError: (errors) => {
                      setRegisterError(
                        errors.attendance_date ??
                          errors.entries ??
                          errors.message ??
                          "Unable to save register. Check input and permissions."
                      );
                    },
                  });
                }}
              >
                {register ? "Update Register" : "Create Register"}
              </Button>
            )}

            {canMarkHoliday && (
              <div className="flex flex-wrap items-center gap-2">
                <input
                  type="text"
                  className="rounded-md border px-3 py-2 text-sm"
                  placeholder="Holiday reason"
                  value={holidayForm.data.holiday_reason}
                  onChange={(e) => holidayForm.setData("holiday_reason", e.target.value)}
                />
                <Button
                  type="button"
                  variant="outline"
                  disabled={holidayForm.processing}
                  onClick={() => {
                    setHolidayError(null);
                    holidayForm.post(`/project-locations/${location.id}/attendance/holiday`, {
                      preserveScroll: true,
                      onError: (errors) => {
                        setHolidayError(
                          errors.attendance_date ??
                            errors.holiday_reason ??
                            errors.message ??
                            "Unable to mark holiday."
                        );
                      },
                    });
                  }}
                >
                  Mark as Holiday
                </Button>
              </div>
            )}
          </div>
        </div>

        <div className="rounded-xl border bg-white p-4 shadow-sm">
          <h2 className="text-lg font-semibold">Recent Registers</h2>
          <p className="text-sm text-muted-foreground">
            Open a previous day to view details or edit mistakes (if you have edit permission).
          </p>

          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="px-3 py-2 text-left">#</th>
                  <th className="px-3 py-2 text-left">Date</th>
                  <th className="px-3 py-2 text-left">Type</th>
                  <th className="px-3 py-2 text-left">Entries</th>
                  <th className="px-3 py-2 text-left">Reason</th>
                  <th className="px-3 py-2 text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                {history.map((item, index) => (
                  <tr key={item.id} className="border-b">
                    <td className="px-3 py-2">{index + 1}</td>
                    <td className="px-3 py-2">{item.date}</td>
                    <td className="px-3 py-2">{item.is_holiday ? "Holiday" : "Register"}</td>
                    <td className="px-3 py-2">{item.entries_count}</td>
                    <td className="px-3 py-2">{item.holiday_reason ?? "-"}</td>
                    <td className="px-3 py-2">
                      <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                          openRegisterDetail(item.id);
                        }}
                      >
                        Open
                      </Button>
                    </td>
                  </tr>
                ))}
                {history.length === 0 && (
                  <tr>
                    <td className="px-3 py-3 text-muted-foreground" colSpan={6}>
                      No previous registers yet.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <Dialog
        open={detailOpen}
        onOpenChange={(open) => {
          setDetailOpen(open);
          if (!open) {
            setSelectedRegisterDetail(null);
          }
        }}
      >
        <DialogContent className="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Attendance Register Details</DialogTitle>
            <DialogDescription>
              Full daily register details for this location.
            </DialogDescription>
          </DialogHeader>

          {selectedRegisterDetail && (
            <div className="space-y-4 text-sm">
              <div className="grid gap-3 sm:grid-cols-2">
                <div><span className="font-medium">Date:</span> {selectedRegisterDetail.attendance_date ?? "-"}</div>
                <div><span className="font-medium">Day:</span> {selectedRegisterDetail.day_of_week ?? "-"}</div>
                <div><span className="font-medium">Project:</span> {selectedRegisterDetail.project_name ?? "-"}</div>
                <div><span className="font-medium">Location:</span> {selectedRegisterDetail.location_name ?? "-"}</div>
                <div><span className="font-medium">Facilitator:</span> {selectedRegisterDetail.facilitator_name ?? "-"}</div>
                <div><span className="font-medium">Venue Address:</span> {selectedRegisterDetail.training_venue_address ?? "-"}</div>
                <div><span className="font-medium">Type:</span> {selectedRegisterDetail.is_holiday ? "Holiday" : "Register"}</div>
                <div><span className="font-medium">Holiday Reason:</span> {selectedRegisterDetail.holiday_reason ?? "-"}</div>
              </div>

              {detailStats && (
                <div className="grid gap-3 sm:grid-cols-4">
                  <div className="rounded-md border p-3">
                    <div className="text-xs text-muted-foreground">Attendance</div>
                    <div className="text-base font-semibold">{detailStats.attendanceRate}%</div>
                  </div>
                  <div className="rounded-md border p-3">
                    <div className="text-xs text-muted-foreground">Absent</div>
                    <div className="text-base font-semibold text-red-700">{detailStats.absent}</div>
                  </div>
                  <div className="rounded-md border p-3">
                    <div className="text-xs text-muted-foreground">Present</div>
                    <div className="text-base font-semibold text-green-700">{detailStats.present}</div>
                  </div>
                  <div className="rounded-md border p-3">
                    <div className="text-xs text-muted-foreground">Excused</div>
                    <div className="text-base font-semibold text-amber-700">{detailStats.excused}</div>
                  </div>
                </div>
              )}

              <div className="overflow-x-auto rounded-md border">
                <table className="min-w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="px-3 py-2 text-left">#</th>
                      <th className="px-3 py-2 text-left">Beneficiary</th>
                      <th className="px-3 py-2 text-left">Status</th>
                      <th className="px-3 py-2 text-left">Reason</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selectedRegisterDetail.entries.map((entry, index) => (
                      <tr key={entry.beneficiary_id} className="border-b">
                        <td className="px-3 py-2">{index + 1}</td>
                        <td className="px-3 py-2">{entry.beneficiary_name}</td>
                        <td className="px-3 py-2 capitalize">{entry.status}</td>
                        <td className="px-3 py-2">{entry.excused_reason ?? "-"}</td>
                      </tr>
                    ))}
                    {selectedRegisterDetail.entries.length === 0 && (
                      <tr>
                        <td colSpan={4} className="px-3 py-3 text-muted-foreground">
                          No entries captured for this date.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              <div className="flex justify-end gap-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => {
                    if (!selectedRegisterDetail) return;
                    window.open(`/attendance-registers/${selectedRegisterDetail.id}/export/pdf`, "_blank");
                  }}
                >
                  Export PDF
                </Button>
                <Button type="button" variant="outline" disabled>
                  Export XML (Soon)
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
