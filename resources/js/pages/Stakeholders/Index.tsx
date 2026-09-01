import { useMemo, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import { Building2, CheckCircle2, ChevronsUpDown, Eye, Filter, Hourglass, Mail, Pencil, Phone, Plus, Search, Trash2, UsersRound } from "lucide-react";

import AppLayout from "@/layouts/app-layout";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { StakeholderModelFormConfig } from "@/config/forms/stakeholder-model-form";
import stakeholders from "@/routes/stakeholders";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Stakeholders", href: stakeholders.index() },
];

const avatarTones = [
  "bg-red-50 text-red-600",
  "bg-emerald-50 text-emerald-600",
  "bg-violet-50 text-violet-600",
  "bg-blue-50 text-blue-600",
];

function initials(value: string | null | undefined) {
  const words = String(value ?? "").trim().split(/\s+/).filter(Boolean);
  if (words.length === 0) return "--";
  if (words.length === 1) return words[0].slice(0, 2).toUpperCase();

  return `${words[0][0]}${words[1][0]}`.toUpperCase();
}

function StatCard({ label, value, note, icon: Icon, tone }: { label: string; value: number; note: string; icon: any; tone: string }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center gap-4">
        <span className={`inline-flex h-14 w-14 items-center justify-center rounded-xl ${tone}`}>
          <Icon className="h-7 w-7" />
        </span>
        <div>
          <p className="text-sm text-slate-500">{label}</p>
          <p className="mt-2 text-3xl font-semibold leading-none text-slate-950">{value}</p>
          <p className="mt-4 text-sm text-slate-500">{note}</p>
        </div>
      </div>
    </section>
  );
}

export default function StakeholderIndex({
  stakeholders: stakeholderPagination,
}: {
  stakeholders: { data: any[] };
}) {
  const [editOpen, setEditOpen] = useState(false);
  const [selectedStakeholder, setSelectedStakeholder] = useState<any | null>(null);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [stakeholderToDelete, setStakeholderToDelete] = useState<any | null>(null);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [organization, setOrganization] = useState("all");
  const rows = stakeholderPagination.data;

  const organizations = useMemo(
    () => Array.from(new Set(rows.map((row) => row.organization_name).filter(Boolean))).sort(),
    [rows],
  );

  const filteredRows = useMemo(() => {
    const term = search.trim().toLowerCase();

    return rows.filter((row) => {
      const matchesSearch = !term || [
        row.organization_name,
        row.name,
        row.email,
        row.contact_number,
      ].some((value) => String(value ?? "").toLowerCase().includes(term));
      const matchesStatus = status === "all" || String(row.status ?? "").toLowerCase() === status;
      const matchesOrganization = organization === "all" || row.organization_name === organization;

      return matchesSearch && matchesStatus && matchesOrganization;
    });
  }, [organization, rows, search, status]);

  const activeCount = rows.filter((row) => String(row.status ?? "").toLowerCase() === "active").length;
  const inactiveCount = rows.filter((row) => String(row.status ?? "").toLowerCase() !== "active").length;
  const mappedStakeholderData = selectedStakeholder
    ? {
        "stakeholder.organization_name": selectedStakeholder.organization_name ?? "",
        "stakeholder.name": selectedStakeholder.name ?? "",
        "stakeholder.email": selectedStakeholder.email ?? "",
        "stakeholder.contact_number": selectedStakeholder.contact_number ?? "",
        "stakeholder.status": selectedStakeholder.status ?? "active",
        "contact.full_name": selectedStakeholder.contact?.full_name ?? "",
        "contact.email": selectedStakeholder.contact?.email ?? "",
        "contact.contact_number": selectedStakeholder.contact?.contact_number ?? "",
        "contact.position": selectedStakeholder.contact?.position ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stakeholders" />

      <div className="space-y-7 bg-white p-4 text-slate-950 md:p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-semibold tracking-normal">Stakeholders</h1>
            <p className="mt-2 text-base text-slate-500">
              Manage and collaborate with organizations and key stakeholders in the system.
            </p>
          </div>

          <CustomModelForm
            addButton={{
              ...StakeholderModelFormConfig.addButton,
              icon: Plus,
              className: "inline-flex h-12 items-center gap-2 rounded-lg bg-red-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-red-700",
            }}
            title="Add Stakeholder"
            description={StakeholderModelFormConfig.description}
            fields={StakeholderModelFormConfig.fields}
            submitRoute={stakeholders.store}
          />
        </div>

        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          <StatCard label="Total Stakeholders" value={rows.length} note="All registered" icon={UsersRound} tone="bg-red-50 text-red-600" />
          <StatCard label="Active Stakeholders" value={activeCount} note="Currently active" icon={CheckCircle2} tone="bg-emerald-50 text-emerald-600" />
          <StatCard label="Inactive Stakeholders" value={inactiveCount} note="Not active" icon={Hourglass} tone="bg-orange-50 text-orange-600" />
          <StatCard label="Organizations" value={organizations.length} note="Represented" icon={Building2} tone="bg-violet-50 text-violet-600" />
        </div>

        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div className="grid items-end gap-5 lg:grid-cols-[1.6fr_.8fr_.9fr_auto]">
            <div className="relative">
              <Search className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" />
              <input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                className="h-12 w-full rounded-lg border border-slate-200 bg-white px-4 pr-12 text-sm shadow-sm outline-none transition placeholder:text-slate-500 focus:border-orange-300 focus:ring-2 focus:ring-orange-200"
                placeholder="Search stakeholders by name, organization or email..."
              />
            </div>

            <label className="grid gap-1 text-sm">
              <span className="text-slate-500">Status</span>
              <select
                value={status}
                onChange={(event) => setStatus(event.target.value)}
                className="h-12 rounded-lg border border-slate-200 bg-white px-4 text-sm shadow-sm outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-200"
              >
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </label>

            <label className="grid gap-1 text-sm">
              <span className="text-slate-500">Organization</span>
              <select
                value={organization}
                onChange={(event) => setOrganization(event.target.value)}
                className="h-12 rounded-lg border border-slate-200 bg-white px-4 text-sm shadow-sm outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-200"
              >
                <option value="all">All Organizations</option>
                {organizations.map((item) => (
                  <option key={item} value={item}>{item}</option>
                ))}
              </select>
            </label>

            <button type="button" className="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-orange-500 px-7 text-sm font-semibold text-orange-600 hover:bg-orange-50">
              <Filter className="h-4 w-4" />
              Filters
            </button>
          </div>
        </section>

        <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead className="bg-gradient-to-r from-red-600 to-orange-600 text-white">
                <tr>
                  {["Organization", "Stakeholder Name", "Email", "Contact Number", "Status", "Actions"].map((heading) => (
                    <th key={heading} className={`px-5 py-4 font-semibold ${heading === "Actions" ? "text-center" : "text-left"}`}>
                      <span className={`inline-flex items-center gap-1 ${heading === "Actions" ? "justify-center" : ""}`}>
                        {heading}
                        {heading !== "Actions" ? <ChevronsUpDown className="h-3.5 w-3.5 text-white/80" /> : null}
                      </span>
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filteredRows.length ? filteredRows.map((row, index) => (
                  <tr key={row.id} className="border-t border-slate-200">
                    <td className="px-5 py-6">
                      <div className="flex items-center gap-4">
                        <span className={`grid h-12 w-12 shrink-0 place-items-center rounded-xl text-lg font-semibold ${avatarTones[index % avatarTones.length]}`}>
                          {initials(row.organization_name)}
                        </span>
                        <span className="max-w-36 font-medium leading-tight text-slate-950">{row.organization_name ?? "-"}</span>
                      </div>
                    </td>
                    <td className="px-5 py-6">
                      <div className="font-medium text-slate-950">{row.name ?? "-"}</div>
                      <span className="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Representative</span>
                    </td>
                    <td className="px-5 py-6">
                      <span className="inline-flex items-center gap-2 text-slate-700">
                        <Mail className="h-4 w-4 text-slate-600" />
                        {row.email ?? "-"}
                      </span>
                    </td>
                    <td className="px-5 py-6">
                      <span className="inline-flex items-center gap-2 text-slate-700">
                        <Phone className="h-4 w-4 text-slate-600" />
                        {row.contact_number ?? "-"}
                      </span>
                    </td>
                    <td className="px-5 py-6">
                      <span className={`inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-semibold capitalize ${String(row.status ?? "").toLowerCase() === "active" ? "border-emerald-200 bg-emerald-50 text-emerald-700" : "border-slate-200 bg-slate-100 text-slate-600"}`}>
                        <span className="h-2 w-2 rounded-full bg-current" />
                        {row.status ?? "inactive"}
                      </span>
                    </td>
                    <td className="px-5 py-6">
                      <div className="flex justify-center gap-3">
                        <Link href={stakeholders.show(row.id).url} className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-orange-500 text-orange-600 hover:bg-orange-50" title="View stakeholder">
                          <Eye className="h-4 w-4" />
                        </Link>
                        <button type="button" className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-orange-500 text-orange-600 hover:bg-orange-50" title="Edit stakeholder" onClick={() => {
                          setSelectedStakeholder(row);
                          setEditOpen(true);
                        }}>
                          <Pencil className="h-4 w-4" />
                        </button>
                        <button type="button" className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-red-500 text-red-600 hover:bg-red-50" title="Delete stakeholder" onClick={() => {
                          setStakeholderToDelete(row);
                          setDeleteOpen(true);
                        }}>
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                )) : (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-slate-500">No stakeholders match the current filters.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          <div className="flex flex-wrap items-center justify-between gap-3 px-5 py-5 text-sm text-slate-500">
            <span>Showing 1 to {filteredRows.length} of {rows.length} stakeholders</span>
            <div className="flex items-center gap-2">
              <button type="button" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">{"<"}</button>
              <button type="button" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-100 bg-red-50 font-semibold text-red-600">1</button>
              <button type="button" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">{">"}</button>
            </div>
          </div>
        </section>

        {selectedStakeholder ? (
          <CustomModelForm
            hideTrigger
            open={editOpen}
            onOpenChange={setEditOpen}
            title="Edit Stakeholder"
            fields={StakeholderModelFormConfig.fields}
            mode="edit"
            initialData={mappedStakeholderData}
            submitRoute={stakeholders.update}
            routeParams={selectedStakeholder.id}
          />
        ) : null}

        {stakeholderToDelete ? (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Stakeholder"
            submitRoute={stakeholders.destroy}
            routeParams={stakeholderToDelete.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
