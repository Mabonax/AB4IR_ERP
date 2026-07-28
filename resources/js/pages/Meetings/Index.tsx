import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";

import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { governanceNavItems } from "@/config/domain-nav/governance";
import { MeetingModelFormConfig } from "@/config/forms/meeting-model-form";
import { MeetingTableConfig } from "@/config/tables/meeting-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Meetings", href: "/meetings" },
];

type Option = { id: number; name: string };
type MeetingRow = {
  id: number;
  organisation_id: number;
  organisation_name: string | null;
  committee_id?: number | null;
  committee_name?: string | null;
  meeting_number: string;
  title: string;
  meeting_date: string | null;
  location?: string | null;
  agenda?: string | null;
  minutes?: string | null;
  attendance_count: number;
  resolution_count: number;
  status: string;
};

export default function MeetingsIndex({
  stats,
  meetings,
  organisations,
  committees,
}: {
  stats: Record<string, number>;
  meetings: MeetingRow[];
  organisations: Option[];
  committees: Option[];
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.meetings.manage");
  const [open, setOpen] = useState(false);
  const [selectedMeeting, setSelectedMeeting] = useState<MeetingRow | null>(null);

  const mappedMeetingData = selectedMeeting
    ? {
        organisation_id: String(selectedMeeting.organisation_id ?? ""),
        committee_id: selectedMeeting.committee_id ? String(selectedMeeting.committee_id) : "",
        meeting_number: selectedMeeting.meeting_number ?? "",
        title: selectedMeeting.title ?? "",
        meeting_date: selectedMeeting.meeting_date ?? "",
        location: selectedMeeting.location ?? "",
        agenda: selectedMeeting.agenda ?? "",
        minutes: selectedMeeting.minutes ?? "",
        status: selectedMeeting.status ?? "draft",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Meetings" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Meetings</h1>
            <p className="text-sm text-muted-foreground">
              Governance calendars, agendas, attendance pressure, and meeting execution status.
            </p>
          </div>
          <div className="flex items-center gap-3">
            {canManage ? (
              <CustomModelForm
                addButton={MeetingModelFormConfig.addButton}
                title="Add Meeting"
                description={MeetingModelFormConfig.description}
                fields={MeetingModelFormConfig.fields}
                options={{ organisations, committees }}
                submitRoute={() => ({ url: "/meetings", method: "post" })}
              />
            ) : null}
            <DomainNav items={governanceNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader>
              <CardTitle>Total Meetings</CardTitle>
              <CardDescription>All recorded meetings</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.total ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Scheduled</CardTitle>
              <CardDescription>Upcoming confirmed meetings</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.scheduled ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Completed</CardTitle>
              <CardDescription>Meetings with recorded closure</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.completed ?? 0}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Draft</CardTitle>
              <CardDescription>Still being prepared</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{stats.draft ?? 0}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Meeting Register</CardTitle>
            <CardDescription>Track governance meetings from preparation through completed minutes.</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={MeetingTableConfig.columns}
              data={meetings}
              actions={canManage ? [
                {
                  icon: "PencilIcon",
                  onClick: (row) => {
                    setSelectedMeeting(row);
                    setOpen(true);
                  },
                },
              ] : []}
            />
          </CardContent>
        </Card>

        {selectedMeeting ? (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Meeting"
            description={MeetingModelFormConfig.description}
            fields={MeetingModelFormConfig.fields}
            options={{ organisations, committees }}
            mode="edit"
            initialData={mappedMeetingData}
            submitRoute={(routeParams) => ({ url: `/meetings/${routeParams}`, method: "put" })}
            routeParams={selectedMeeting.id}
          />
        ) : null}
      </div>
    </AppLayout>
  );
}
