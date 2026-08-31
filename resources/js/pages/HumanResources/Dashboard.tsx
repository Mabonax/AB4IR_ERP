import { Head, Link, router } from "@inertiajs/react";
import {
  BriefcaseBusiness,
  CalendarCheck2,
  CalendarDays,
  CheckCircle2,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ClipboardList,
  Download,
  Eye,
  FileText,
  Filter,
  HeartPulse,
  MoreHorizontal,
  Pencil,
  Plus,
  Search,
  Sparkles,
  Star,
  UserCheck,
  UserPlus,
  Users,
  XCircle,
} from "lucide-react";
import { useMemo, useState } from "react";
import {
  Area,
  AreaChart,
  Cell,
  Line,
  LineChart,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
} from "recharts";

import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import AppLayout from "@/layouts/app-layout";
import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Human Resources", href: "/human-resources" },
];

type PendingLeaveApproval = {
  id: number;
  staff_member_name: string | null;
  department_name: string | null;
  manager_name: string | null;
  leave_type_label: string;
  start_date: string | null;
  end_date: string | null;
  total_days: number;
  status: string;
};

type StaffDirectoryRow = {
  id: number;
  name: string;
  email: string;
  employee_number: string;
  status: string;
  employment_type: string;
  position: string;
  avatar_initials: string;
  department_id: number | null;
  department_name: string | null;
  manager_name: string | null;
};

type Department = {
  id: number;
  name: string;
  description?: string | null;
  staff_count: number;
};

type ChartPoint = {
  label: string;
  staff?: number;
  present?: number;
};

type PiePoint = {
  name: string;
  value: number;
  staff?: number;
};

type LeaveCalendarEvent = {
  id: number;
  day: number;
  type: string;
  status: string;
  label: string;
};

type Holiday = {
  date: string | null;
  label: string;
  days_until: number | null;
};

type Props = {
  stats: {
    totalStaff: number;
    activeStaff: number;
    inactiveStaff: number;
    presentToday: number;
    onLeaveToday: number;
    pendingApprovals: number;
    pendingManager: number;
    pendingHr: number;
    approved: number;
    newEmployees: number;
    attendanceRate: number;
    monthLeaveDays: number;
    availableLeaveDays: number;
  };
  analytics: {
    headcountTrend: ChartPoint[];
    attendanceTrend: ChartPoint[];
    departmentDistribution: PiePoint[];
    employmentTypes: PiePoint[];
    staffMix: PiePoint[];
  };
  workforce: {
    present: number;
    onLeave: number;
    absent: number;
    pendingApprovals: number;
    newEmployees: number;
  };
  departments: Department[];
  leaveSummary: {
    totals: {
      annual_taken: number;
      annual_available: number;
      sick_taken: number;
      sick_available: number;
    };
    staff: {
      staff_id: number;
      staff_name: string;
      department_name: string | null;
      leave_account: {
        annual: { available: number; taken: number };
        sick: { available: number; taken: number };
        pending: { count: number };
      };
    }[];
  };
  staffDirectory: StaffDirectoryRow[];
  pendingLeaveApprovals: PendingLeaveApproval[];
  leaveCalendar: {
    monthLabel: string;
    today: number;
    events: LeaveCalendarEvent[];
    holidays: Holiday[];
  };
  selectedDepartmentId: number | null;
  canManageManagerLeave: boolean;
  canManageHrLeave: boolean;
};

const pieColors = ["#ef3b1f", "#f59e0b", "#8b5cf6", "#2f80ed", "#22c55e", "#14b8a6"];

const cardTone = {
  purple: "bg-violet-50 text-violet-700 ring-violet-100",
  green: "bg-emerald-50 text-emerald-700 ring-emerald-100",
  amber: "bg-amber-50 text-amber-700 ring-amber-100",
  red: "bg-red-50 text-red-700 ring-red-100",
  blue: "bg-blue-50 text-blue-700 ring-blue-100",
};

const leaveTypeClass: Record<string, string> = {
  annual: "bg-emerald-400",
  sick: "bg-red-400",
  personal: "bg-orange-400",
  maternity: "bg-violet-400",
  family_responsibility: "bg-blue-400",
};

function percent(value: number, total: number) {
  if (total <= 0) return 0;
  return Math.round((value / total) * 100);
}

function statusClass(status: string) {
  return status === "active"
    ? "bg-emerald-50 text-emerald-700 ring-emerald-100"
    : "bg-slate-100 text-slate-600 ring-slate-200";
}

function formatStatus(status: string) {
  return status
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

function eventTone(event: LeaveCalendarEvent) {
  if (event.status !== "hr_approved") return "bg-amber-400";
  return leaveTypeClass[event.type] ?? "bg-blue-400";
}

function MiniLine({ data, dataKey, color }: { data: ChartPoint[]; dataKey: "staff" | "present"; color: string }) {
  return (
    <ResponsiveContainer width="100%" height={46}>
      <LineChart data={data}>
        <Line type="monotone" dataKey={dataKey} stroke={color} strokeWidth={2.5} dot={false} />
      </LineChart>
    </ResponsiveContainer>
  );
}

function MetricCard({
  title,
  value,
  subtitle,
  icon: Icon,
  tone,
  trendData,
  trendKey,
  trendColor,
}: {
  title: string;
  value: number | string;
  subtitle: string;
  icon: typeof Users;
  tone: keyof typeof cardTone;
  trendData?: ChartPoint[];
  trendKey?: "staff" | "present";
  trendColor: string;
}) {
  return (
    <Card className="rounded-xl border-slate-200 shadow-sm">
      <CardContent className="space-y-4 p-5">
        <div className="flex items-start justify-between gap-4">
          <div>
            <p className="text-sm font-semibold text-slate-900">{title}</p>
            <div className="mt-2 text-3xl font-bold tracking-normal text-slate-950">{value}</div>
          </div>
          <div className={`flex size-11 items-center justify-center rounded-full ring-1 ${cardTone[tone]}`}>
            <Icon className="size-5" />
          </div>
        </div>
        <p className="text-xs font-medium text-emerald-600">{subtitle}</p>
        {trendData && trendKey ? <MiniLine data={trendData} dataKey={trendKey} color={trendColor} /> : null}
      </CardContent>
    </Card>
  );
}

function DoughnutCard({
  title,
  data,
  center,
}: {
  title: string;
  data: PiePoint[];
  center: string;
}) {
  const safeData = data.some((item) => item.value > 0) ? data : [{ name: "No data", value: 1 }];

  return (
    <Card className="rounded-xl border-slate-200 shadow-sm">
      <CardHeader className="pb-2">
        <CardTitle className="text-sm font-semibold">{title}</CardTitle>
      </CardHeader>
      <CardContent className="grid grid-cols-[110px_1fr] items-center gap-3">
        <div className="relative h-[110px]">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie data={safeData} innerRadius={34} outerRadius={52} dataKey="value" paddingAngle={2}>
                {safeData.map((entry, index) => (
                  <Cell key={entry.name} fill={pieColors[index % pieColors.length]} />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>
          <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
            <span className="text-lg font-bold text-slate-950">{center}</span>
            <span className="text-[10px] text-slate-500">total</span>
          </div>
        </div>
        <div className="space-y-2">
          {data.slice(0, 5).map((item, index) => (
            <div key={item.name} className="flex items-center justify-between gap-2 text-xs">
              <span className="flex min-w-0 items-center gap-2 text-slate-600">
                <span className="size-2 rounded-full" style={{ backgroundColor: pieColors[index % pieColors.length] }} />
                <span className="truncate">{item.name}</span>
              </span>
              <span className="font-semibold text-slate-900">{item.value}</span>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

export default function HumanResourcesDashboard({
  stats,
  analytics,
  workforce,
  departments,
  staffDirectory,
  pendingLeaveApprovals,
  leaveCalendar,
  selectedDepartmentId,
  canManageManagerLeave,
  canManageHrLeave,
}: Props) {
  const [actionOpen, setActionOpen] = useState(false);
  const [actionType, setActionType] = useState<"manager_approve" | "manager_reject" | "hr_approve" | "hr_reject" | null>(null);
  const [selectedLeave, setSelectedLeave] = useState<PendingLeaveApproval | null>(null);
  const [comment, setComment] = useState("");
  const [query, setQuery] = useState("");
  const [departmentFilter, setDepartmentFilter] = useState(selectedDepartmentId ? String(selectedDepartmentId) : "");
  const [statusFilter, setStatusFilter] = useState("");

  const filteredStaff = useMemo(() => {
    const normalized = query.trim().toLowerCase();

    return staffDirectory.filter((employee) => {
      const matchesQuery =
        normalized.length === 0 ||
        [employee.name, employee.email, employee.employee_number, employee.department_name ?? ""]
          .join(" ")
          .toLowerCase()
          .includes(normalized);
      const matchesDepartment = !departmentFilter || String(employee.department_id ?? "") === departmentFilter;
      const matchesStatus = !statusFilter || employee.status === statusFilter;

      return matchesQuery && matchesDepartment && matchesStatus;
    });
  }, [departmentFilter, query, staffDirectory, statusFilter]);

  const visibleStaff = filteredStaff.slice(0, 10);
  const leaveEventsByDay = useMemo(() => {
    return leaveCalendar.events.reduce<Record<number, LeaveCalendarEvent[]>>((days, event) => {
      days[event.day] = [...(days[event.day] ?? []), event];
      return days;
    }, {});
  }, [leaveCalendar.events]);

  const monthDate = useMemo(() => {
    const parsed = new Date(`${leaveCalendar.monthLabel} 1`);
    return Number.isNaN(parsed.getTime()) ? new Date() : parsed;
  }, [leaveCalendar.monthLabel]);
  const daysInMonth = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();
  const firstDay = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1).getDay();
  const calendarCells = [
    ...Array.from({ length: firstDay }, () => null),
    ...Array.from({ length: daysInMonth }, (_, index) => index + 1),
  ];

  const openAction = (leave: PendingLeaveApproval, type: typeof actionType) => {
    setSelectedLeave(leave);
    setActionType(type);
    setComment("");
    setActionOpen(true);
  };

  const submitAction = (event: React.FormEvent) => {
    event.preventDefault();
    if (!selectedLeave || !actionType) return;

    const url =
      actionType === "manager_approve"
        ? `/leave-requests/${selectedLeave.id}/manager-approve`
        : actionType === "manager_reject"
          ? `/leave-requests/${selectedLeave.id}/manager-reject`
          : actionType === "hr_approve"
            ? `/leave-requests/${selectedLeave.id}/hr-approve`
            : `/leave-requests/${selectedLeave.id}/hr-reject`;

    const payload =
      actionType === "manager_approve" || actionType === "manager_reject"
        ? { manager_comment: comment }
        : { hr_comment: comment };

    router.post(url, payload, {
      preserveScroll: true,
      onSuccess: () => setActionOpen(false),
    });
  };

  const syncDepartmentFilter = (value: string) => {
    setDepartmentFilter(value);
    router.get("/human-resources", value ? { department_id: value } : {}, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Human Resources" />

      <div className="min-h-screen bg-slate-50/50 p-4 text-slate-950 md:p-6">
        <div className="mx-auto max-w-[1800px] space-y-5">
          <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
              <h1 className="text-3xl font-bold tracking-normal">Human Resources</h1>
              <p className="mt-1 text-sm text-slate-500">Manage your people, performance and workforce.</p>
            </div>
            <div className="flex flex-wrap items-center gap-3">
              <div className="relative w-full min-w-[260px] sm:w-[360px]">
                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  className="h-10 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-20 text-sm shadow-sm outline-none ring-red-100 transition focus:border-red-300 focus:ring-4"
                  placeholder="Search employees..."
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Ctrl + K</span>
              </div>
              <Button asChild className="bg-red-600 shadow-sm hover:bg-red-700">
                <Link href={staff.create.url()}>
                  <Plus className="size-4" />
                  Add Employee
                </Link>
              </Button>
              <Button asChild variant="outline" className="bg-white">
                <Link href="/leave-requests">
                  <CalendarCheck2 className="size-4" />
                  Leave Requests
                </Link>
              </Button>
              <Button asChild variant="outline" className="bg-white">
                <Link href="/staff-departments">
                  <Users className="size-4" />
                  Departments
                </Link>
              </Button>
              <Button asChild variant="outline" className="bg-white">
                <Link href="/human-resources/attendance/report/pdf">
                  <FileText className="size-4" />
                  Reports
                </Link>
              </Button>
              <Button variant="outline" className="bg-white">
                More
                <ChevronDown className="size-4" />
              </Button>
            </div>
          </div>

          <div className="grid gap-4 xl:grid-cols-[1fr_320px]">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
              <MetricCard
                title="Total Staff"
                value={stats.totalStaff}
                subtitle={`${stats.activeStaff} active employees`}
                icon={Users}
                tone="purple"
                trendData={analytics.headcountTrend}
                trendKey="staff"
                trendColor="#6d28d9"
              />
              <MetricCard
                title="Present Today"
                value={stats.presentToday}
                subtitle={`${stats.attendanceRate}% attendance rate`}
                icon={UserCheck}
                tone="green"
                trendData={analytics.attendanceTrend}
                trendKey="present"
                trendColor="#16a34a"
              />
              <MetricCard
                title="On Leave"
                value={stats.onLeaveToday}
                subtitle={`${stats.monthLeaveDays} approved leave days this month`}
                icon={CalendarDays}
                tone="amber"
                trendColor="#f97316"
              />
              <MetricCard
                title="Pending Approvals"
                value={stats.pendingApprovals}
                subtitle={`${stats.pendingManager} manager, ${stats.pendingHr} HR`}
                icon={ClipboardList}
                tone="red"
                trendColor="#ef4444"
              />
              <MetricCard
                title="New Employees"
                value={stats.newEmployees}
                subtitle="Last 30 days"
                icon={UserPlus}
                tone="blue"
                trendColor="#2563eb"
              />
            </div>

            <Card className="rounded-xl border-orange-100 bg-gradient-to-br from-orange-50 to-white shadow-sm">
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-base">
                  <Sparkles className="size-5 text-red-500" />
                  HR Insights
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <Insight icon={UserCheck} text={`${stats.attendanceRate}% attendance recorded today`} subtext="Based on active staff clock-ins" />
                <Insight icon={HeartPulse} text={`${stats.pendingApprovals} approvals need attention`} subtext="Manager and HR leave workflow" />
                <Insight icon={BriefcaseBusiness} text={`${stats.availableLeaveDays} annual days available`} subtext="Across active leave accounts" />
                <Button asChild variant="outline" className="w-full border-orange-200 bg-white text-red-600 hover:bg-orange-50">
                  <Link href="/human-resources/attendance">View full report</Link>
                </Button>
              </CardContent>
            </Card>
          </div>

          <div className="grid gap-4 xl:grid-cols-[1fr_1.1fr_320px]">
            <Card className="rounded-xl border-slate-200 shadow-sm">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-base">Today's Workforce</CardTitle>
                <Button variant="outline" size="sm" className="bg-white">View all</Button>
              </CardHeader>
              <CardContent className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <WorkforceTile label="Present" value={workforce.present} icon={Users} tone="bg-emerald-50 text-emerald-700" />
                <WorkforceTile label="On Leave" value={workforce.onLeave} icon={UserCheck} tone="bg-orange-50 text-orange-700" />
                <WorkforceTile label="Absent" value={workforce.absent} icon={HeartPulse} tone="bg-red-50 text-red-700" />
                <WorkforceTile label="Pending" value={workforce.pendingApprovals} icon={CalendarCheck2} tone="bg-blue-50 text-blue-700" />
                <WorkforceTile label="New" value={workforce.newEmployees} icon={UserPlus} tone="bg-violet-50 text-violet-700" />
              </CardContent>
            </Card>

            <Card className="rounded-xl border-slate-200 shadow-sm">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-base">Pending Actions</CardTitle>
                <Button asChild variant="outline" size="sm" className="bg-white">
                  <Link href="/leave-requests">View all</Link>
                </Button>
              </CardHeader>
              <CardContent className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <ActionCard
                  title="Leave Requests"
                  value={`${pendingLeaveApprovals.length} request${pendingLeaveApprovals.length === 1 ? "" : "s"}`}
                  hint="Requires approval"
                  icon={CalendarCheck2}
                  tone="bg-red-50 text-red-600"
                  href="/leave-requests"
                />
                <ActionCard
                  title="Contract Renewals"
                  value={`${analytics.employmentTypes.find((item) => item.name === "Contract")?.value ?? 0} contracts`}
                  hint="Review fixed-term staff"
                  icon={FileText}
                  tone="bg-amber-50 text-amber-600"
                  href="/staff"
                />
                <ActionCard
                  title="Performance Reviews"
                  value={`${analytics.staffMix.find((item) => item.name === "Managers")?.value ?? 0} managers`}
                  hint="Team review owners"
                  icon={Star}
                  tone="bg-violet-50 text-violet-600"
                  href="/staff"
                />
                <ActionCard
                  title="Probation Reviews"
                  value={`${stats.newEmployees} new hires`}
                  hint="Joined in 30 days"
                  icon={UserPlus}
                  tone="bg-emerald-50 text-emerald-600"
                  href="/staff"
                />
                {(canManageManagerLeave || canManageHrLeave) && pendingLeaveApprovals.length > 0 ? (
                  <div className="space-y-2 rounded-xl border border-slate-200 bg-white p-3 sm:col-span-2 xl:col-span-4">
                    {pendingLeaveApprovals.slice(0, 3).map((leave) => (
                      <div key={leave.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                        <div className="min-w-0">
                          <p className="truncate text-sm font-semibold text-slate-900">{leave.staff_member_name ?? "Employee"}</p>
                          <p className="text-xs text-slate-500">
                            {leave.leave_type_label} - {leave.start_date ?? "-"} to {leave.end_date ?? "-"}
                          </p>
                        </div>
                        <div className="flex gap-2">
                          <Button
                            type="button"
                            size="sm"
                            className="h-8 bg-emerald-600 hover:bg-emerald-700"
                            onClick={() => openAction(leave, canManageHrLeave ? "hr_approve" : "manager_approve")}
                          >
                            <CheckCircle2 className="size-4" />
                            Approve
                          </Button>
                          <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            className="h-8 border-red-200 bg-white text-red-600 hover:bg-red-50"
                            onClick={() => openAction(leave, canManageHrLeave ? "hr_reject" : "manager_reject")}
                          >
                            <XCircle className="size-4" />
                            Reject
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : null}
              </CardContent>
            </Card>

            <Card className="row-span-3 rounded-xl border-slate-200 shadow-sm">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-base">Leave Calendar</CardTitle>
                <Button variant="outline" size="sm" className="bg-white">Today</Button>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-semibold text-slate-900">{leaveCalendar.monthLabel}</p>
                  <div className="flex gap-1">
                    <Button variant="ghost" size="icon" className="size-8"><ChevronLeft className="size-4" /></Button>
                    <Button variant="ghost" size="icon" className="size-8"><ChevronRight className="size-4" /></Button>
                  </div>
                </div>
                <div className="grid grid-cols-7 gap-1 text-center text-xs">
                  {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => (
                    <div key={day} className="py-2 font-medium text-slate-500">{day}</div>
                  ))}
                  {calendarCells.map((day, index) => {
                    const events = day ? leaveEventsByDay[day] ?? [] : [];
                    return (
                      <div
                        key={`${day ?? "blank"}-${index}`}
                        className={`min-h-10 rounded-lg p-1 ${day === leaveCalendar.today ? "bg-red-600 text-white" : "text-slate-700"}`}
                      >
                        {day ? <span className="text-xs font-medium">{day}</span> : null}
                        <div className="mt-1 space-y-0.5">
                          {events.slice(0, 2).map((event) => (
                            <span key={event.id} className={`block h-1 rounded-full ${eventTone(event)}`} title={event.label} />
                          ))}
                        </div>
                      </div>
                    );
                  })}
                </div>
                <div className="grid grid-cols-2 gap-2 text-xs text-slate-600">
                  <Legend color="bg-emerald-400" label="Annual Leave" />
                  <Legend color="bg-red-400" label="Sick Leave" />
                  <Legend color="bg-orange-400" label="Personal Leave" />
                  <Legend color="bg-amber-400" label="Pending" />
                </div>
                <div className="space-y-3">
                  <p className="text-sm font-semibold text-slate-900">Upcoming Holidays</p>
                  {leaveCalendar.holidays.length > 0 ? (
                    leaveCalendar.holidays.map((holiday) => (
                      <div key={`${holiday.date}-${holiday.label}`} className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div className="flex size-9 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                          <CalendarDays className="size-4" />
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-semibold">{holiday.label}</p>
                          <p className="text-xs text-slate-500">{holiday.date ?? "Date pending"}</p>
                        </div>
                        {holiday.days_until !== null ? (
                          <span className="rounded-full bg-violet-100 px-2 py-1 text-[11px] font-semibold text-violet-700">
                            {holiday.days_until} days
                          </span>
                        ) : null}
                      </div>
                    ))
                  ) : (
                    <p className="rounded-lg border border-dashed border-slate-200 p-3 text-xs text-slate-500">
                      No holidays are recorded for this month.
                    </p>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="grid gap-4 xl:grid-cols-[1fr_320px]">
            <Card className="rounded-xl border-slate-200 shadow-sm">
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-base">Staff Analytics</CardTitle>
                <Button variant="outline" size="sm" className="bg-white">This Month <ChevronDown className="size-4" /></Button>
              </CardHeader>
              <CardContent className="grid gap-3 md:grid-cols-2 2xl:grid-cols-5">
                <Card className="rounded-lg border-slate-200">
                  <CardHeader className="pb-1"><CardTitle className="text-sm">Headcount Trend</CardTitle></CardHeader>
                  <CardContent className="h-[150px]">
                    <p className="text-2xl font-bold">{stats.totalStaff} <span className="text-xs font-medium text-emerald-600">active view</span></p>
                    <ResponsiveContainer width="100%" height={90}>
                      <AreaChart data={analytics.headcountTrend}>
                        <Area type="monotone" dataKey="staff" stroke="#7c3aed" fill="#ede9fe" strokeWidth={2.5} />
                        <Tooltip />
                      </AreaChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>
                <DoughnutCard title="Department Distribution" data={analytics.departmentDistribution} center={String(departments.length)} />
                <Card className="rounded-lg border-slate-200">
                  <CardHeader className="pb-1"><CardTitle className="text-sm">Leave Usage</CardTitle></CardHeader>
                  <CardContent className="space-y-4">
                    <p className="text-2xl font-bold">{percent(stats.monthLeaveDays, stats.monthLeaveDays + stats.availableLeaveDays)}% <span className="text-xs font-medium text-emerald-600">used</span></p>
                    <div className="h-3 overflow-hidden rounded-full bg-emerald-100">
                      <div className="h-full rounded-full bg-emerald-500" style={{ width: `${percent(stats.monthLeaveDays, stats.monthLeaveDays + stats.availableLeaveDays)}%` }} />
                    </div>
                    <div className="flex justify-between text-xs text-slate-600">
                      <span>Taken <strong className="block text-slate-950">{stats.monthLeaveDays} days</strong></span>
                      <span>Available <strong className="block text-slate-950">{stats.availableLeaveDays} days</strong></span>
                    </div>
                  </CardContent>
                </Card>
                <Card className="rounded-lg border-slate-200">
                  <CardHeader className="pb-1"><CardTitle className="text-sm">Attendance Rate</CardTitle></CardHeader>
                  <CardContent className="space-y-4">
                    <p className="text-2xl font-bold">{stats.attendanceRate}% <span className="text-xs font-medium text-emerald-600">today</span></p>
                    <div className="h-3 overflow-hidden rounded-full bg-blue-100">
                      <div className="h-full rounded-full bg-blue-500" style={{ width: `${stats.attendanceRate}%` }} />
                    </div>
                    <div className="flex justify-between text-xs text-slate-600">
                      <span>Present <strong className="block text-slate-950">{stats.presentToday}</strong></span>
                      <span>Not clocked <strong className="block text-slate-950">{Math.max(stats.activeStaff - stats.presentToday, 0)}</strong></span>
                    </div>
                  </CardContent>
                </Card>
                <DoughnutCard title="Employment Type" data={analytics.employmentTypes} center={String(stats.totalStaff)} />
              </CardContent>
            </Card>
          </div>

          <Card className="rounded-xl border-slate-200 shadow-sm">
            <CardHeader className="gap-4 pb-3 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <CardTitle className="text-base">Employee Directory</CardTitle>
                <CardDescription>Showing {visibleStaff.length} of {filteredStaff.length} employees from the database</CardDescription>
              </div>
              <div className="flex flex-wrap gap-3">
                <div className="relative w-full sm:w-64">
                  <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                  <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    className="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-red-300"
                    placeholder="Search employees..."
                  />
                </div>
                <select
                  value={departmentFilter}
                  onChange={(event) => syncDepartmentFilter(event.currentTarget.value)}
                  className="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm"
                >
                  <option value="">All Departments</option>
                  {departments.map((department) => (
                    <option key={department.id} value={department.id}>{department.name}</option>
                  ))}
                </select>
                <select
                  value={statusFilter}
                  onChange={(event) => setStatusFilter(event.currentTarget.value)}
                  className="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm"
                >
                  <option value="">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
                <Button variant="outline" className="bg-white"><Filter className="size-4" /> Filters</Button>
                <Button variant="outline" className="bg-white"><Download className="size-4" /> Export</Button>
              </div>
            </CardHeader>
            <CardContent className="overflow-hidden rounded-lg border border-slate-200 p-0">
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                  <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-normal text-slate-500">
                    <tr>
                      <th className="px-5 py-3">Employee</th>
                      <th className="px-5 py-3">Department</th>
                      <th className="px-5 py-3">Position</th>
                      <th className="px-5 py-3">Email</th>
                      <th className="px-5 py-3">Status</th>
                      <th className="px-5 py-3">Employment Type</th>
                      <th className="px-5 py-3 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white">
                    {visibleStaff.map((employee) => (
                      <tr key={employee.id} className="hover:bg-slate-50">
                        <td className="px-5 py-3">
                          <div className="flex items-center gap-3">
                            <div className="flex size-9 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">
                              {employee.avatar_initials}
                            </div>
                            <div>
                              <p className="font-semibold text-slate-900">{employee.name}</p>
                              <p className="text-xs text-slate-500">{employee.employee_number}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-5 py-3 text-slate-600">{employee.department_name ?? "-"}</td>
                        <td className="px-5 py-3 text-slate-600">{employee.position}</td>
                        <td className="px-5 py-3 text-slate-600">{employee.email}</td>
                        <td className="px-5 py-3">
                          <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${statusClass(employee.status)}`}>
                            {formatStatus(employee.status)}
                          </span>
                        </td>
                        <td className="px-5 py-3">
                          <span className="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                            {employee.employment_type}
                          </span>
                        </td>
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-2">
                            <Button asChild variant="outline" size="icon" className="size-8 bg-white">
                              <Link href={staff.profile.url(employee.id)}><Eye className="size-4" /></Link>
                            </Button>
                            <Button asChild variant="outline" size="icon" className="size-8 bg-white">
                              <Link href={staff.edit.url(employee.id)}><Pencil className="size-4" /></Link>
                            </Button>
                            <Button variant="outline" size="icon" className="size-8 bg-white"><MoreHorizontal className="size-4" /></Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                    {visibleStaff.length === 0 ? (
                      <tr>
                        <td colSpan={7} className="px-5 py-8 text-center text-slate-500">No employees match the current filters.</td>
                      </tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
              <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-5 py-4 text-sm text-slate-500">
                <span>Showing 1 to {visibleStaff.length} of {filteredStaff.length} results</span>
                <div className="flex items-center gap-2">
                  <Button variant="outline" size="icon" className="size-8 bg-white"><ChevronLeft className="size-4" /></Button>
                  <Button size="icon" className="size-8 bg-red-600 hover:bg-red-700">1</Button>
                  <Button variant="outline" size="icon" className="size-8 bg-white"><ChevronRight className="size-4" /></Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <div className="grid gap-4 lg:grid-cols-3">
            {departments.slice(0, 6).map((department) => (
              <Card key={department.id} className="rounded-xl border-slate-200 shadow-sm">
                <CardHeader>
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <CardTitle className="text-base">{department.name}</CardTitle>
                      <CardDescription>{department.description || "Department staff allocation"}</CardDescription>
                    </div>
                    <div className="flex size-10 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                      <Users className="size-5" />
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="flex items-center justify-between gap-4">
                  <div>
                    <p className="text-3xl font-bold">{department.staff_count}</p>
                    <p className="text-xs text-slate-500">staff members assigned</p>
                  </div>
                  <div className="flex gap-2">
                    <Button asChild size="sm" className="bg-red-600 hover:bg-red-700">
                      <Link href={staff.create.url({ query: { department_id: department.id } })}>
                        <UserPlus className="size-4" />
                        Add
                      </Link>
                    </Button>
                    <Button size="sm" variant="outline" onClick={() => syncDepartmentFilter(String(department.id))}>View</Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </div>

      <Dialog open={actionOpen} onOpenChange={setActionOpen}>
        <DialogContent className="sm:max-w-[520px]">
          <DialogHeader>
            <DialogTitle>Leave Decision</DialogTitle>
            <DialogDescription>
              {selectedLeave?.staff_member_name ?? "Employee"} - {selectedLeave?.start_date ?? "-"} to {selectedLeave?.end_date ?? "-"}
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={submitAction} className="grid gap-3">
            <textarea
              rows={3}
              value={comment}
              onChange={(event) => setComment(event.target.value)}
              placeholder="Comment (optional)"
              className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
            />
            <button type="submit" className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700">
              Confirm
            </button>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );

  function Insight({ icon: Icon, text, subtext }: { icon: typeof Users; text: string; subtext: string }) {
    return (
      <div className="flex gap-3">
        <div className="flex size-9 items-center justify-center rounded-lg bg-white text-red-500 shadow-sm">
          <Icon className="size-4" />
        </div>
        <div>
          <p className="text-sm font-semibold text-slate-900">{text}</p>
          <p className="text-xs text-slate-500">{subtext}</p>
        </div>
      </div>
    );
  }

  function WorkforceTile({
    label,
    value,
    icon: Icon,
    tone,
  }: {
    label: string;
    value: number;
    icon: typeof Users;
    tone: string;
  }) {
    return (
      <div className={`flex min-h-32 flex-col items-center justify-center rounded-xl p-4 text-center ${tone}`}>
        <div className="mb-3 flex size-10 items-center justify-center rounded-lg bg-white/70">
          <Icon className="size-5" />
        </div>
        <p className="text-3xl font-bold">{value}</p>
        <p className="text-xs font-semibold">{label}</p>
      </div>
    );
  }

  function ActionCard({
    title,
    value,
    hint,
    icon: Icon,
    tone,
    href,
  }: {
    title: string;
    value: string;
    hint: string;
    icon: typeof Users;
    tone: string;
    href: string;
  }) {
    return (
      <div className={`rounded-xl border border-slate-200 p-4 text-center ${tone}`}>
        <div className="mx-auto mb-3 flex size-10 items-center justify-center rounded-lg bg-white/80">
          <Icon className="size-5" />
        </div>
        <p className="text-sm font-semibold text-slate-900">{title}</p>
        <p className="mt-2 text-sm font-medium">{value}</p>
        <p className="text-xs text-slate-500">{hint}</p>
        <Button asChild variant="outline" size="sm" className="mt-4 h-8 w-full bg-white">
          <Link href={href}>Review</Link>
        </Button>
      </div>
    );
  }

  function Legend({ color, label }: { color: string; label: string }) {
    return (
      <span className="flex items-center gap-2">
        <span className={`size-3 rounded-full ${color}`} />
        {label}
      </span>
    );
  }
}
