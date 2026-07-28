import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { organizationNavItems } from "@/config/domain-nav/organization";
import { OrganisationModelFormConfig } from "@/config/forms/organisation-model-form";
import { OrganisationTableConfig } from "@/config/tables/organisation-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Organisation Registry", href: "/organization/registry" },
];

type OrganisationRow = {
  id: number;
  name: string;
  registration_number: string;
  organisation_type: string;
  npo_number?: string | null;
  pbo_number?: string | null;
  tax_reference_number?: string | null;
  constitution_version?: string | null;
  registered_at?: string | null;
  status: string;
  contact_email?: string | null;
  contact_phone?: string | null;
  contact_person?: string | null;
};

export default function OrganisationRegistry({
  stats,
  organisations,
}: {
  stats: Record<string, number>;
  organisations: OrganisationRow[];
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.organization.manage");
  const [open, setOpen] = useState(false);
  const [selectedOrganisation, setSelectedOrganisation] = useState<OrganisationRow | null>(null);

  const mappedOrganisationData = selectedOrganisation
    ? {
        name: selectedOrganisation.name ?? "",
        registration_number: selectedOrganisation.registration_number ?? "",
        organisation_type: selectedOrganisation.organisation_type ?? "NPC",
        npo_number: selectedOrganisation.npo_number ?? "",
        pbo_number: selectedOrganisation.pbo_number ?? "",
        tax_reference_number: selectedOrganisation.tax_reference_number ?? "",
        constitution_version: selectedOrganisation.constitution_version ?? "",
        registered_at: selectedOrganisation.registered_at ?? "",
        status: selectedOrganisation.status ?? "active",
        "contact_details.contact_person": selectedOrganisation.contact_person ?? "",
        "contact_details.email": selectedOrganisation.contact_email ?? "",
        "contact_details.phone": selectedOrganisation.contact_phone ?? "",
      }
    : {};

  const rows = organisations.map((organisation) => ({
    ...organisation,
    compliance_refs: [organisation.npo_number, organisation.pbo_number].filter(Boolean).join(" | ") || "-",
    contact: [organisation.contact_person, organisation.contact_email, organisation.contact_phone].filter(Boolean).join(" | ") || "-",
  }));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Organisation Registry" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Organisation Registry</h1>
            <p className="text-sm text-muted-foreground">
              Legal entity master data for NPC, NPO, and PBO operations across the platform.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {canManage ? (
              <CustomModelForm
                addButton={OrganisationModelFormConfig.addButton}
                title="Add Organisation"
                description={OrganisationModelFormConfig.description}
                fields={OrganisationModelFormConfig.fields}
                submitRoute={() => ({ url: "/organization/registry", method: "post" })}
              />
            ) : null}
            <DomainNav items={organizationNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Total Entities</CardTitle>
              <CardDescription>Tracked legal entities</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Entities</CardTitle>
              <CardDescription>Currently active on the platform</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.active ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>NPC Records</CardTitle>
              <CardDescription>Non-profit company entities</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.npc ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Compliance Ready</CardTitle>
              <CardDescription>Entities with NPO or PBO references</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.compliance_ready ?? 0}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Registered Organisations</CardTitle>
            <CardDescription>
              Maintain the legal entity registry before layering governance, compliance, funding, and reporting workflows.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={OrganisationTableConfig.columns}
              data={rows}
              actions={canManage ? [
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedOrganisation(row);
                    setOpen(true);
                  },
                },
              ] : []}
            />
          </CardContent>
        </Card>

        {selectedOrganisation ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Organisation"
            description={OrganisationModelFormConfig.description}
            fields={OrganisationModelFormConfig.fields}
            mode="edit"
            initialData={mappedOrganisationData}
            submitRoute={(routeParams) => ({ url: `/organization/registry/${routeParams}`, method: "put" })}
            routeParams={selectedOrganisation.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
