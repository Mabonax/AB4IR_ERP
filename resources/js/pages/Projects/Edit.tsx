import { ProjectFormPage } from "@/components/project-form-page";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { type BreadcrumbItem } from "@/types";

type StatusTransition = {
  status: string;
  label: string;
  ready: boolean;
  blockers: string[];
};

type StatusSummary = {
  current: string;
  current_label: string;
  allowed_transitions: StatusTransition[];
  readiness: {
    active: { ready: boolean; blockers: string[] };
    completed: { ready: boolean; blockers: string[] };
  };
};

const statusTone = (ready: boolean) =>
  ready
    ? "border-emerald-200 bg-emerald-50 text-emerald-700"
    : "border-amber-200 bg-amber-50 text-amber-700";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "List", href: "/projects/list" },
  { title: "Edit", href: "#" },
];

function ProjectStatusPanel({ summary }: { summary?: StatusSummary | null }) {
  if (!summary) return null;

  return (
    <Card>
      <CardHeader>
        <CardTitle>Status Readiness</CardTitle>
        <CardDescription>Current transition state and blockers before changing delivery status.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        <div className="flex flex-wrap items-center gap-2">
          <span className="font-semibold text-slate-900">Current Status</span>
          <span className="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700">
            {summary.current_label}
          </span>
        </div>

        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Allowed transitions</p>
          {summary.allowed_transitions.length === 0 ? (
            <p className="mt-2 text-slate-600">No further transitions are currently allowed.</p>
          ) : (
            <div className="mt-2 flex flex-wrap gap-2">
              {summary.allowed_transitions.map((transition) => (
                <span
                  key={transition.status}
                  className={`rounded-full border px-2.5 py-1 text-xs font-medium ${statusTone(transition.ready)}`}
                >
                  {transition.label}
                  {!transition.ready ? ` (${transition.blockers.length} blocker${transition.blockers.length === 1 ? "" : "s"})` : ""}
                </span>
              ))}
            </div>
          )}
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          {(["active", "completed"] as const).map((statusKey) => {
            const readiness = summary.readiness[statusKey];

            return (
              <div key={statusKey} className={`rounded-lg border p-3 ${statusTone(readiness.ready)}`}>
                <p className="text-xs font-semibold uppercase tracking-wide">
                  {statusKey === "active" ? "Activation readiness" : "Completion readiness"}
                </p>
                <p className="mt-1 text-xs font-medium">
                  {readiness.ready ? "Ready" : `${readiness.blockers.length} blocker${readiness.blockers.length === 1 ? "" : "s"}`}
                </p>
                {!readiness.ready ? (
                  <ul className="mt-2 space-y-1 text-xs">
                    {readiness.blockers.map((blocker) => (
                      <li key={blocker}>{blocker}</li>
                    ))}
                  </ul>
                ) : null}
              </div>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}

export default function ProjectEdit(props: {
  project: any;
  programs: { id: number; title: string }[];
  stakeholders: { id: number; name: string }[];
  partnerStakeholders: { id: number; name: string }[];
  staffMembers: { id: number; name: string }[];
}) {
  const projectData = props.project?.data ?? props.project;

  return (
    <ProjectFormPage
      pageTitle="Edit Project"
      pageDescription="Update the delivery structure, governance metadata, and reporting configuration for this project."
      submitLabel="Save Project"
      submitMethod="put"
      submitUrl={`/projects/${projectData.id}`}
      breadcrumbs={breadcrumbs}
      programs={props.programs}
      stakeholders={props.stakeholders}
      partnerStakeholders={props.partnerStakeholders}
      staffMembers={props.staffMembers}
      initialData={{
        name: projectData.name ?? "",
        description: projectData.description ?? "",
        start_date: projectData.start_date ?? "",
        end_date: projectData.end_date ?? "",
        status: projectData.status ?? "planned",
        program_id: projectData.program_id !== null && projectData.program_id !== undefined ? String(projectData.program_id) : "",
        sponsor_stakeholder_id: projectData.sponsor_stakeholder_id !== null && projectData.sponsor_stakeholder_id !== undefined ? String(projectData.sponsor_stakeholder_id) : "",
        partner_stakeholder_ids: Array.isArray(projectData.partner_stakeholder_ids)
          ? projectData.partner_stakeholder_ids.map((value: string | number) => String(value))
          : [],
        project_manager_id: projectData.project_manager_id !== null && projectData.project_manager_id !== undefined ? String(projectData.project_manager_id) : "",
        contract_reference: projectData.contract_reference ?? "",
        funding_amount: projectData.funding_amount !== null && projectData.funding_amount !== undefined ? String(projectData.funding_amount) : "",
        reporting_cadence: projectData.reporting_cadence ?? "",
        reporting_obligations: projectData.reporting_obligations ?? "",
      }}
    >
      <ProjectStatusPanel summary={projectData.status_summary} />
    </ProjectFormPage>
  );
}
