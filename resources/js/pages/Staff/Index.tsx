import { Head, Link } from "@inertiajs/react";
import { Building2, CheckCircle2, Filter, MoreHorizontal, Plus, Search, UserCircle, Users } from "lucide-react";
import { type ComponentType } from "react";

import { DomainNav } from "@/components/domain-nav";
import { staffNavItems } from "@/config/domain-nav/staff";
import AppLayout from "@/layouts/app-layout";
import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff", href: "/staff" },
  { title: "List", href: "/staff" },
];

type StaffRow = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  employee_number: string | null;
  status: string;
  is_manager: boolean;
  department_name: string | null;
  manager_name: string | null;
};

type MetricCard = {
  label: string;
  value: number;
  caption: string;
  Icon: ComponentType<{ className?: string }>;
  tone: string;
};

export default function StaffIndex({
  staffMembers,
  departments = [],
  selectedDepartmentId,
}: {
  staffMembers: { data: StaffRow[] };
  departments?: Array<{ id: number; name: string }>;
  selectedDepartmentId: number | null;
}) {
  const rows = staffMembers.data;
  const active = rows.filter((row) => row.status === "active").length;
  const managerCount = rows.filter((row) => row.is_manager).length;
  const departmentCount = new Set(rows.map((row) => row.department_name).filter(Boolean)).size || departments.length;
  const metrics: MetricCard[] = [
    { label: "Total Staff", value: rows.length, caption: "All departments", Icon: Users, tone: "bg-orange-50 text-orange-600" },
    { label: "Active Staff", value: active, caption: "100% of total", Icon: CheckCircle2, tone: "bg-emerald-50 text-emerald-600" },
    { label: "Departments", value: departmentCount, caption: "With active staff", Icon: Building2, tone: "bg-violet-50 text-violet-600" },
    { label: "Managers", value: managerCount, caption: `${rows.length ? Math.round((managerCount / rows.length) * 100) : 0}% of total staff`, Icon: UserCircle, tone: "bg-orange-50 text-orange-600" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Staff List" />

      <div className="space-y-5 p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight text-slate-950">Staff List</h1>
            <p className="mt-1 text-sm text-slate-500">View and manage all staff members in your organization.</p>
          </div>
          <Link
            href={staff.create.url(selectedDepartmentId ? { query: { department_id: selectedDepartmentId } } : undefined)}
            className="inline-flex h-11 items-center gap-2 rounded-lg bg-red-600 px-5 text-sm font-semibold text-white hover:bg-red-700"
          >
            <Plus className="h-4 w-4" />
            Add Staff Member
          </Link>
        </div>

        <DomainNav items={staffNavItems} />

        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {metrics.map(({ label, value, caption, Icon, tone }) => (
            <div key={label} className="rounded-xl border bg-white p-6 shadow-sm">
              <div className="flex items-center gap-5">
                <span className={`flex size-14 items-center justify-center rounded-full ${tone}`}><Icon className="h-7 w-7" /></span>
                <div><div className="text-sm text-slate-600">{label}</div><div className="mt-1 text-3xl font-semibold text-slate-950">{value}</div><div className="text-sm text-slate-500">{caption}</div></div>
              </div>
            </div>
          ))}
        </section>

        <section className="rounded-2xl border bg-white shadow-sm">
          <div className="grid gap-3 border-b p-4 xl:grid-cols-[minmax(0,1fr)_190px_170px_190px_auto_auto]">
            <label className="flex h-11 items-center gap-2 rounded-lg border px-3">
              <Search className="h-4 w-4 text-slate-400" />
              <input className="min-w-0 flex-1 text-sm outline-none" placeholder="Search by name, email, or employee ID..." />
            </label>
            <select className="h-11 rounded-lg border px-3 text-sm"><option>All Departments</option>{departments.map((department) => <option key={department.id}>{department.name}</option>)}</select>
            <select className="h-11 rounded-lg border px-3 text-sm"><option>All Roles</option><option>Managers</option><option>Users</option></select>
            <select className="h-11 rounded-lg border px-3 text-sm"><option>All Statuses</option><option>Active</option><option>Inactive</option></select>
            <button className="inline-flex h-11 items-center gap-2 rounded-lg border px-4 text-sm font-medium"><Filter className="h-4 w-4" />Filters</button>
            <button className="inline-flex h-11 items-center rounded-lg border px-4 text-sm font-medium">Reset</button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[1120px] text-sm">
              <thead className="border-b bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>{["Staff Member", "Role", "Email", "Phone", "Employee ID", "Department", "Manager", "Status", "Actions"].map((heading) => <th key={heading} className="px-4 py-3 font-semibold">{heading}</th>)}</tr>
              </thead>
              <tbody className="divide-y">
                {rows.map((row) => {
                  const initials = `${row.first_name?.[0] ?? ""}${row.last_name?.[0] ?? ""}`.toUpperCase();
                  return (
                    <tr key={row.id} className="hover:bg-slate-50">
                      <td className="px-4 py-4"><div className="flex items-center gap-3"><span className="flex size-10 items-center justify-center rounded-full bg-orange-50 font-semibold text-orange-600">{initials}</span><div><div className="font-semibold text-slate-950">{row.first_name} {row.last_name}</div><div className="text-xs text-slate-500">{row.is_manager ? "Manager" : "User"}</div></div></div></td>
                      <td className="px-4 py-4">{row.is_manager ? "Manager" : "User"}</td>
                      <td className="px-4 py-4">{row.email}</td>
                      <td className="px-4 py-4">{row.phone ?? "-"}</td>
                      <td className="px-4 py-4">{row.employee_number ?? "-"}</td>
                      <td className="px-4 py-4"><div className="flex items-center gap-2"><span className="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><Building2 className="h-4 w-4" /></span>{row.department_name ?? "-"}</div></td>
                      <td className="px-4 py-4">{row.manager_name ?? "-"}</td>
                      <td className="px-4 py-4"><span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{row.status}</span></td>
                      <td className="px-4 py-4"><Link href={`/staff/${row.id}/profile`} className="inline-flex size-10 items-center justify-center rounded-full border text-slate-600"><MoreHorizontal className="h-5 w-5" /></Link></td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t px-4 py-3 text-sm text-slate-500">
            <span>Showing 1 to {rows.length} of {rows.length} staff members</span>
            <div className="flex items-center gap-2"><select className="h-10 rounded-lg border px-3"><option>10 per page</option></select><button className="size-9 rounded-lg border">&lt;</button><button className="size-9 rounded-lg bg-red-600 text-white">1</button><button className="size-9 rounded-lg border">&gt;</button></div>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
