import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { governanceNavItems } from "@/config/domain-nav/governance";
import { CommitteeModelFormConfig } from "@/config/forms/committee-model-form";
import { CommitteeTableConfig } from "@/config/tables/committee-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Committees", href: "/committees" },
];

type Option = { id: number; name: string };
type CommitteeRow = {
  id: number;
  organisation_id: number;
  organisation_name: string | null;
  name: string;
  description?: string | null;
  chairperson_id?: number | null;
  chairperson_name?: string | null;
  secretary_id?: number | null;
  secretary_name?: string | null;
  members_count: number;
  meetings_count: number;
  status: string;
};

export default function CommitteesIndex({
  stats,
  committees,
  organisations,
  users,
}: {
  stats: Record<string, number>;
  committees: CommitteeRow[];
  organisations: Option[];
  users: Option[];
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.committees.manage");
  const [open, setOpen] = useState(false);
  const [selectedCommittee, setSelectedCommittee] = useState<CommitteeRow | null>(null);

  const mappedCommitteeData = selectedCommittee
    ? {
        organisation_id: String(selectedCommittee.organisation_id ?? ""),
        name: selectedCommittee.name ?? "",
        description: selectedCommittee.description ?? "",
        chairperson_id: selectedCommittee.chairperson_id ? String(selectedCommittee.chairperson_id) : "",
        secretary_id: selectedCommittee.secretary_id ? String(selectedCommittee.secretary_id) : "",
        status: selectedCommittee.status ?? "active",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Committees" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Committees</h1>
            <p className="text-sm text-muted-foreground">
              Committee leadership, membership footprint, and scheduled governance activity.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {canManage ? (
              <CustomModelForm
                addButton={CommitteeModelFormConfig.addButton}
                title="Add Committee"
                description={CommitteeModelFormConfig.description}
                fields={CommitteeModelFormConfig.fields}
                options={{ organisations, users }}
                submitRoute={() => ({ url: "/committees", method: "post" })}
              />
            ) : null}
            <DomainNav items={governanceNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Total Committees</CardTitle>
              <CardDescription>Current committee register</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Active Committees</CardTitle>
              <CardDescription>Operational structures</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.active ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Total Members</CardTitle>
              <CardDescription>Membership assignments</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total_members ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Scheduled Meetings</CardTitle>
              <CardDescription>Committee calendar load</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.scheduled_meetings ?? 0}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Committee Register</CardTitle>
            <CardDescription>Formal committees with leadership and current meeting pressure.</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={CommitteeTableConfig.columns}
              data={committees}
              actions={canManage ? [
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedCommittee(row);
                    setOpen(true);
                  },
                },
              ] : []}
            />
          </CardContent>
        </Card>

        {selectedCommittee ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Committee"
            description={CommitteeModelFormConfig.description}
            fields={CommitteeModelFormConfig.fields}
            options={{ organisations, users }}
            mode="edit"
            initialData={mappedCommitteeData}
            submitRoute={(routeParams) => ({ url: `/committees/${routeParams}`, method: "put" })}
            routeParams={selectedCommittee.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
