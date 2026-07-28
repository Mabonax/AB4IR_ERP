import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { governanceNavItems } from "@/config/domain-nav/governance";
import { GovernanceStructureModelFormConfig } from "@/config/forms/governance-structure-model-form";
import { GovernanceStructureTableConfig } from "@/config/tables/governance-structure-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Governance", href: "/governance" },
];

type OrganisationOption = { id: number; name: string };
type GovernanceStructureRow = {
  id: number;
  organisation_id: number;
  organisation_name: string | null;
  name: string;
  description?: string | null;
  status: string;
};

export default function GovernanceIndex({
  stats,
  structures,
  organisations,
}: {
  stats: Record<string, number>;
  structures: GovernanceStructureRow[];
  organisations: OrganisationOption[];
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.governance.manage");
  const [open, setOpen] = useState(false);
  const [selectedStructure, setSelectedStructure] = useState<GovernanceStructureRow | null>(null);

  const mappedStructureData = selectedStructure
    ? {
        organisation_id: String(selectedStructure.organisation_id ?? ""),
        name: selectedStructure.name ?? "",
        description: selectedStructure.description ?? "",
        status: selectedStructure.status ?? "active",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Governance" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Governance Structures</h1>
            <p className="text-sm text-muted-foreground">
              Formal governance bodies, upcoming meetings, and resolution pressure in one workspace.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {canManage ? (
              <CustomModelForm
                addButton={GovernanceStructureModelFormConfig.addButton}
                title="Add Governance Structure"
                description={GovernanceStructureModelFormConfig.description}
                fields={GovernanceStructureModelFormConfig.fields}
                options={{ organisations }}
                submitRoute={() => ({ url: "/governance", method: "post" })}
              />
            ) : null}
            <DomainNav items={governanceNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Total Structures</CardTitle>
              <CardDescription>Tracked governance bodies</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Upcoming Meetings</CardTitle>
              <CardDescription>Forward governance calendar</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.upcoming_meetings ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Open Resolutions</CardTitle>
              <CardDescription>Actions still requiring execution</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.open_resolutions ?? 0}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Governance Register</CardTitle>
            <CardDescription>Board, committee, and oversight structures across the organisation.</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={GovernanceStructureTableConfig.columns}
              data={structures}
              actions={canManage ? [
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedStructure(row);
                    setOpen(true);
                  },
                },
              ] : []}
            />
          </CardContent>
        </Card>

        {selectedStructure ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Governance Structure"
            description={GovernanceStructureModelFormConfig.description}
            fields={GovernanceStructureModelFormConfig.fields}
            options={{ organisations }}
            mode="edit"
            initialData={mappedStructureData}
            submitRoute={(routeParams) => ({ url: `/governance/${routeParams}`, method: "put" })}
            routeParams={selectedStructure.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
