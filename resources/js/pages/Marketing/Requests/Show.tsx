import { Head, router, usePage } from "@inertiajs/react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { marketingNavItems } from "@/config/domain-nav/marketing";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type Version = {
  id: number;
  version_number: number;
  uploaded_by_name: string | null;
  change_notes: string | null;
  asset_file_name: string | null;
  external_reference: string | null;
  approval_status: string;
  approved_by_name: string | null;
  approved_at: string | null;
  created_at: string | null;
};

type Asset = {
  id: number;
  asset_type: string;
  asset_file_name: string | null;
  reusable: boolean;
  archived_at: string | null;
  version_number: number | null;
  can: { publish: boolean; archive: boolean; publish_to_vault: boolean };
  publications: Array<{
    id: number;
    publication_channel: string;
    published_by_name: string | null;
    published_at: string | null;
    external_reference: string | null;
    publication_notes: string | null;
    metrics?: { data?: Array<{ metric_date: string | null; reach: number | null; impressions: number | null; engagements: number | null; clicks: number | null; sessions: number | null; conversions: number | null; followers: number | null }> };
  }> | { data?: Array<{
    id: number;
    publication_channel: string;
    published_by_name: string | null;
    published_at: string | null;
    external_reference: string | null;
    publication_notes: string | null;
    metrics?: { data?: Array<{ metric_date: string | null; reach: number | null; impressions: number | null; engagements: number | null; clicks: number | null; sessions: number | null; conversions: number | null; followers: number | null }> };
  }> };
};

type Deliverable = {
  id: number;
  title: string;
  deliverable_type: string;
  assigned_unit: string;
  status: string;
  due_date: string | null;
  review_notes: string | null;
  approved_at: string | null;
  published_at: string | null;
  assignee_name: string | null;
  versions: Version[] | { data?: Version[] };
  assets: Asset[] | { data?: Asset[] };
  can: { upload_version: boolean; approve: boolean };
};

type RequestRecord = {
  id: number;
  title: string;
  objective: string | null;
  description: string | null;
  target_audience: string | null;
  campaign_goal: string | null;
  priority: string;
  due_date: string | null;
  status: string;
  requester_name: string | null;
  approver_name: string | null;
  project_name: string | null;
  program_name: string | null;
  event_name: string | null;
  owner_department_name: string | null;
  source_marketing_job_id: number | null;
  work_packages: Array<{ id: number; assigned_unit: string; workload_status: string; operational_owner_name: string | null; planned_start_date: string | null; planned_end_date: string | null; actual_end_date: string | null }>;
  deliverables: Deliverable[] | { data?: Deliverable[] };
  activities: Array<{ id: number; action: string; summary: string; actor_name: string | null; created_at: string | null }> | { data?: Array<{ id: number; action: string; summary: string; actor_name: string | null; created_at: string | null }> };
  comments: Array<{ id: number; user_name: string | null; message: string; created_at: string | null }> | { data?: Array<{ id: number; user_name: string | null; message: string; created_at: string | null }> };
  documents: Array<{ id: number; title: string; document_kind: string; notes: string | null; file_name: string; uploaded_by_name: string | null; created_at: string | null }> | { data?: Array<{ id: number; title: string; document_kind: string; notes: string | null; file_name: string; uploaded_by_name: string | null; created_at: string | null }> };
  can: { update: boolean; comment: boolean; upload_document: boolean };
};

function PublishAssetToVaultForm({
  asset,
  deliverableTitle,
  departments,
  users,
  documentTypes,
  slotOptions,
}: {
  asset: Asset;
  deliverableTitle: string;
  departments: Array<{ id: number; name: string }>;
  users: Array<{ id: number; name: string; email: string }>;
  documentTypes: Array<{ value: string; label: string }>;
  slotOptions: Array<{ value: string; label: string; document_type: string }>;
}) {
  const [selectedVaultType, setSelectedVaultType] = useState(
    documentTypes.some((documentType) => documentType.value === asset.asset_type) ? asset.asset_type : "other"
  );
  const filteredSlots = useMemo(
    () => slotOptions.filter((slot) => slot.document_type === selectedVaultType),
    [slotOptions, selectedVaultType],
  );

  return (
    <form
      className="mt-3 grid gap-2 rounded-md border border-dashed p-3"
      onSubmit={(event) => {
        event.preventDefault();
        router.post(`/marketing/assets/${asset.id}/publish-to-vault`, new FormData(event.currentTarget), {
          preserveScroll: true,
        });
      }}
    >
      <div className="text-sm font-medium">Publish To Organization Vault</div>
      <input name="title" defaultValue={deliverableTitle} className="rounded-md border bg-background px-3 py-2 text-sm" />
      <select
        name="document_type"
        value={selectedVaultType}
        onChange={(event) => setSelectedVaultType(event.target.value)}
        className="rounded-md border bg-background px-3 py-2 text-sm"
      >
        {documentTypes.map((documentType) => (
          <option key={documentType.value} value={documentType.value}>{documentType.label}</option>
        ))}
      </select>
      <select name="audience_scope" defaultValue="all_staff" className="rounded-md border bg-background px-3 py-2 text-sm">
        <option value="all_staff">All staff</option>
        <option value="department">Department</option>
        <option value="selected_users">Selected users</option>
      </select>
      <select name="department_id" defaultValue="" className="rounded-md border bg-background px-3 py-2 text-sm">
        <option value="">No department target</option>
        {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
      </select>
      <select name="slot_key" defaultValue="" className="rounded-md border bg-background px-3 py-2 text-sm">
        <option value="">No replacement slot</option>
        {filteredSlots.map((slot) => (
          <option key={slot.value} value={slot.value}>{slot.label}</option>
        ))}
      </select>
      <label className="flex items-center gap-2 text-xs text-muted-foreground">
        <input type="checkbox" name="replace_existing" value="1" />
        Replace current organization file in this slot
      </label>
      <label className="flex items-center gap-2 text-xs text-muted-foreground">
        <input type="checkbox" name="is_active" value="1" defaultChecked />
        Active immediately
      </label>
      <label className="grid gap-1 text-xs font-medium text-muted-foreground">
        Available from
        <input name="effective_from" type="date" className="rounded-md border bg-background px-3 py-2 text-sm font-normal text-slate-900" />
      </label>
      <label className="grid gap-1 text-xs font-medium text-muted-foreground">
        Retire after
        <input name="effective_until" type="date" className="rounded-md border bg-background px-3 py-2 text-sm font-normal text-slate-900" />
      </label>
      <textarea name="description" rows={2} placeholder="How staff should use this approved asset." className="rounded-md border bg-background px-3 py-2 text-sm" />
      <div className="grid gap-2 md:grid-cols-2">
        {users.map((user) => (
          <label key={user.id} className="flex items-center gap-2 text-xs text-muted-foreground">
            <input type="checkbox" name="selected_user_ids[]" value={user.id} />
            <span>{user.name}</span>
          </label>
        ))}
      </div>
      <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
        Publish Approved Asset
      </button>
    </form>
  );
}

export default function MarketingRequestShow({
  requestRecord,
  events,
  projects,
  programs,
  departments,
  approvers,
  assignees,
  units,
  users,
  documentTypes,
  slotOptions,
  canManageVault,
}: {
  requestRecord: RequestRecord;
  events: Array<{ id: number; title: string }>;
  projects: Array<{ id: number; name: string }>;
  programs: Array<{ id: number; title: string }>;
  departments: Array<{ id: number; name: string }>;
  approvers: Array<{ id: number; name: string; email: string }>;
  assignees: Array<{ id: number; name: string; email: string }>;
  units: string[];
  users: Array<{ id: number; name: string; email: string }>;
  documentTypes: Array<{ value: string; label: string }>;
  slotOptions: Array<{ value: string; label: string; document_type: string }>;
  canManageVault: boolean;
}) {
  const flash = (usePage<SharedData>().props.flash ?? {}) as Record<string, unknown>;
  const deliverables = Array.isArray(requestRecord.deliverables) ? requestRecord.deliverables : (requestRecord.deliverables.data ?? []);
  const activities = Array.isArray(requestRecord.activities) ? requestRecord.activities : (requestRecord.activities.data ?? []);
  const comments = Array.isArray(requestRecord.comments) ? requestRecord.comments : (requestRecord.comments.data ?? []);
  const documents = Array.isArray(requestRecord.documents) ? requestRecord.documents : (requestRecord.documents.data ?? []);
  const workPackage = requestRecord.work_packages[0];
  const selectedApproverId = String(approvers.find((approver) => approver.name === requestRecord.approver_name)?.id ?? "");
  const selectedDepartmentId = String(departments.find((department) => department.name === requestRecord.owner_department_name)?.id ?? "");
  const selectedProjectId = String(projects.find((project) => project.name === requestRecord.project_name)?.id ?? "");
  const selectedProgramId = String(programs.find((program) => program.title === requestRecord.program_name)?.id ?? "");
  const selectedEventId = String(events.find((eventItem) => eventItem.title === requestRecord.event_name)?.id ?? "");
  const selectedOperationalOwnerId = String(assignees.find((assignee) => assignee.name === workPackage?.operational_owner_name)?.id ?? "");
  const deliverableVersionCount = deliverables.reduce((total, deliverable) => {
    const versions = Array.isArray(deliverable.versions) ? deliverable.versions : (deliverable.versions.data ?? []);

    return total + versions.length;
  }, 0);
  const approvedAssetCount = deliverables.reduce((total, deliverable) => {
    const assets = Array.isArray(deliverable.assets) ? deliverable.assets : (deliverable.assets.data ?? []);

    return total + assets.length;
  }, 0);
  const pendingApprovalCount = deliverables.filter((deliverable) => !deliverable.approved_at && deliverable.can.approve).length;

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Marketing", href: "/marketing" },
    { title: "Requests", href: "/marketing/requests" },
    { title: requestRecord.title, href: `/marketing/requests/${requestRecord.id}` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={requestRecord.title} />

      <div className="space-y-6 bg-slate-50 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{requestRecord.title}</h1>
            <p className="text-sm text-muted-foreground">
              Content request workspace. Each workflow below is separate, but remains tied to this request and its work package.
            </p>
          </div>
          <DomainNav items={marketingNavItems} />
        </div>

        {flash.success ? <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">{String(flash.success)}</div> : null}

        <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Current job/task context</div>
              <div className="mt-1 flex flex-wrap items-center gap-2">
                <span className="rounded-full bg-slate-900 px-3 py-1 text-xs font-medium capitalize text-white">{requestRecord.status.replaceAll("_", " ")}</span>
                <span className="rounded-full border border-slate-200 px-3 py-1 text-xs capitalize text-slate-700">{requestRecord.priority} priority</span>
                <span className="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-700">Due {requestRecord.due_date ?? "not set"}</span>
                {requestRecord.source_marketing_job_id ? <span className="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-700">Job #{requestRecord.source_marketing_job_id}</span> : null}
              </div>
            </div>
            <div className="grid gap-1 text-right text-xs text-slate-500">
              <span>Requester: {requestRecord.requester_name ?? "-"}</span>
              <span>Approver: {requestRecord.approver_name ?? "-"}</span>
            </div>
          </div>
          <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div className="rounded-xl bg-slate-50 p-3">
              <div className="text-xs text-slate-500">Owner department</div>
              <div className="mt-1 text-sm font-medium">{requestRecord.owner_department_name ?? "-"}</div>
            </div>
            <div className="rounded-xl bg-slate-50 p-3">
              <div className="text-xs text-slate-500">Project</div>
              <div className="mt-1 text-sm font-medium">{requestRecord.project_name ?? "-"}</div>
            </div>
            <div className="rounded-xl bg-slate-50 p-3">
              <div className="text-xs text-slate-500">Program</div>
              <div className="mt-1 text-sm font-medium">{requestRecord.program_name ?? "-"}</div>
            </div>
            <div className="rounded-xl bg-slate-50 p-3">
              <div className="text-xs text-slate-500">Event</div>
              <div className="mt-1 text-sm font-medium">{requestRecord.event_name ?? "-"}</div>
            </div>
          </div>
        </section>

        <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-xl border-l-4 border-l-sky-500 bg-white p-4 shadow-sm">
            <div className="text-xs font-semibold uppercase tracking-wide text-sky-700">Content request</div>
            <div className="mt-2 text-2xl font-semibold">{requestRecord.work_packages.length}</div>
            <div className="text-xs text-muted-foreground">work package{requestRecord.work_packages.length === 1 ? "" : "s"} scoped to this task</div>
          </div>
          <div className="rounded-xl border-l-4 border-l-indigo-500 bg-white p-4 shadow-sm">
            <div className="text-xs font-semibold uppercase tracking-wide text-indigo-700">Content uploads</div>
            <div className="mt-2 text-2xl font-semibold">{documents.length + deliverableVersionCount}</div>
            <div className="text-xs text-muted-foreground">brief files and deliverable versions</div>
          </div>
          <div className="rounded-xl border-l-4 border-l-amber-500 bg-white p-4 shadow-sm">
            <div className="text-xs font-semibold uppercase tracking-wide text-amber-700">Reviews</div>
            <div className="mt-2 text-2xl font-semibold">{comments.length}</div>
            <div className="text-xs text-muted-foreground">request comments and review notes</div>
          </div>
          <div className="rounded-xl border-l-4 border-l-emerald-500 bg-white p-4 shadow-sm">
            <div className="text-xs font-semibold uppercase tracking-wide text-emerald-700">Approvals</div>
            <div className="mt-2 text-2xl font-semibold">{approvedAssetCount}</div>
            <div className="text-xs text-muted-foreground">{pendingApprovalCount} deliverable{pendingApprovalCount === 1 ? "" : "s"} awaiting your approval</div>
          </div>
        </section>

        <section className="rounded-2xl border border-sky-200 bg-white shadow-sm">
          <div className="border-b border-sky-100 bg-sky-50 px-4 py-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-sky-700">Workflow 1</div>
            <h2 className="text-lg font-semibold text-slate-950">Content Request</h2>
            <p className="text-sm text-slate-600">The brief, audience, campaign goal, and assigned production work package.</p>
          </div>
          <div className="grid gap-4 p-4 xl:grid-cols-[1.4fr_1fr]">
            <div className="grid gap-3 md:grid-cols-2">
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Objective</div>
                <div className="mt-1 text-sm">{requestRecord.objective ?? "No objective captured."}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Status</div>
                <div className="mt-1 text-sm capitalize">{requestRecord.status.replaceAll("_", " ")}</div>
              </div>
              <div className="rounded-lg border p-3 md:col-span-2">
                <div className="text-xs text-muted-foreground">Description</div>
                <div className="mt-1 text-sm">{requestRecord.description ?? "No description captured."}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Target audience</div>
                <div className="mt-1 text-sm">{requestRecord.target_audience ?? "Not specified."}</div>
              </div>
              <div className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground">Campaign goal</div>
                <div className="mt-1 text-sm">{requestRecord.campaign_goal ?? "Not specified."}</div>
              </div>
            </div>
            <div className="space-y-3">
              <div className="text-sm font-semibold">Work package</div>
              {requestRecord.work_packages.length === 0 ? <div className="rounded-lg border p-3 text-sm text-muted-foreground">No work package assigned yet.</div> : requestRecord.work_packages.map((workPackage) => (
                <div key={workPackage.id} className="rounded-lg border p-3">
                  <div className="font-medium capitalize">{workPackage.assigned_unit.replaceAll("_", " ")}</div>
                  <div className="mt-1 text-xs text-muted-foreground">
                    Owner {workPackage.operational_owner_name ?? "-"} | {workPackage.workload_status.replaceAll("_", " ")}
                  </div>
                  <div className="mt-1 text-xs text-muted-foreground">
                    Planned {workPackage.planned_start_date ?? "-"} to {workPackage.planned_end_date ?? "-"}
                  </div>
                </div>
              ))}
            </div>
          </div>

        {requestRecord.can.update ? (
          <details className="border-t border-sky-100">
            <summary className="cursor-pointer px-4 py-3 text-sm font-medium text-sky-800">Edit request plan</summary>
            <form
              className="grid gap-3 p-4 pt-1 md:grid-cols-2 xl:grid-cols-4"
              onSubmit={(event) => {
                event.preventDefault();
                const formData = new FormData(event.currentTarget);
                router.put(`/marketing/requests/${requestRecord.id}`, {
                  title: formData.get("title"),
                  objective: formData.get("objective"),
                  description: formData.get("description"),
                  target_audience: formData.get("target_audience"),
                  campaign_goal: formData.get("campaign_goal"),
                  approver_user_id: formData.get("approver_user_id"),
                  project_id: formData.get("project_id"),
                  program_id: formData.get("program_id"),
                  event_id: formData.get("event_id"),
                  owner_department_id: formData.get("owner_department_id"),
                  priority: formData.get("priority"),
                  due_date: formData.get("due_date"),
                  status: formData.get("status"),
                  work_package: {
                    assigned_unit: formData.get("assigned_unit"),
                    operational_owner_user_id: formData.get("operational_owner_user_id"),
                    planned_start_date: formData.get("planned_start_date"),
                    planned_end_date: formData.get("planned_end_date"),
                  },
                }, { preserveScroll: true });
              }}
            >
              <input name="title" defaultValue={requestRecord.title} className="rounded-md border bg-background px-3 py-2 text-sm" />
              <input name="objective" defaultValue={requestRecord.objective ?? ""} className="rounded-md border bg-background px-3 py-2 text-sm" />
              <select name="approver_user_id" defaultValue={selectedApproverId} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No approver</option>
                {approvers.map((approver) => <option key={approver.id} value={approver.id}>{approver.name}</option>)}
              </select>
              <select name="owner_department_id" defaultValue={selectedDepartmentId} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">Owner department</option>
                {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
              </select>
              <select name="project_id" defaultValue={selectedProjectId} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No project</option>
                {projects.map((project) => <option key={project.id} value={project.id}>{project.name}</option>)}
              </select>
              <select name="program_id" defaultValue={selectedProgramId} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No program</option>
                {programs.map((program) => <option key={program.id} value={program.id}>{program.title}</option>)}
              </select>
              <select name="event_id" defaultValue={selectedEventId} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No event</option>
                {events.map((eventItem) => <option key={eventItem.id} value={eventItem.id}>{eventItem.title}</option>)}
              </select>
              <select name="priority" defaultValue={requestRecord.priority} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
              <input name="due_date" type="date" defaultValue={requestRecord.due_date ?? ""} className="rounded-md border bg-background px-3 py-2 text-sm" />
              <select name="status" defaultValue={requestRecord.status} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="planned">Planned</option>
                <option value="in_production">In production</option>
                <option value="in_review">In review</option>
                <option value="partially_approved">Partially approved</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <select name="assigned_unit" defaultValue={workPackage?.assigned_unit ?? units[0] ?? "graphics"} className="rounded-md border bg-background px-3 py-2 text-sm">
                {units.map((unit) => <option key={unit} value={unit}>{unit.replaceAll("_", " ")}</option>)}
              </select>
              <select name="operational_owner_user_id" defaultValue={selectedOperationalOwnerId} className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No operational owner</option>
                {assignees.map((assignee) => <option key={assignee.id} value={assignee.id}>{assignee.name}</option>)}
              </select>
              <input name="planned_start_date" type="date" defaultValue={workPackage?.planned_start_date ?? ""} className="rounded-md border bg-background px-3 py-2 text-sm" />
              <input name="planned_end_date" type="date" defaultValue={workPackage?.planned_end_date ?? ""} className="rounded-md border bg-background px-3 py-2 text-sm" />
              <textarea name="description" defaultValue={requestRecord.description ?? ""} rows={3} className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2" />
              <textarea name="target_audience" defaultValue={requestRecord.target_audience ?? ""} rows={3} className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2" />
              <textarea name="campaign_goal" defaultValue={requestRecord.campaign_goal ?? ""} rows={3} className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2 xl:col-span-4" />
              <div className="md:col-span-2 xl:col-span-4">
                <button type="submit" className="rounded-md bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-900">Save Request Plan</button>
              </div>
            </form>
          </details>
        ) : null}
        </section>

        <section className="rounded-2xl border border-indigo-200 bg-white shadow-sm">
          <div className="border-b border-indigo-100 bg-indigo-50 px-4 py-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-indigo-700">Workflow 2</div>
            <h2 className="text-lg font-semibold text-slate-950">Content Uploads</h2>
            <p className="text-sm text-slate-600">Upload request references and production versions here. These are working files for this task.</p>
          </div>
          <div className="grid gap-4 p-4 xl:grid-cols-[0.9fr_1.1fr]">
            <div className="rounded-xl border p-4">
              <h3 className="text-base font-semibold">Request files</h3>
              {requestRecord.can.upload_document ? (
                <form
                  className="mt-4 grid gap-3"
                  onSubmit={(event) => {
                    event.preventDefault();
                    const formData = new FormData(event.currentTarget);
                    router.post(`/marketing/requests/${requestRecord.id}/documents`, formData, {
                      forceFormData: true,
                      preserveScroll: true,
                    });
                    event.currentTarget.reset();
                  }}
                >
                  <input name="title" placeholder="Document title" className="rounded-md border bg-background px-3 py-2 text-sm" />
                  <select name="document_kind" defaultValue="supporting" className="rounded-md border bg-background px-3 py-2 text-sm">
                    <option value="supporting">Supporting</option>
                    <option value="brief">Brief</option>
                    <option value="brand_reference">Brand reference</option>
                    <option value="approval_reference">Approval reference</option>
                    <option value="publication_reference">Publication reference</option>
                  </select>
                  <textarea name="notes" rows={3} placeholder="What is this file for?" className="rounded-md border bg-background px-3 py-2 text-sm" />
                  <input name="file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
                  <button type="submit" className="rounded-md bg-indigo-700 px-4 py-2 text-sm text-white hover:bg-indigo-800">Upload Request File</button>
                </form>
              ) : null}
              <div className="mt-4 space-y-3">
                {documents.length === 0 ? <div className="text-sm text-muted-foreground">No request documents uploaded yet.</div> : documents.map((document) => (
                  <div key={document.id} className="rounded-md border p-3">
                    <div className="font-medium">{document.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {document.document_kind.replaceAll("_", " ")} | {document.uploaded_by_name ?? "-"} | {document.created_at ?? "-"}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">{document.file_name}</div>
                    {document.notes ? <div className="mt-2 text-sm text-muted-foreground">{document.notes}</div> : null}
                    <a href={`/marketing/requests/${requestRecord.id}/documents/${document.id}`} className="mt-2 inline-block text-sm text-blue-700 underline">
                      Download
                    </a>
                  </div>
                ))}
              </div>
            </div>
            <div className="space-y-4">
            {deliverables.map((deliverable) => {
              const versions = Array.isArray(deliverable.versions) ? deliverable.versions : (deliverable.versions.data ?? []);

              return (
                <div key={deliverable.id} className="rounded-xl border p-4">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-base font-semibold">{deliverable.title}</h3>
                        <span className="rounded-full border px-2 py-1 text-xs capitalize">{deliverable.status.replaceAll("_", " ")}</span>
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {deliverable.deliverable_type.replaceAll("_", " ")} | {deliverable.assigned_unit.replaceAll("_", " ")} | {deliverable.assignee_name ?? "Unassigned"}
                        {deliverable.due_date ? ` | Due ${deliverable.due_date}` : ""}
                      </div>
                      {deliverable.review_notes ? <div className="mt-2 text-sm text-muted-foreground">{deliverable.review_notes}</div> : null}
                    </div>
                  </div>

                  {deliverable.can.upload_version ? (
                    <form
                      className="mt-4 grid gap-3"
                      onSubmit={(event) => {
                        event.preventDefault();
                        const formData = new FormData(event.currentTarget);
                        router.post(`/marketing/deliverables/${deliverable.id}/versions`, formData, {
                          forceFormData: true,
                          preserveScroll: true,
                        });
                      }}
                    >
                      <textarea name="change_notes" rows={3} placeholder="What changed in this revision?" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <input name="external_reference" type="url" placeholder="Optional external proof link" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <input name="asset_file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
                      <button type="submit" className="rounded-md bg-indigo-700 px-4 py-2 text-sm text-white hover:bg-indigo-800">Upload Deliverable Version</button>
                    </form>
                  ) : null}
                  <div className="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-muted-foreground">
                    {versions.length} version{versions.length === 1 ? "" : "s"} uploaded for this deliverable.
                  </div>
                </div>
              );
            })}
            {deliverables.length === 0 ? <div className="rounded-xl border p-4 text-sm text-muted-foreground">No deliverables have been created for this request yet.</div> : null}
            </div>
          </div>
        </section>

        <section className="rounded-2xl border border-amber-200 bg-white shadow-sm">
          <div className="border-b border-amber-100 bg-amber-50 px-4 py-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-amber-700">Workflow 3</div>
            <h2 className="text-lg font-semibold text-slate-950">Reviews</h2>
            <p className="text-sm text-slate-600">Use this area for feedback, version review history, and coordination notes before approval.</p>
          </div>
          <div className="grid gap-4 p-4 xl:grid-cols-[0.9fr_1.1fr]">
            <div className="rounded-xl border p-4">
              <h3 className="text-base font-semibold">Review conversation</h3>
              {requestRecord.can.comment ? (
                <form
                  className="mt-4 grid gap-3"
                  onSubmit={(event) => {
                    event.preventDefault();
                    const formData = new FormData(event.currentTarget);
                    router.post(`/marketing/requests/${requestRecord.id}/comment`, {
                      message: formData.get("message"),
                    }, { preserveScroll: true });
                    event.currentTarget.reset();
                  }}
                >
                  <textarea name="message" rows={4} placeholder="Add clarification, feedback, or review guidance." className="rounded-md border bg-background px-3 py-2 text-sm" />
                  <button type="submit" className="rounded-md bg-amber-700 px-4 py-2 text-sm text-white hover:bg-amber-800">Post Review Note</button>
                </form>
              ) : null}
              <div className="mt-4 space-y-3">
                {comments.length === 0 ? <div className="text-sm text-muted-foreground">No review comments recorded yet.</div> : comments.map((comment) => (
                  <div key={comment.id} className="rounded-md border p-3">
                    <div className="text-xs text-muted-foreground">{comment.user_name ?? "-"} | {comment.created_at ?? "-"}</div>
                    <div className="mt-1 text-sm">{comment.message}</div>
                  </div>
                ))}
              </div>
            </div>
            <div className="space-y-4">
              {deliverables.map((deliverable) => {
                const versions = Array.isArray(deliverable.versions) ? deliverable.versions : (deliverable.versions.data ?? []);

                return (
                  <div key={deliverable.id} className="rounded-xl border p-4">
                    <div className="font-medium">{deliverable.title}</div>
                    <div className="mt-3 space-y-3">
                      {versions.length === 0 ? <div className="text-sm text-muted-foreground">No versions available for review yet.</div> : versions.map((version) => (
                        <div key={version.id} className="rounded-md border p-3">
                          <div className="text-sm font-medium">Version {version.version_number}</div>
                          <div className="mt-1 text-xs text-muted-foreground">
                            {version.approval_status.replaceAll("_", " ")} | {version.uploaded_by_name ?? "-"} | {version.created_at ?? "-"}
                          </div>
                          <div className="mt-1 text-xs text-muted-foreground">{version.asset_file_name ?? version.external_reference ?? "No file linked"}</div>
                          {version.change_notes ? <div className="mt-2 text-sm text-muted-foreground">{version.change_notes}</div> : null}
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}
              <details className="rounded-xl border p-4">
                <summary className="cursor-pointer text-sm font-medium">Activity history</summary>
                <div className="mt-4 space-y-3">
                  {activities.length === 0 ? <div className="text-sm text-muted-foreground">No activity history recorded yet.</div> : activities.map((activity) => (
                    <div key={activity.id} className="rounded-md border p-3">
                      <div className="text-xs text-muted-foreground">{activity.actor_name ?? "System"} | {activity.created_at ?? "-"}</div>
                      <div className="mt-1 text-sm">{activity.summary}</div>
                    </div>
                  ))}
                </div>
              </details>
            </div>
          </div>
        </section>

        <section className="rounded-2xl border border-emerald-200 bg-white shadow-sm">
          <div className="border-b border-emerald-100 bg-emerald-50 px-4 py-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-emerald-700">Workflow 4</div>
            <h2 className="text-lg font-semibold text-slate-950">Content Approvals</h2>
            <p className="text-sm text-slate-600">Approve final deliverables, publish approved assets, and optionally send approved assets to the organization vault.</p>
          </div>
          <div className="space-y-4 p-4">
            {deliverables.map((deliverable) => {
              const assets = Array.isArray(deliverable.assets) ? deliverable.assets : (deliverable.assets.data ?? []);

              return (
                <div key={deliverable.id} className="rounded-xl border p-4">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-base font-semibold">{deliverable.title}</h3>
                        <span className="rounded-full border px-2 py-1 text-xs capitalize">{deliverable.status.replaceAll("_", " ")}</span>
                      </div>
                      <div className="mt-1 text-xs text-muted-foreground">
                        {deliverable.deliverable_type.replaceAll("_", " ")} | {deliverable.assigned_unit.replaceAll("_", " ")} | {deliverable.assignee_name ?? "Unassigned"}
                        {deliverable.due_date ? ` | Due ${deliverable.due_date}` : ""}
                      </div>
                    </div>
                  </div>
                  <div className="mt-4 grid gap-4 xl:grid-cols-[0.8fr_1.2fr]">
                    <div className="rounded-lg border p-3">
                      <div className="font-medium">Approval decision</div>
                      <div className="mt-3 space-y-3">
                        {deliverable.can.approve ? (
                          <div className="grid gap-3">
                            <form
                              className="grid gap-3"
                              onSubmit={(event) => {
                                event.preventDefault();
                                const formData = new FormData(event.currentTarget);
                                router.post(`/marketing/deliverables/${deliverable.id}/approve`, {
                                  review_notes: formData.get("review_notes"),
                                  reusable: formData.get("reusable") ? 1 : 0,
                                }, { preserveScroll: true });
                              }}
                            >
                              <textarea name="review_notes" rows={3} placeholder="Approval notes" className="rounded-md border bg-background px-3 py-2 text-sm" />
                              <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                <input type="checkbox" name="reusable" value="1" />
                                Mark approved asset as reusable
                              </label>
                              <button type="submit" className="rounded-md bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700">Approve Deliverable</button>
                            </form>
                            <form
                              className="grid gap-3"
                              onSubmit={(event) => {
                                event.preventDefault();
                                const formData = new FormData(event.currentTarget);
                                router.post(`/marketing/deliverables/${deliverable.id}/request-changes`, {
                                  review_notes: formData.get("review_notes"),
                                }, { preserveScroll: true });
                              }}
                            >
                              <textarea name="review_notes" rows={3} placeholder="Change request notes" className="rounded-md border bg-background px-3 py-2 text-sm" />
                              <button type="submit" className="rounded-md bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">Request Changes</button>
                            </form>
                          </div>
                        ) : <div className="text-sm text-muted-foreground">You do not have approval rights for this deliverable.</div>}
                      </div>
                    </div>

                    <div className="rounded-lg border p-3">
                      <div className="font-medium">Approved assets and publishing</div>
                      <div className="mt-3 space-y-3">
                        {assets.length === 0 ? <div className="text-sm text-muted-foreground">No approved assets available yet.</div> : assets.map((asset) => (
                          <div key={asset.id} className="rounded-md border p-3">
                            <div className="font-medium">{asset.asset_type.replaceAll("_", " ")}</div>
                            <div className="mt-1 text-xs text-muted-foreground">
                              {asset.asset_file_name ?? "No file name"} | Version {asset.version_number ?? "-"} | {asset.reusable ? "Reusable" : "Single-use"}
                            </div>

                            {asset.can.publish ? (
                              <form
                                className="mt-3 grid gap-2"
                                onSubmit={(event) => {
                                  event.preventDefault();
                                  const formData = new FormData(event.currentTarget);
                                  router.post(`/marketing/assets/${asset.id}/publish`, {
                                    publication_channel: formData.get("publication_channel"),
                                    published_at: formData.get("published_at"),
                                    external_reference: formData.get("external_reference"),
                                    publication_notes: formData.get("publication_notes"),
                                    metrics: {
                                      metric_date: formData.get("metric_date"),
                                      reach: formData.get("reach"),
                                      impressions: formData.get("impressions"),
                                      engagements: formData.get("engagements"),
                                      clicks: formData.get("clicks"),
                                      sessions: formData.get("sessions"),
                                      conversions: formData.get("conversions"),
                                      followers: formData.get("followers"),
                                    },
                                  }, { preserveScroll: true });
                                }}
                              >
                                <select name="publication_channel" className="rounded-md border bg-background px-3 py-2 text-sm">
                                  <option value="Facebook">Facebook</option>
                                  <option value="Instagram">Instagram</option>
                                  <option value="LinkedIn">LinkedIn</option>
                                  <option value="X">X</option>
                                  <option value="Website">Website</option>
                                  <option value="Email Campaign">Email Campaign</option>
                                  <option value="Print">Print</option>
                                  <option value="Internal">Internal</option>
                                </select>
                                <input name="published_at" type="datetime-local" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                <input name="external_reference" placeholder="Publication URL or reference" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                <textarea name="publication_notes" rows={2} placeholder="Publication notes" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                <div className="grid gap-2 md:grid-cols-4">
                                  <input name="metric_date" type="date" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="reach" type="number" min="0" placeholder="Reach" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="impressions" type="number" min="0" placeholder="Impressions" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="engagements" type="number" min="0" placeholder="Engagements" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="clicks" type="number" min="0" placeholder="Clicks" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="sessions" type="number" min="0" placeholder="Sessions" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="conversions" type="number" min="0" placeholder="Conversions" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                  <input name="followers" type="number" min="0" placeholder="Followers" className="rounded-md border bg-background px-3 py-2 text-sm" />
                                </div>
                                <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Publish Asset</button>
                              </form>
                            ) : null}

                            {asset.can.archive && !asset.archived_at ? (
                              <form
                                className="mt-3"
                                onSubmit={(event) => {
                                  event.preventDefault();
                                  router.post(`/marketing/assets/${asset.id}/archive`, {}, { preserveScroll: true });
                                }}
                              >
                                <button type="submit" className="rounded-md bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-800">
                                  Archive Asset
                                </button>
                              </form>
                            ) : null}

                            {canManageVault && asset.can.publish_to_vault ? (
                              <details className="mt-3 rounded-md border border-dashed p-3">
                                <summary className="cursor-pointer text-sm font-medium">Publish approved asset to Organization Vault</summary>
                                <PublishAssetToVaultForm
                                  asset={asset}
                                  deliverableTitle={deliverable.title}
                                  departments={departments}
                                  users={users}
                                  documentTypes={documentTypes}
                                  slotOptions={slotOptions}
                                />
                              </details>
                            ) : null}

                            {(Array.isArray(asset.publications) ? asset.publications : asset.publications.data ?? []).length ? (
                              <div className="mt-3 space-y-2">
                                {(Array.isArray(asset.publications) ? asset.publications : asset.publications.data ?? []).map((publication) => (
                                  <div key={publication.id} className="rounded-md border border-dashed p-2 text-xs text-muted-foreground">
                                    {publication.publication_channel} | {publication.published_by_name ?? "-"} | {publication.published_at ?? "-"}
                                  </div>
                                ))}
                              </div>
                            ) : null}
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
            {deliverables.length === 0 ? <div className="rounded-xl border p-4 text-sm text-muted-foreground">No deliverables are available for approval yet.</div> : null}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
