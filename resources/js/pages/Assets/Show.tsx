import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useEffect, useMemo, useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { DomainNav } from "@/components/domain-nav";
import { assetNavItems } from "@/config/domain-nav/assets";
import { AssetModelFormConfig } from "@/config/forms/asset-model-form";
import AppLayout from "@/layouts/app-layout";
import assets from "@/routes/assets";
import { type BreadcrumbItem, type SharedData } from "@/types";

type AssetDetail = {
  id: number;
  asset_category_id: number | null;
  asset_code: string | null;
  category_name: string | null;
  name: string;
  type: string;
  model_name: string | null;
  serial_state: string;
  serial_number: string | null;
  status: string;
  assigned_to: string | null;
  current_assignment: {
    id: number;
    department_id: number | null;
    department_name: string | null;
    staff_member_id: number | null;
    staff_member_name: string | null;
    project_id: number | null;
    project_name: string | null;
    assigned_at: string | null;
    notes: string | null;
  } | null;
  assignment_history?: Array<{
    id: number;
    department_name: string | null;
    staff_member_name: string | null;
    project_name: string | null;
    assigned_at: string | null;
    returned_at: string | null;
    notes: string | null;
  }>;
  active_maintenance_record: {
    id: number;
    support_ticket_id: number | null;
    support_ticket_title: string | null;
    issue_summary: string;
    maintenance_notes: string | null;
    started_at: string | null;
  } | null;
  maintenance_history?: Array<{
    id: number;
    support_ticket_id: number | null;
    support_ticket_title: string | null;
    issue_summary: string;
    maintenance_notes: string | null;
    status: string;
    started_by_name: string | null;
    completed_by_name: string | null;
    started_at: string | null;
    completed_at: string | null;
  }>;
  decommission_record: {
    id: number;
    reason: string;
    notes: string | null;
    decommissioned_by_name: string | null;
    decommissioned_at: string | null;
  } | null;
  support_tickets?: Array<{
    id: number;
    title: string;
    status: string;
    priority: string;
    requester_name: string | null;
    assignee_name: string | null;
    created_at: string | null;
  }>;
};

const statusTone: Record<string, string> = {
  assigned: "bg-blue-50 text-blue-700 border-blue-200",
  unassigned: "bg-slate-50 text-slate-700 border-slate-200",
  maintenance: "bg-amber-50 text-amber-700 border-amber-200",
  retired: "bg-red-50 text-red-700 border-red-200",
};

export default function AssetShow({
  assetId,
  asset,
  categories,
  departments,
  staffMembers,
  projects,
  supportTickets,
}: {
  assetId: number;
  asset: AssetDetail;
  categories: { id: number; name: string }[];
  departments: { id: number; name: string }[];
  staffMembers: { id: number; name: string; department_id: number | null }[];
  projects: { id: number; name: string; project_manager_id: number | null }[];
  supportTickets: Array<{ id: number; title: string; asset_id: number | null; asset_code: string | null; asset_name: string | null }>;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Assets", href: "/assets" },
    { title: "List", href: "/assets/list" },
    { title: asset.asset_code ?? asset.name, href: `/assets/${assetId}` },
  ];
  const assetBaseUrl = `/assets/${assetId}`;
  const serialStateLabel = (asset.serial_state ?? "unknown").replaceAll("_", " ");
  const statusLabel = (asset.status ?? "unassigned").replaceAll("_", " ");
  const statusClass = statusTone[asset.status ?? "unassigned"] ?? statusTone.unassigned;

  const assignForm = useForm({
    assignment_mode: "department_staff",
    department_id: asset.current_assignment?.department_id ? String(asset.current_assignment.department_id) : "",
    staff_member_id: asset.current_assignment?.staff_member_id ? String(asset.current_assignment.staff_member_id) : "",
    project_id: asset.current_assignment?.project_id ? String(asset.current_assignment.project_id) : "",
    notes: "",
  });
  const returnForm = useForm({ notes: "" });
  const maintenanceForm = useForm({
    issue_summary: "",
    maintenance_notes: "",
    support_ticket_id: "",
  });
  const completeMaintenanceForm = useForm({ completion_notes: "" });
  const decommissionForm = useForm({ reason: "", notes: "" });
  const faultForm = useForm({
    title: "",
    description: "",
    priority: "medium",
    project_id: "",
  });
  const filteredStaffMembers = useMemo(() => {
    if (assignForm.data.assignment_mode !== "department_staff") {
      return staffMembers;
    }

    if (!assignForm.data.department_id) {
      return [];
    }

    return staffMembers.filter(
      (staff) => String(staff.department_id ?? "") === assignForm.data.department_id,
    );
  }, [assignForm.data.assignment_mode, assignForm.data.department_id, staffMembers]);

  useEffect(() => {
    if (assignForm.data.assignment_mode !== "department_staff") {
      return;
    }

    if (!assignForm.data.department_id) {
      if (assignForm.data.staff_member_id) {
        assignForm.setData("staff_member_id", "");
      }
      return;
    }

    const selectedStaffStillValid = filteredStaffMembers.some(
      (staff) => String(staff.id) === assignForm.data.staff_member_id,
    );

    if (assignForm.data.staff_member_id && !selectedStaffStillValid) {
      assignForm.setData("staff_member_id", "");
    }
  }, [
    assignForm,
    assignForm.data.assignment_mode,
    assignForm.data.department_id,
    assignForm.data.staff_member_id,
    filteredStaffMembers,
  ]);

  const mappedAssetData = {
    name: asset.name ?? "",
    asset_category_id: asset.asset_category_id ? String(asset.asset_category_id) : "",
    type: asset.type ?? "",
    model_name: asset.model_name ?? "",
    serial_state: asset.serial_state ?? "recorded",
    serial_number: asset.serial_number ?? "",
    status: asset.status ?? "unassigned",
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
          <Head title={`Asset ${asset.asset_code ?? asset.name}`} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="space-y-1">
            <div className="text-sm text-muted-foreground">
              <Link href="/assets/list" className="hover:underline">
                Back to asset list
              </Link>
            </div>
            <h1 className="text-2xl font-semibold">
              {asset.asset_code ?? "No Code"} | {asset.name}
            </h1>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <DomainNav items={assetNavItems} />
            <button
              type="button"
              onClick={() => setEditOpen(true)}
              className="rounded-md border border-orange-500 px-4 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Edit Asset
            </button>
            <button
              type="button"
              onClick={() => setDeleteOpen(true)}
              className="rounded-md border border-red-600 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white"
            >
              Delete Asset
            </button>
          </div>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}
        {flash.warning ? (
          <div className="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800">
            {String(flash.warning)}
          </div>
        ) : null}
        {flash.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        <div className="grid gap-4 lg:grid-cols-3">
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Asset Profile</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Category</dt>
                <dd>{asset.category_name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Type</dt>
                <dd>{asset.type}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Model</dt>
                <dd>{asset.model_name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Serial State</dt>
                <dd className="capitalize">{serialStateLabel}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Serial Number</dt>
                <dd>{asset.serial_number ?? "-"}</dd>
              </div>
            </dl>
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Current State</h2>
            <div className="mt-3 flex items-center gap-3">
              <span className={`rounded-full border px-3 py-1 text-xs font-semibold ${statusClass}`}>
                {statusLabel}
              </span>
            </div>
            <div className="mt-3 text-sm text-muted-foreground">
              {asset.assigned_to ?? "Not currently assigned."}
            </div>
            {asset.active_maintenance_record ? (
              <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                <div className="font-medium">Active maintenance</div>
                <div className="mt-1">{asset.active_maintenance_record.issue_summary}</div>
                {asset.active_maintenance_record.support_ticket_title ? (
                  <div className="mt-1 text-xs">
                    Linked ticket: {asset.active_maintenance_record.support_ticket_title}
                  </div>
                ) : null}
              </div>
            ) : null}
            {asset.decommission_record ? (
              <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                <div className="font-medium">Decommissioned</div>
                <div className="mt-1">{asset.decommission_record.reason}</div>
              </div>
            ) : null}
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Current Assignment</h2>
            {asset.current_assignment ? (
              <dl className="mt-3 space-y-2 text-sm">
                <div className="flex justify-between gap-3">
                  <dt className="text-muted-foreground">Department</dt>
                  <dd>{asset.current_assignment.department_name ?? "-"}</dd>
                </div>
                <div className="flex justify-between gap-3">
                  <dt className="text-muted-foreground">Staff</dt>
                  <dd>{asset.current_assignment.staff_member_name ?? "-"}</dd>
                </div>
                <div className="flex justify-between gap-3">
                  <dt className="text-muted-foreground">Project</dt>
                  <dd>{asset.current_assignment.project_name ?? "-"}</dd>
                </div>
                <div className="flex justify-between gap-3">
                  <dt className="text-muted-foreground">Assigned At</dt>
                  <dd>{asset.current_assignment.assigned_at ?? "-"}</dd>
                </div>
                <div className="space-y-1">
                  <dt className="text-muted-foreground">Notes</dt>
                  <dd>{asset.current_assignment.notes ?? "-"}</dd>
                </div>
              </dl>
            ) : (
              <p className="mt-3 text-sm text-muted-foreground">No active assignment.</p>
            )}
          </section>
        </div>

        <section className="grid gap-4 xl:grid-cols-2">
          {!asset.decommission_record ? (
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Assign Asset</h2>
              <p className="mt-1 text-sm text-muted-foreground">
                Use this form to assign the asset to a department, staff member, or project.
              </p>
              <form
                className="mt-4 grid gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  assignForm.post(`${assetBaseUrl}/assign`, { preserveScroll: true });
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">Assignment Mode</label>
                  <select
                    value={assignForm.data.assignment_mode}
                    onChange={(e) => assignForm.setData("assignment_mode", e.currentTarget.value as "department_staff" | "project")}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  >
                    <option value="department_staff">Department/Staff</option>
                    <option value="project">Project (Exclusive)</option>
                  </select>
                </div>

                {assignForm.data.assignment_mode === "project" ? (
                  <div>
                    <label className="mb-1 block text-sm font-medium">Project</label>
                    <select
                      value={assignForm.data.project_id}
                      onChange={(e) => assignForm.setData("project_id", e.currentTarget.value)}
                      className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                      <option value="">Select project</option>
                      {projects.map((project) => (
                        <option key={project.id} value={project.id}>
                          {project.name}
                        </option>
                      ))}
                    </select>
                  </div>
                ) : (
                  <>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Department</label>
                      <select
                        value={assignForm.data.department_id}
                        onChange={(e) => assignForm.setData("department_id", e.currentTarget.value)}
                        className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                      >
                        <option value="">Select department</option>
                        {departments.map((department) => (
                          <option key={department.id} value={department.id}>
                            {department.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="mb-1 block text-sm font-medium">Staff</label>
                      <select
                        value={assignForm.data.staff_member_id}
                        onChange={(e) => assignForm.setData("staff_member_id", e.currentTarget.value)}
                        disabled={!assignForm.data.department_id}
                        className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                      >
                        <option value="">
                          {assignForm.data.department_id ? "Select staff" : "Select department first"}
                        </option>
                        {filteredStaffMembers.map((staff) => (
                          <option key={staff.id} value={staff.id}>
                            {staff.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </>
                )}

                <div>
                  <label className="mb-1 block text-sm font-medium">Notes</label>
                  <textarea
                    rows={3}
                    value={assignForm.data.notes}
                    onChange={(e) => assignForm.setData("notes", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <button type="submit" className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">
                  Assign Asset
                </button>
              </form>
            </div>
          ) : null}

          {asset.current_assignment && asset.status === "assigned" ? (
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Return Asset</h2>
              <p className="mt-1 text-sm text-muted-foreground">
                Close the active assignment and move the asset back into the available pool.
              </p>
              <form
                className="mt-4 grid gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  returnForm.post(`${assetBaseUrl}/return`, { preserveScroll: true });
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">Return Notes</label>
                  <textarea
                    rows={3}
                    value={returnForm.data.notes}
                    onChange={(e) => returnForm.setData("notes", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">
                  Return Asset
                </button>
              </form>
            </div>
          ) : null}

          {!asset.active_maintenance_record && !asset.decommission_record ? (
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Start Maintenance</h2>
              <form
                className="mt-4 grid gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  maintenanceForm.post(`${assetBaseUrl}/maintenance/start`, { preserveScroll: true });
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">Issue Summary</label>
                  <input
                    value={maintenanceForm.data.issue_summary}
                    onChange={(e) => maintenanceForm.setData("issue_summary", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Linked Support Ticket</label>
                  <select
                    value={maintenanceForm.data.support_ticket_id}
                    onChange={(e) => maintenanceForm.setData("support_ticket_id", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  >
                    <option value="">No ticket</option>
                    {supportTickets
                      .filter((ticket) => ticket.asset_id === asset.id)
                      .map((ticket) => (
                        <option key={ticket.id} value={ticket.id}>
                          #{ticket.id} | {ticket.title}
                        </option>
                      ))}
                  </select>
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Maintenance Notes</label>
                  <textarea
                    rows={3}
                    value={maintenanceForm.data.maintenance_notes}
                    onChange={(e) => maintenanceForm.setData("maintenance_notes", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <button type="submit" className="rounded-md bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">
                  Start Maintenance
                </button>
              </form>
            </div>
          ) : null}

          {asset.active_maintenance_record ? (
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Complete Maintenance</h2>
              <p className="mt-1 text-sm text-muted-foreground">
                {asset.active_maintenance_record.issue_summary}
              </p>
              <form
                className="mt-4 grid gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  completeMaintenanceForm.post(`${assetBaseUrl}/maintenance/complete`, { preserveScroll: true });
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">Completion Notes</label>
                  <textarea
                    rows={3}
                    value={completeMaintenanceForm.data.completion_notes}
                    onChange={(e) => completeMaintenanceForm.setData("completion_notes", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <button type="submit" className="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">
                  Complete Maintenance
                </button>
              </form>
            </div>
          ) : null}

          {!asset.decommission_record ? (
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Report Fault</h2>
              <form
                className="mt-4 grid gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  faultForm.post(`${assetBaseUrl}/report-fault`, { preserveScroll: true });
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">Title</label>
                  <input
                    value={faultForm.data.title}
                    onChange={(e) => faultForm.setData("title", e.currentTarget.value)}
                    placeholder="Optional custom title"
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Description</label>
                  <textarea
                    rows={4}
                    value={faultForm.data.description}
                    onChange={(e) => faultForm.setData("description", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <label className="mb-1 block text-sm font-medium">Priority</label>
                    <select
                      value={faultForm.data.priority}
                      onChange={(e) => faultForm.setData("priority", e.currentTarget.value as "low" | "medium" | "high" | "urgent")}
                      className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>
                  <div>
                    <label className="mb-1 block text-sm font-medium">Project</label>
                    <select
                      value={faultForm.data.project_id}
                      onChange={(e) => faultForm.setData("project_id", e.currentTarget.value)}
                      className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                      <option value="">No project</option>
                      {projects.map((project) => (
                        <option key={project.id} value={project.id}>
                          {project.name}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
                <button type="submit" className="rounded-md bg-orange-600 px-4 py-2 text-sm text-white hover:bg-orange-700">
                  Report Fault
                </button>
              </form>
            </div>
          ) : null}

          {!asset.decommission_record && !asset.active_maintenance_record ? (
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="text-base font-semibold">Decommission Asset</h2>
              <form
                className="mt-4 grid gap-3"
                onSubmit={(e) => {
                  e.preventDefault();
                  decommissionForm.post(`${assetBaseUrl}/decommission`, { preserveScroll: true });
                }}
              >
                <div>
                  <label className="mb-1 block text-sm font-medium">Reason</label>
                  <input
                    value={decommissionForm.data.reason}
                    onChange={(e) => decommissionForm.setData("reason", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Notes</label>
                  <textarea
                    rows={3}
                    value={decommissionForm.data.notes}
                    onChange={(e) => decommissionForm.setData("notes", e.currentTarget.value)}
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                </div>
                <button type="submit" className="rounded-md bg-red-700 px-4 py-2 text-sm text-white hover:bg-red-800">
                  Decommission Asset
                </button>
              </form>
            </div>
          ) : null}
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Support Tickets</h2>
          <div className="mt-3 space-y-3">
            {(asset.support_tickets ?? []).length === 0 ? (
              <p className="text-sm text-muted-foreground">No support tickets linked to this asset.</p>
            ) : (
              asset.support_tickets?.map((ticket) => (
                <div key={ticket.id} className="rounded-lg border p-3 text-sm">
                  <div className="font-medium">
                    #{ticket.id} | {ticket.title}
                  </div>
                  <div className="mt-1 text-muted-foreground">
                    {ticket.status} | {ticket.priority} | Requested by {ticket.requester_name ?? "-"} | Assigned to {ticket.assignee_name ?? "Technical queue"}
                  </div>
                </div>
              ))
            )}
          </div>
        </section>

        <section className="grid gap-4 xl:grid-cols-2">
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Maintenance History</h2>
            <div className="mt-3 space-y-3">
              {(asset.maintenance_history ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground">No maintenance history.</p>
              ) : (
                asset.maintenance_history?.map((record) => (
                  <div key={record.id} className="rounded-lg border p-3 text-sm">
                    <div className="font-medium">{record.issue_summary}</div>
                    <div className="mt-1 text-muted-foreground">
                      {record.status} | Started {record.started_at ?? "-"} by {record.started_by_name ?? "-"}
                    </div>
                    {record.completed_at ? (
                      <div className="mt-1 text-muted-foreground">
                        Completed {record.completed_at} by {record.completed_by_name ?? "-"}
                      </div>
                    ) : null}
                    {record.maintenance_notes ? <div className="mt-2">{record.maintenance_notes}</div> : null}
                  </div>
                ))
              )}
            </div>
          </div>

          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Assignment History</h2>
            <div className="mt-3 space-y-3">
              {(asset.assignment_history ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground">No assignment history.</p>
              ) : (
                asset.assignment_history?.map((item) => (
                  <div key={item.id} className="rounded-lg border p-3 text-sm">
                    <div className="font-medium">
                      {item.project_name ? `Project: ${item.project_name}` : item.staff_member_name ?? item.department_name ?? "Unknown target"}
                    </div>
                    <div className="mt-1 text-muted-foreground">
                      Assigned {item.assigned_at ?? "-"} | Returned {item.returned_at ?? "Still active"}
                    </div>
                    {item.notes ? <div className="mt-2">{item.notes}</div> : null}
                  </div>
                ))
              )}
            </div>
          </div>
        </section>

        {asset.decommission_record ? (
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Decommission Record</h2>
            <div className="mt-3 grid gap-2 text-sm md:grid-cols-2">
              <div>
                <div className="text-muted-foreground">Reason</div>
                <div>{asset.decommission_record.reason}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Decommissioned At</div>
                <div>{asset.decommission_record.decommissioned_at ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Decommissioned By</div>
                <div>{asset.decommission_record.decommissioned_by_name ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Notes</div>
                <div>{asset.decommission_record.notes ?? "-"}</div>
              </div>
            </div>
          </section>
        ) : null}

        <CustomModelForm
          hideTrigger
          open={editOpen}
          onOpenChange={setEditOpen}
          title="Edit Asset"
          description="Update the asset profile."
          fields={AssetModelFormConfig.fields}
          mode="edit"
          initialData={mappedAssetData}
          submitRoute={assets.update}
          routeParams={assetId}
          options={{ categories }}
        />

        <ConfirmDeleteModal
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          title="Delete Asset"
          submitRoute={assets.destroy}
          routeParams={assetId}
        />
      </div>
    </AppLayout>
  );
}
