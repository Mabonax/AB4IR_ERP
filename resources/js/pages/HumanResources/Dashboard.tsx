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
  Star,
  UserCheck,
  UserPlus,
  Users,
  XCircle,
} from "lucide-react";
import { useMemo, useState, type ComponentType, type FormEvent, type ReactNode } from "react";
import { Area, AreaChart, Bar, BarChart, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";

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

const pieColors = ["#ef233c", "#f97316", "#8b5cf6", "#2563eb", "#22c55e", "#14b8a6"];
const leaveTypeClass: Record<string, string> = {
  annual: "bg-emerald-500",
  sick: "bg-red-500",
  personal: "bg-blue-500",
  maternity: "bg-violet-500",
  family_responsibility: "bg-blue-500",
};

function percent(value: number, total: number) {
  return total <= 0 ? 0 : Math.round((value / total) * 100);
}

function formatStatus(status: string) {
  return status
    .split("_")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(" ");
}

function eventTone(event: LeaveCalendarEvent) {
  if (event.status !== "hr_approved") {
    return "bg-amber-500";
  }

  return leaveTypeClass[event.type] ?? "bg-blue-500";
}

function CardShell({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <section className={`rounded-lg border border-slate-200 bg-white shadow-sm ${className}`}>{children}</section>;
}

function SectionTitle({ title, description }: { title: string; description?: string }) {
  return (
    <div>
      <h2 className="text-lg font-bold text-slate-950">{title}</h2>
      {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
    </div>
  );
}

function ActionButton({
  children,
  href,
  variant = "outline",
}: {
  children: ReactNode;
  href?: string;
  variant?: "primary" | "outline";
}) {
  const className = variant === "primary"
    ? "inline-flex h-11 items-center gap-2 rounded-md bg-red-600 px-4 text-sm font-bold text-white shadow-sm shadow-red-100 hover:bg-red-700"
    : "inline-flex h-11 items-center gap-2 rounded-md border border-slate-200 bg-white px-4 text-sm font-bold text-slate-800 shadow-sm hover:bg-slate-50";

  return href ? <Link href={href} className={className}>{children}</Link> : <button type="button" className={className}>{children}</button>;
}

function StatCard({
  title,
  value,
  note,
  icon: Icon,
  tone,
}: {
  title: string;
  value: number | string;
  note: string;
  icon: ComponentType<{ className?: string }>;
  tone: string;
}) {
  return (
    <CardShell className="min-h-[170px] bg-gradient-to-br from-white to-slate-50 p-5">
      <div className="flex items-start justify-between gap-3">
        <p className="text-sm font-bold text-slate-950">{title}</p>
        <span className={`flex h-11 w-11 items-center justify-center rounded-full ${tone}`}>
          <Icon className="h-5 w-5" />
        </span>
      </div>
      <div className="mt-6 text-3xl font-bold leading-none text-slate-950">{value}</div>
      <p className={`mt-5 text-xs font-bold ${note.startsWith("+") || note.startsWith("0%") ? "text-emerald-600" : "text-slate-500"}`}>{note}</p>
    </CardShell>
  );
}

function Insight({ icon: Icon, title, detail }: { icon: ComponentType<{ className?: string }>; title: string; detail: string }) {
  return (
    <div className="flex gap-3 border-b border-orange-100 pb-4 last:border-b-0 last:pb-0">
      <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-red-500 shadow-sm">
        <Icon className="h-4 w-4" />
      </span>
      <div>
        <p className="text-sm font-bold text-slate-950">{title}</p>
        <p className="text-xs text-slate-500">{detail}</p>
      </div>
    </div>
  );
}

function WorkforceTile({ label, value, icon: Icon, tone }: { label: string; value: number; icon: ComponentType<{ className?: string }>; tone: string }) {
  return (
    <div className={`flex min-h-[112px] flex-col items-center justify-center rounded-lg p-4 text-center ${tone}`}>
      <Icon className="h-6 w-6" />
      <p className="mt-5 text-2xl font-bold text-slate-950">{value}</p>
      <p className="mt-1 text-xs font-bold text-slate-700">{label}</p>
    </div>
  );
}

function PendingAction({ title, hint, value, icon: Icon, tone }: { title: string; hint: string; value: number; icon: ComponentType<{ className?: string }>; tone: string }) {
  return (
    <div className="flex items-center gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <span className={`flex h-14 w-14 shrink-0 items-center justify-center rounded-lg ${tone}`}>
        <Icon className="h-6 w-6" />
      </span>
      <div className="min-w-0 flex-1">
        <p className="font-bold text-slate-950">{title}</p>
        <p className="text-xs text-slate-500">{hint}</p>
      </div>
      <span className="text-2xl font-bold text-slate-950">{value}</span>
    </div>
  );
}

function Donut({ data, center, caption }: { data: PiePoint[]; center: string; caption: string }) {
  const safeData = data.length > 0 && data.some((item) => (item.value ?? item.staff ?? 0) > 0)
    ? data.map((item) => ({ ...item, value: item.value ?? item.staff ?? 0 }))
    : [{ name: "No data", value: 1 }];

  return (
    <div className="relative h-40">
      <ResponsiveContainer width="100%" height="100%">
        <PieChart>
          <Pie data={safeData} dataKey="value" innerRadius={48} outerRadius={72} strokeWidth={0}>
            {safeData.map((entry, index) => (
              <Cell key={entry.name} fill={pieColors[index % pieColors.length]} />
            ))}
          </Pie>
        </PieChart>
      </ResponsiveContainer>
      <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
        <span className="text-2xl font-bold text-slate-950">{center}</span>
        <span className="text-xs text-slate-500">{caption}</span>
      </div>
    </div>
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

  const visibleStaff = filteredStaff.slice(0, 5);
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
  const leaveUsage = percent(stats.monthLeaveDays, stats.monthLeaveDays + stats.availableLeaveDays);
  const contractCount = analytics.employmentTypes.find((item) => item.name === "Contract")?.value ?? 0;
  const permanentCount = analytics.employmentTypes.find((item) => item.name === "Permanent")?.value ?? 0;

  const openAction = (leave: PendingLeaveApproval, type: typeof actionType) => {
    setSelectedLeave(leave);
    setActionType(type);
    setComment("");
    setActionOpen(true);
  };

  const submitAction = (event: FormEvent) => {
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

      <div className="min-h-screen bg-slate-50/40 px-4 py-6 text-slate-950 md:px-6 lg:px-8">
        <div className="mx-auto max-w-[1400px] space-y-5">
          <div className="rounded-lg bg-[radial-gradient(circle_at_72%_10%,rgba(248,113,113,.18),transparent_28%),linear-gradient(110deg,#fff_0%,#fff7ed_100%)] p-5">
            <div className="flex flex-wrap items-start justify-between gap-5">
              <div className="flex items-center gap-5">
                <span className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                  <Users className="h-8 w-8" />
                </span>
                <div>
                  <h1 className="text-3xl font-bold tracking-normal text-slate-950">Human Resources</h1>
                  <p className="mt-1 text-sm text-slate-500">Manage your people, performance and workforce.</p>
                </div>
              </div>
              <div className="flex flex-1 flex-wrap justify-end gap-3">
                <div className="relative min-w-[300px] max-w-[430px] flex-1">
                  <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    className="h-11 w-full rounded-md border border-slate-200 bg-white pl-10 pr-20 text-sm shadow-sm outline-none focus:border-red-300 focus:ring-2 focus:ring-red-100"
                    placeholder="Search employees, departments..."
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Ctrl + K</span>
                </div>
                <ActionButton href="/leave-requests"><CalendarCheck2 className="h-4 w-4" />Leave Requests</ActionButton>
                <ActionButton href={staff.create.url()} variant="primary"><Plus className="h-4 w-4" />Add Employee</ActionButton>
                <ActionButton href="/human-resources/attendance/report/pdf"><FileText className="h-4 w-4" />Reports<ChevronDown className="h-4 w-4" /></ActionButton>
                <ActionButton>More<ChevronDown className="h-4 w-4" /></ActionButton>
                <ActionButton href="/staff-departments"><Users className="h-4 w-4" />Departments</ActionButton>
              </div>
            </div>
          </div>

          <div className="grid gap-4 lg:grid-cols-[1fr_230px]">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
              <StatCard title="Total Staff" value={stats.totalStaff} note="+10% vs last month" icon={Users} tone="bg-violet-50 text-violet-600" />
              <StatCard title="Present Today" value={stats.presentToday} note={`${stats.attendanceRate}% attendance`} icon={UserCheck} tone="bg-emerald-50 text-emerald-600" />
              <StatCard title="On Leave" value={stats.onLeaveToday} note={stats.onLeaveToday ? "Staff on leave today" : "No staff on leave"} icon={CalendarDays} tone="bg-orange-50 text-orange-600" />
              <StatCard title="Pending Approvals" value={stats.pendingApprovals} note="All requests processed" icon={ClipboardList} tone="bg-pink-50 text-pink-600" />
              <StatCard title="New Hires" value={stats.newEmployees} note="Last 30 days" icon={UserPlus} tone="bg-sky-50 text-sky-600" />
            </div>
            <CardShell className="bg-gradient-to-br from-orange-50 to-white p-5">
              <SectionTitle title="HR Insights" />
              <div className="mt-5 space-y-4">
                <Insight icon={UserCheck} title={`${stats.attendanceRate}% attendance today`} detail="Based on active staff" />
                <Insight icon={HeartPulse} title={`${stats.pendingApprovals} approvals pending`} detail="Manager workflow" />
                <Insight icon={BriefcaseBusiness} title={`${stats.availableLeaveDays} leave balance`} detail="Average annual leave" />
                <Link href="/human-resources/attendance" className="inline-flex items-center gap-2 text-sm font-bold text-red-600">
                  View full report <ChevronRight className="h-4 w-4" />
                </Link>
              </div>
            </CardShell>
          </div>

          <div className="grid gap-4 xl:grid-cols-[1fr_0.85fr_310px]">
            <CardShell className="p-5">
              <div className="flex items-center justify-between gap-4">
                <SectionTitle title="Workforce Overview" />
                <div className="flex gap-2 text-xs font-bold">
                  <button className="rounded-full bg-red-600 px-4 py-2 text-white">Today</button>
                  <button className="rounded-full bg-slate-100 px-4 py-2 text-slate-700">This Week</button>
                  <button className="rounded-full bg-slate-100 px-4 py-2 text-slate-700">This Month</button>
                </div>
              </div>
              <div className="mt-5 grid gap-3 md:grid-cols-5">
                <WorkforceTile label="Present" value={workforce.present} icon={CheckCircle2} tone="bg-emerald-50 text-emerald-600" />
                <WorkforceTile label="On Leave" value={workforce.onLeave} icon={UserCheck} tone="bg-orange-50 text-orange-600" />
                <WorkforceTile label="Absent" value={workforce.absent} icon={XCircle} tone="bg-pink-50 text-pink-600" />
                <WorkforceTile label="Pending" value={workforce.pendingApprovals} icon={CalendarCheck2} tone="bg-blue-50 text-blue-600" />
                <WorkforceTile label="New" value={workforce.newEmployees} icon={UserPlus} tone="bg-violet-50 text-violet-600" />
              </div>
              <div className="mt-6 h-[220px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={[
                    { label: "Present", value: workforce.present, fill: "#22c55e" },
                    { label: "On Leave", value: workforce.onLeave, fill: "#f97316" },
                    { label: "Absent", value: workforce.absent, fill: "#e11d48" },
                    { label: "Pending", value: workforce.pendingApprovals, fill: "#2563eb" },
                    { label: "New", value: workforce.newEmployees, fill: "#8b5cf6" },
                  ]}>
                    <XAxis dataKey="label" tickLine={false} axisLine={false} tick={{ fontSize: 12 }} />
                    <YAxis tickLine={false} axisLine={false} tick={{ fontSize: 12 }} />
                    <Tooltip />
                    <Bar dataKey="value" radius={[6, 6, 0, 0]}>
                      {["#22c55e", "#f97316", "#e11d48", "#2563eb", "#8b5cf6"].map((color) => <Cell key={color} fill={color} />)}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </CardShell>

            <CardShell className="p-5">
              <div className="flex items-center justify-between gap-4">
                <SectionTitle title="Pending Actions" />
                <Link href="/leave-requests" className="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">View all</Link>
              </div>
              <div className="mt-5 space-y-4">
                <PendingAction title="Leave Requests" hint="Requires approval" value={pendingLeaveApprovals.length} icon={CalendarCheck2} tone="bg-red-50 text-red-600" />
                <PendingAction title="Contract Renewals" hint="Due soon" value={contractCount} icon={FileText} tone="bg-amber-50 text-amber-600" />
                <PendingAction title="Performance Reviews" hint="Managers action" value={analytics.staffMix.find((item) => item.name === "Managers")?.value ?? 0} icon={Star} tone="bg-violet-50 text-violet-600" />
                <PendingAction title="Probation Reviews" hint="In 30 days" value={stats.newEmployees} icon={CheckCircle2} tone="bg-emerald-50 text-emerald-600" />
              </div>
              {pendingLeaveApprovals.length > 0 ? (
                <div className="mt-5 space-y-3 border-t border-slate-100 pt-4">
                  {pendingLeaveApprovals.slice(0, 2).map((leave) => (
                    <div key={leave.id} className="rounded-lg bg-slate-50 p-3 text-sm">
                      <p className="font-bold text-slate-950">{leave.staff_member_name ?? "Employee"}</p>
                      <p className="text-xs text-slate-500">{leave.leave_type_label} - {leave.total_days} days</p>
                      <div className="mt-3 flex gap-2">
                        {canManageManagerLeave ? <button onClick={() => openAction(leave, "manager_approve")} className="rounded-md bg-emerald-600 px-3 py-1 text-xs font-bold text-white">Approve</button> : null}
                        {canManageHrLeave ? <button onClick={() => openAction(leave, "hr_approve")} className="rounded-md bg-red-600 px-3 py-1 text-xs font-bold text-white">HR approve</button> : null}
                      </div>
                    </div>
                  ))}
                </div>
              ) : null}
            </CardShell>

            <CardShell className="p-5">
              <div className="flex items-center justify-between gap-4">
                <SectionTitle title="Leave Calendar" />
              </div>
              <div className="mt-5 flex items-center justify-between">
                <p className="font-bold text-slate-950">{leaveCalendar.monthLabel}</p>
                <div className="flex gap-2">
                  <ChevronLeft className="h-5 w-5 text-slate-700" />
                  <ChevronRight className="h-5 w-5 text-slate-700" />
                </div>
              </div>
              <div className="mt-4 grid grid-cols-7 gap-1 text-center text-xs">
                {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => <div key={day} className="py-2 font-medium text-slate-500">{day}</div>)}
                {calendarCells.map((day, index) => {
                  const events = day ? leaveEventsByDay[day] ?? [] : [];
                  return (
                    <div key={`${day ?? "blank"}-${index}`} className={`min-h-8 rounded-md p-1 ${day === leaveCalendar.today ? "bg-red-600 font-bold text-white" : "text-slate-700"}`}>
                      {day ? <span>{day}</span> : null}
                      <div className="mt-1 flex justify-center gap-0.5">
                        {events.slice(0, 2).map((event) => <span key={event.id} className={`h-1.5 w-1.5 rounded-full ${eventTone(event)}`} />)}
                      </div>
                    </div>
                  );
                })}
              </div>
              <div className="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-600">
                <Legend color="bg-emerald-500" label="Annual Leave" />
                <Legend color="bg-red-500" label="Sick Leave" />
                <Legend color="bg-blue-500" label="Personal Leave" />
                <Legend color="bg-amber-500" label="Pending" />
              </div>
              <div className="mt-5">
                <p className="mb-3 flex items-center gap-2 text-sm font-bold text-slate-950"><CalendarDays className="h-4 w-4" />Upcoming Holidays</p>
                {leaveCalendar.holidays.length > 0 ? (
                  leaveCalendar.holidays.map((holiday) => (
                    <div key={`${holiday.date}-${holiday.label}`} className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                      <p className="font-bold">{holiday.label}</p>
                      <p className="text-xs text-slate-500">{holiday.date ?? "Date pending"}</p>
                    </div>
                  ))
                ) : (
                  <p className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-500">No holidays recorded this month</p>
                )}
              </div>
            </CardShell>
          </div>

          <CardShell className="p-5">
            <div className="flex items-center justify-between gap-4">
              <SectionTitle title="Staff Analytics" />
              <button className="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">This Month <ChevronDown className="inline h-4 w-4" /></button>
            </div>
            <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
              <MiniAnalytics title="Headcount Trend">
                <p className="text-3xl font-bold">{stats.totalStaff}</p>
                <p className="text-sm font-bold text-emerald-600">Active employees</p>
                <ResponsiveContainer width="100%" height={80}>
                  <AreaChart data={analytics.headcountTrend}>
                    <Area type="monotone" dataKey="staff" stroke="#8b5cf6" fill="#ede9fe" strokeWidth={3} />
                    <Tooltip />
                  </AreaChart>
                </ResponsiveContainer>
              </MiniAnalytics>
              <MiniAnalytics title="Department Distribution">
                <Donut data={analytics.departmentDistribution.map((item) => ({ ...item, value: item.staff ?? item.value }))} center={String(stats.totalStaff)} caption="Total" />
              </MiniAnalytics>
              <MiniAnalytics title="Employment Type">
                <Donut data={analytics.employmentTypes} center={String(stats.totalStaff)} caption="Total" />
                <div className="mt-2 flex justify-center gap-4 text-xs">
                  <span className="font-bold text-blue-600">Permanent {permanentCount}</span>
                  <span className="font-bold text-orange-600">Contract {contractCount}</span>
                </div>
              </MiniAnalytics>
              <MiniAnalytics title="Attendance Rate">
                <p className="text-3xl font-bold">{stats.attendanceRate}% <span className="text-sm text-emerald-600">Today</span></p>
                <div className="mt-6 h-4 rounded-full bg-blue-100">
                  <div className="h-4 rounded-full bg-blue-500" style={{ width: `${stats.attendanceRate}%` }} />
                </div>
                <div className="mt-5 flex justify-between text-xs text-slate-600">
                  <span>Present <strong className="block text-slate-950">{stats.presentToday}</strong></span>
                  <span>Not checked <strong className="block text-slate-950">{Math.max(stats.activeStaff - stats.presentToday, 0)}</strong></span>
                </div>
              </MiniAnalytics>
              <MiniAnalytics title="Leave Usage" className="md:col-span-2">
                <p className="text-3xl font-bold">{leaveUsage}% <span className="text-sm text-emerald-600">Used</span></p>
                <div className="mt-4 h-4 rounded-full bg-emerald-100">
                  <div className="h-4 rounded-full bg-emerald-400" style={{ width: `${leaveUsage}%` }} />
                </div>
                <div className="mt-4 flex justify-between text-xs text-slate-600">
                  <span>Taken <strong className="block text-slate-950">{stats.monthLeaveDays} days</strong></span>
                  <span>Available <strong className="block text-slate-950">{stats.availableLeaveDays} days</strong></span>
                </div>
              </MiniAnalytics>
            </div>
          </CardShell>

          <CardShell className="p-5">
            <div className="mb-5 flex flex-wrap items-start justify-between gap-4">
              <SectionTitle title="Employee Directory" description={`Showing ${visibleStaff.length} of ${filteredStaff.length} employees`} />
              <div className="flex flex-wrap gap-3">
                <div className="relative w-64">
                  <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input value={query} onChange={(event) => setQuery(event.target.value)} className="h-10 w-full rounded-md border border-slate-200 pl-10 pr-3 text-sm" placeholder="Search employees..." />
                </div>
                <select value={departmentFilter} onChange={(event) => syncDepartmentFilter(event.currentTarget.value)} className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm">
                  <option value="">All Departments</option>
                  {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
                </select>
                <select value={statusFilter} onChange={(event) => setStatusFilter(event.currentTarget.value)} className="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm">
                  <option value="">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
                <button className="inline-flex h-10 items-center gap-2 rounded-md border border-slate-200 px-3 text-sm font-bold"><Filter className="h-4 w-4" />Filters</button>
                <button className="inline-flex h-10 items-center gap-2 rounded-md border border-slate-200 px-3 text-sm font-bold"><Download className="h-4 w-4" />Export</button>
                <button className="inline-flex h-10 items-center gap-2 rounded-md border border-red-100 px-3 text-sm font-bold text-red-600"><Users className="h-4 w-4" />Column view<ChevronDown className="h-4 w-4" /></button>
              </div>
            </div>
            <div className="overflow-hidden rounded-lg border border-slate-200">
              <div className="overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                  <thead className="bg-slate-100 text-xs font-bold uppercase text-slate-500">
                    <tr>
                      {["Employee", "Department", "Position", "Email", "Status", "Employment Type", "Actions"].map((heading) => (
                        <th key={heading} className="px-5 py-3">{heading}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white">
                    {visibleStaff.map((employee) => (
                      <tr key={employee.id}>
                        <td className="px-5 py-3">
                          <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">{employee.avatar_initials}</span>
                            <div>
                              <p className="font-bold text-slate-950">{employee.name}</p>
                              <p className="text-xs text-slate-500">{employee.employee_number}</p>
                            </div>
                          </div>
                        </td>
                        <td className="px-5 py-3 text-slate-600">{employee.department_name ?? "-"}</td>
                        <td className="px-5 py-3 text-slate-600">{employee.position}</td>
                        <td className="px-5 py-3 text-slate-600">{employee.email}</td>
                        <td className="px-5 py-3">
                          <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{formatStatus(employee.status)}</span>
                        </td>
                        <td className="px-5 py-3">
                          <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{employee.employment_type}</span>
                        </td>
                        <td className="px-5 py-3">
                          <div className="flex gap-2">
                            <Link href={staff.profile.url(employee.id)} className="rounded-md border border-slate-200 p-2"><Eye className="h-4 w-4" /></Link>
                            <Link href={staff.edit.url(employee.id)} className="rounded-md border border-slate-200 p-2"><Pencil className="h-4 w-4" /></Link>
                            <button className="rounded-md border border-slate-200 p-2"><MoreHorizontal className="h-4 w-4" /></button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="flex items-center justify-between border-t border-slate-200 px-5 py-4 text-sm text-slate-500">
                <span>Showing 1 to {visibleStaff.length} of {filteredStaff.length} results</span>
                <div className="flex gap-2">
                  <button className="rounded-md border border-slate-200 p-2"><ChevronLeft className="h-4 w-4" /></button>
                  <button className="rounded-md bg-red-600 px-3 py-2 font-bold text-white">1</button>
                  <button className="rounded-md border border-slate-200 px-3 py-2">2</button>
                  <button className="rounded-md border border-slate-200 px-3 py-2">3</button>
                  <button className="rounded-md border border-slate-200 p-2"><ChevronRight className="h-4 w-4" /></button>
                </div>
              </div>
            </div>
          </CardShell>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            {departments.slice(0, 5).map((department, index) => (
              <CardShell key={department.id} className="p-5">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <h3 className="font-bold text-slate-950">{department.name}</h3>
                    <p className="mt-6 text-3xl font-bold">{department.staff_count}</p>
                    <p className="text-xs text-slate-500">{department.staff_count === 1 ? "staff member" : "staff members"}</p>
                  </div>
                  <span className={`flex h-9 w-9 items-center justify-center rounded-full ${index % 2 === 0 ? "bg-blue-50 text-blue-600" : "bg-orange-50 text-orange-600"}`}>
                    <Users className="h-5 w-5" />
                  </span>
                </div>
                <div className="mt-5 h-2 rounded-full bg-slate-100">
                  <div className="h-2 rounded-full bg-emerald-500" style={{ width: `${percent(department.staff_count, stats.totalStaff)}%` }} />
                </div>
                <p className="mt-2 text-right text-xs text-slate-500">{percent(department.staff_count, stats.totalStaff)}% of total</p>
                <div className="mt-5 flex gap-3">
                  <Link href={staff.create.url({ query: { department_id: department.id } })} className="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-md border border-red-200 text-sm font-bold text-red-600"><Plus className="h-4 w-4" />Add</Link>
                  <button onClick={() => syncDepartmentFilter(String(department.id))} className="h-9 flex-1 rounded-md border border-slate-200 text-sm font-bold text-slate-700">View</button>
                </div>
              </CardShell>
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
            <button type="submit" className="rounded-md bg-red-600 px-3 py-2 text-sm font-bold text-white hover:bg-red-700">
              Confirm
            </button>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}

function MiniAnalytics({ title, children, className = "" }: { title: string; children: ReactNode; className?: string }) {
  return (
    <div className={`rounded-lg border border-slate-200 bg-white p-5 ${className}`}>
      <h3 className="font-bold text-slate-950">{title}</h3>
      <div className="mt-5">{children}</div>
    </div>
  );
}

function Legend({ color, label }: { color: string; label: string }) {
  return (
    <span className="flex items-center gap-2">
      <span className={`h-2 w-2 rounded-full ${color}`} />
      {label}
    </span>
  );
}
