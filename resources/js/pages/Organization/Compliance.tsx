import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { organizationNavItems } from "@/config/domain-nav/organization";
import { ComplianceRecordModelFormConfig } from "@/config/forms/compliance-record-model-form";
import { ComplianceRecordTableConfig } from "@/config/tables/compliance-record-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Compliance", href: "/organization/compliance" },
];

type ComplianceRecordRow = {
  id: number;
  organisation_id: number;
  organisation_name: string;
  title: string;
  compliance_area: string;
  reference_code?: string | null;
  filing_frequency?: string | null;
  due_date?: string | null;
  submitted_at?: string | null;
  status: string;
  owner_name?: string | null;
  notes?: string | null;
};

type OrganisationOption = {
  id: number;
  name: string;
};

const statusLabels: Record<string, string> = {
  planned: "Planned",
  in_progress: "In Progress",
  submitted: "Submitted",
  approved: "Approved",
  overdue: "Overdue",
};

const frequencyLabels: Record<string, string> = {
  monthly: "Monthly",
  quarterly: "Quarterly",
  biannual: "Biannual",
  annual: "Annual",
  ad_hoc: "Ad Hoc",
};

export default function ComplianceRegistry({
  stats,
  records,
  organisations,
}: {
  stats: Record<string, number>;
  records: ComplianceRecordRow[];
  organisations: OrganisationOption[];
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.compliance.manage") || permissions.includes("domain.organization.manage");
  const [open, setOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<ComplianceRecordRow | null>(null);

  const mappedRecordData = selectedRecord
    ? {
        organisation_id: String(selectedRecord.organisation_id ?? ""),
        title: selectedRecord.title ?? "",
        compliance_area: selectedRecord.compliance_area ?? "",
        reference_code: selectedRecord.reference_code ?? "",
        filing_frequency: selectedRecord.filing_frequency ?? "",
        due_date: selectedRecord.due_date ?? "",
        submitted_at: selectedRecord.submitted_at ?? "",
        status: selectedRecord.status ?? "planned",
        owner_name: selectedRecord.owner_name ?? "",
        notes: selectedRecord.notes ?? "",
      }
    : {};

  const rows = records.map((record) => ({
    ...record,
    status_label: statusLabels[record.status] ?? record.status,
    filing_frequency_label: record.filing_frequency ? (frequencyLabels[record.filing_frequency] ?? record.filing_frequency) : "-",
    due_date: record.due_date ?? "-",
    owner_name: record.owner_name ?? "-",
  }));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Compliance Registry" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Compliance Registry</h1>
            <p className="text-sm text-muted-foreground">
              Track statutory submissions, governance obligations, and recurring organisation compliance deadlines.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {canManage ? (
              <CustomModelForm
                addButton={ComplianceRecordModelFormConfig.addButton}
                title="Add Compliance Record"
                description={ComplianceRecordModelFormConfig.description}
                fields={ComplianceRecordModelFormConfig.fields}
                options={{ organisations }}
                submitRoute={() => ({ url: "/organization/compliance", method: "post" })}
              />
            ) : null}
            <DomainNav items={organizationNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Total Records</CardTitle>
              <CardDescription>Tracked obligations</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Due Soon</CardTitle>
              <CardDescription>Open items due in 30 days</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.due_soon ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Submitted</CardTitle>
              <CardDescription>Awaiting closure or approval</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.submitted ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Overdue</CardTitle>
              <CardDescription>Past due and still unresolved</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.overdue ?? 0}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Organisation Compliance Tracker</CardTitle>
            <CardDescription>
              Keep filing cycles, accountable owners, and evidence dates visible before they become audit gaps.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={ComplianceRecordTableConfig.columns}
              data={rows}
              actions={canManage ? [
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedRecord(row);
                    setOpen(true);
                  },
                },
              ] : []}
            />
          </CardContent>
        </Card>

        {selectedRecord ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Compliance Record"
            description={ComplianceRecordModelFormConfig.description}
            fields={ComplianceRecordModelFormConfig.fields}
            options={{ organisations }}
            mode="edit"
            initialData={mappedRecordData}
            submitRoute={(routeParams) => ({ url: `/organization/compliance/${routeParams}`, method: "put" })}
            routeParams={selectedRecord.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
