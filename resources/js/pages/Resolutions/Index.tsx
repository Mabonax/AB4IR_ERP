import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { governanceNavItems } from "@/config/domain-nav/governance";
import { ResolutionModelFormConfig } from "@/config/forms/resolution-model-form";
import { ResolutionTableConfig } from "@/config/tables/resolution-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Resolutions", href: "/resolutions" },
];

type Option = { id: number; name: string };
type ResolutionRow = {
  id: number;
  organisation_id: number;
  organisation_name: string | null;
  meeting_id: number;
  meeting_title: string | null;
  resolution_number: string;
  title: string;
  description?: string | null;
  owner_id?: number | null;
  owner_name?: string | null;
  due_date?: string | null;
  status: string;
};

export default function ResolutionsIndex({
  stats,
  resolutions,
  organisations,
  meetings,
  users,
}: {
  stats: Record<string, number>;
  resolutions: ResolutionRow[];
  organisations: Option[];
  meetings: Option[];
  users: Option[];
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.resolutions.manage");
  const [open, setOpen] = useState(false);
  const [selectedResolution, setSelectedResolution] = useState<ResolutionRow | null>(null);

  const mappedResolutionData = selectedResolution
    ? {
        organisation_id: String(selectedResolution.organisation_id ?? ""),
        meeting_id: String(selectedResolution.meeting_id ?? ""),
        owner_id: selectedResolution.owner_id ? String(selectedResolution.owner_id) : "",
        resolution_number: selectedResolution.resolution_number ?? "",
        title: selectedResolution.title ?? "",
        description: selectedResolution.description ?? "",
        due_date: selectedResolution.due_date ?? "",
        status: selectedResolution.status ?? "open",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Resolutions" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Resolutions</h1>
            <p className="text-sm text-muted-foreground">
              Track resolution ownership, due dates, and overdue governance action.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {canManage ? (
              <CustomModelForm
                addButton={ResolutionModelFormConfig.addButton}
                title="Add Resolution"
                description={ResolutionModelFormConfig.description}
                fields={ResolutionModelFormConfig.fields}
                options={{ organisations, meetings, users }}
                submitRoute={() => ({ url: "/resolutions", method: "post" })}
              />
            ) : null}
            <DomainNav items={governanceNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Total Resolutions</CardTitle>
              <CardDescription>All tracked governance resolutions</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Open</CardTitle>
              <CardDescription>Still awaiting execution</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.open ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>In Progress</CardTitle>
              <CardDescription>Currently being worked</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.in_progress ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Overdue</CardTitle>
              <CardDescription>Past due action items</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.overdue ?? 0}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Resolution Register</CardTitle>
            <CardDescription>Keep ownership and due-date slippage visible for governance follow-through.</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={ResolutionTableConfig.columns}
              data={resolutions}
              actions={canManage ? [
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedResolution(row);
                    setOpen(true);
                  },
                },
              ] : []}
            />
          </CardContent>
        </Card>

        {selectedResolution ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Resolution"
            description={ResolutionModelFormConfig.description}
            fields={ResolutionModelFormConfig.fields}
            options={{ organisations, meetings, users }}
            mode="edit"
            initialData={mappedResolutionData}
            submitRoute={(routeParams) => ({ url: `/resolutions/${routeParams}`, method: "put" })}
            routeParams={selectedResolution.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
