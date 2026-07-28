import { Head, Link, useForm } from "@inertiajs/react";
import { BriefcaseBusiness, CalendarRange, CircleDollarSign, FileText, Handshake, Layers3, UserRoundCog } from "lucide-react";
import { type ReactNode } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { projectNavItems } from "@/config/domain-nav/projects";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type ProjectOption = { id: number; title?: string; name?: string };
type ProjectData = {
  id?: number;
  name: string;
  project_code: string;
  description: string;
  primary_location: string;
  start_date: string;
  end_date: string;
  status: string;
  program_id: string;
  sponsor_stakeholder_id: string;
  partner_stakeholder_ids: string[];
  project_manager_id: string;
  contract_reference: string;
  funding_amount: string;
  budget: string;
  target_beneficiaries: string;
  reporting_cadence: string;
  reporting_obligations: string;
};

type ProjectFormPageProps = {
  pageTitle: string;
  pageDescription: string;
  submitLabel: string;
  submitMethod: "post" | "put";
  submitUrl: string;
  breadcrumbs: BreadcrumbItem[];
  programs: ProjectOption[];
  stakeholders: ProjectOption[];
  partnerStakeholders: ProjectOption[];
  staffMembers: ProjectOption[];
  initialData?: Partial<ProjectData>;
  children?: ReactNode;
};

const defaultProjectData: ProjectData = {
  name: "",
  project_code: "",
  description: "",
  primary_location: "",
  start_date: "",
  end_date: "",
  status: "planned",
  program_id: "",
  sponsor_stakeholder_id: "",
  partner_stakeholder_ids: [],
  project_manager_id: "",
  contract_reference: "",
  funding_amount: "",
  budget: "",
  target_beneficiaries: "",
  reporting_cadence: "",
  reporting_obligations: "",
};

const statusOptions = [
  { label: "Planned", value: "planned" },
  { label: "Active", value: "active" },
  { label: "Completed", value: "completed" },
  { label: "On Hold", value: "on_hold" },
  { label: "Cancelled", value: "cancelled" },
];

const cadenceOptions = [
  { label: "Monthly", value: "monthly" },
  { label: "Quarterly", value: "quarterly" },
  { label: "Biannual", value: "biannual" },
  { label: "Annual", value: "annual" },
  { label: "Ad Hoc", value: "ad_hoc" },
];

export function ProjectFormPage({
  pageTitle,
  pageDescription,
  submitLabel,
  submitMethod,
  submitUrl,
  breadcrumbs,
  programs,
  stakeholders,
  partnerStakeholders,
  staffMembers,
  initialData,
  children,
}: ProjectFormPageProps) {
  const form = useForm<ProjectData>({
    ...defaultProjectData,
    ...initialData,
    partner_stakeholder_ids: initialData?.partner_stakeholder_ids ?? defaultProjectData.partner_stakeholder_ids,
  });

  const submit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (submitMethod === "put") {
      form.put(submitUrl);

      return;
    }

    form.post(submitUrl);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <form onSubmit={submit} className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{pageTitle}</h1>
            <p className="text-sm text-muted-foreground">{pageDescription}</p>
          </div>
          <DomainNav items={projectNavItems} />
        </div>

        {children}

        <div className="grid gap-6 xl:grid-cols-[1.7fr,1fr]">
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <BriefcaseBusiness className="h-4 w-4 text-red-600" />
                  Project Identity
                </CardTitle>
                <CardDescription>Core project details and delivery window.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <Label htmlFor="project-name">Project Name</Label>
                  <Input
                    id="project-name"
                    value={form.data.name}
                    onChange={(event) => form.setData("name", event.target.value)}
                    placeholder="Enter project name"
                  />
                  {form.errors.name ? <p className="mt-1 text-sm text-red-600">{form.errors.name}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-code">Project Code</Label>
                  <Input
                    id="project-code"
                    value={form.data.project_code}
                    onChange={(event) => form.setData("project_code", event.target.value)}
                    placeholder="PROJ-001"
                  />
                  {form.errors.project_code ? <p className="mt-1 text-sm text-red-600">{form.errors.project_code}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-primary-location">Primary Location</Label>
                  <Input
                    id="project-primary-location"
                    value={form.data.primary_location}
                    onChange={(event) => form.setData("primary_location", event.target.value)}
                    placeholder="Johannesburg South"
                  />
                  {form.errors.primary_location ? <p className="mt-1 text-sm text-red-600">{form.errors.primary_location}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-start-date">Start Date</Label>
                  <Input
                    id="project-start-date"
                    type="date"
                    value={form.data.start_date}
                    onChange={(event) => form.setData("start_date", event.target.value)}
                  />
                  {form.errors.start_date ? <p className="mt-1 text-sm text-red-600">{form.errors.start_date}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-end-date">End Date</Label>
                  <Input
                    id="project-end-date"
                    type="date"
                    value={form.data.end_date}
                    onChange={(event) => form.setData("end_date", event.target.value)}
                  />
                  {form.errors.end_date ? <p className="mt-1 text-sm text-red-600">{form.errors.end_date}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-status">Status</Label>
                  <select
                    id="project-status"
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    value={form.data.status}
                    onChange={(event) => form.setData("status", event.target.value)}
                  >
                    {statusOptions.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                  {form.errors.status ? <p className="mt-1 text-sm text-red-600">{form.errors.status}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-budget">Project Budget</Label>
                  <Input
                    id="project-budget"
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.data.budget}
                    onChange={(event) => form.setData("budget", event.target.value)}
                    placeholder="0.00"
                  />
                  {form.errors.budget ? <p className="mt-1 text-sm text-red-600">{form.errors.budget}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-target-beneficiaries">Target Beneficiaries</Label>
                  <Input
                    id="project-target-beneficiaries"
                    type="number"
                    min="0"
                    value={form.data.target_beneficiaries}
                    onChange={(event) => form.setData("target_beneficiaries", event.target.value)}
                    placeholder="0"
                  />
                  {form.errors.target_beneficiaries ? <p className="mt-1 text-sm text-red-600">{form.errors.target_beneficiaries}</p> : null}
                </div>

                <div className="sm:col-span-2">
                  <Label htmlFor="project-description">Description</Label>
                  <textarea
                    id="project-description"
                    value={form.data.description}
                    onChange={(event) => form.setData("description", event.target.value)}
                    placeholder="Capture what the project is meant to deliver."
                    className="min-h-28 w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                  {form.errors.description ? <p className="mt-1 text-sm text-red-600">{form.errors.description}</p> : null}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Layers3 className="h-4 w-4 text-red-600" />
                  Delivery Structure
                </CardTitle>
                <CardDescription>Program alignment and responsible delivery lead.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 sm:grid-cols-2">
                <div>
                  <Label htmlFor="project-program">Program</Label>
                  <select
                    id="project-program"
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    value={form.data.program_id}
                    onChange={(event) => form.setData("program_id", event.target.value)}
                  >
                    <option value="">Select program</option>
                    {programs.map((program) => (
                      <option key={program.id} value={program.id}>
                        {program.title ?? program.name ?? `Program ${program.id}`}
                      </option>
                    ))}
                  </select>
                  {form.errors.program_id ? <p className="mt-1 text-sm text-red-600">{form.errors.program_id}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-manager">Project Manager</Label>
                  <select
                    id="project-manager"
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    value={form.data.project_manager_id}
                    onChange={(event) => form.setData("project_manager_id", event.target.value)}
                  >
                    <option value="">Select project manager</option>
                    {staffMembers.map((staffMember) => (
                      <option key={staffMember.id} value={staffMember.id}>
                        {staffMember.name ?? `Staff ${staffMember.id}`}
                      </option>
                    ))}
                  </select>
                  {form.errors.project_manager_id ? <p className="mt-1 text-sm text-red-600">{form.errors.project_manager_id}</p> : null}
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Handshake className="h-4 w-4 text-red-600" />
                  Stakeholders
                </CardTitle>
                <CardDescription>Sponsor and implementation partner relationships.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label htmlFor="project-sponsor">Sponsor</Label>
                  <select
                    id="project-sponsor"
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    value={form.data.sponsor_stakeholder_id}
                    onChange={(event) => form.setData("sponsor_stakeholder_id", event.target.value)}
                  >
                    <option value="">No sponsor</option>
                    {stakeholders.map((stakeholder) => (
                      <option key={stakeholder.id} value={stakeholder.id}>
                        {stakeholder.name ?? `Stakeholder ${stakeholder.id}`}
                      </option>
                    ))}
                  </select>
                  {form.errors.sponsor_stakeholder_id ? <p className="mt-1 text-sm text-red-600">{form.errors.sponsor_stakeholder_id}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-partners">Implementation Partners</Label>
                  <select
                    id="project-partners"
                    multiple
                    className="min-h-36 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    value={form.data.partner_stakeholder_ids}
                    onChange={(event) => {
                      const values = Array.from(event.target.selectedOptions, (option) => option.value);
                      form.setData("partner_stakeholder_ids", values);
                    }}
                  >
                    {partnerStakeholders.map((stakeholder) => (
                      <option key={stakeholder.id} value={String(stakeholder.id)}>
                        {stakeholder.name ?? `Stakeholder ${stakeholder.id}`}
                      </option>
                    ))}
                  </select>
                  <p className="mt-1 text-xs text-muted-foreground">Hold Ctrl or Cmd to select multiple partners.</p>
                  {form.errors.partner_stakeholder_ids ? <p className="mt-1 text-sm text-red-600">{form.errors.partner_stakeholder_ids}</p> : null}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <CircleDollarSign className="h-4 w-4 text-red-600" />
                  Commercials
                </CardTitle>
                <CardDescription>Funding and contract tracking metadata.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label htmlFor="project-contract-reference">Contract Reference</Label>
                  <Input
                    id="project-contract-reference"
                    value={form.data.contract_reference}
                    onChange={(event) => form.setData("contract_reference", event.target.value)}
                    placeholder="Contract or funding reference"
                  />
                  {form.errors.contract_reference ? <p className="mt-1 text-sm text-red-600">{form.errors.contract_reference}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-funding-amount">Funding Amount</Label>
                  <Input
                    id="project-funding-amount"
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.data.funding_amount}
                    onChange={(event) => form.setData("funding_amount", event.target.value)}
                    placeholder="0.00"
                  />
                  {form.errors.funding_amount ? <p className="mt-1 text-sm text-red-600">{form.errors.funding_amount}</p> : null}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <FileText className="h-4 w-4 text-red-600" />
                  Reporting
                </CardTitle>
                <CardDescription>Cadence and reporting obligations for governance follow-up.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label htmlFor="project-reporting-cadence">Reporting Cadence</Label>
                  <select
                    id="project-reporting-cadence"
                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                    value={form.data.reporting_cadence}
                    onChange={(event) => form.setData("reporting_cadence", event.target.value)}
                  >
                    <option value="">Select cadence</option>
                    {cadenceOptions.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                  {form.errors.reporting_cadence ? <p className="mt-1 text-sm text-red-600">{form.errors.reporting_cadence}</p> : null}
                </div>

                <div>
                  <Label htmlFor="project-reporting-obligations">Reporting Obligations</Label>
                  <textarea
                    id="project-reporting-obligations"
                    value={form.data.reporting_obligations}
                    onChange={(event) => form.setData("reporting_obligations", event.target.value)}
                    placeholder="Capture sponsor or partner reporting expectations."
                    className="min-h-28 w-full rounded-md border bg-card px-3 py-2 text-sm"
                  />
                  {form.errors.reporting_obligations ? <p className="mt-1 text-sm text-red-600">{form.errors.reporting_obligations}</p> : null}
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
          <div className="text-sm text-muted-foreground">
            Project creation should happen as a proper record workflow, not inside a modal.
          </div>
          <div className="flex gap-2">
            <Button type="button" variant="outline" asChild>
              <Link href="/projects/list">Cancel</Link>
            </Button>
            <Button type="submit" disabled={form.processing}>
              <CalendarRange className="mr-2 h-4 w-4" />
              {submitLabel}
            </Button>
          </div>
        </div>
      </form>
    </AppLayout>
  );
}
