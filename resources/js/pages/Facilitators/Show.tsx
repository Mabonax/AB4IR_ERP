import { Head, Link, router, usePage } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

export default function FacilitatorShow({
  facilitator,
  canManageFacilitators,
  learningSummary,
}: {
  facilitator: any;
  canManageFacilitators: boolean;
  learningSummary: any;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Facilitators", href: facilitators.index() },
    { title: facilitator.full_name ?? "Facilitator", href: facilitators.show(facilitator.id) },
  ];
  const flashActivationUrl = (usePage<any>().props?.flash?.activation_url as string | null) ?? null;

  const resendLmsInvitation = () => {
    router.post(`/facilitators/${facilitator.id}/lms-invitation/resend`, {}, { preserveScroll: true });
  };

  const provisionLmsAccess = () => {
    router.post(`/facilitators/${facilitator.id}/lms-access/provision`, {}, { preserveScroll: true });
  };

  const copyActivationUrl = () => {
    if (flashActivationUrl) {
      navigator.clipboard.writeText(flashActivationUrl);
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={facilitator.full_name ?? "Facilitator"} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Facilitator file</div>
            <h1 className="text-2xl font-semibold tracking-tight">{facilitator.full_name ?? "-"}</h1>
            <p className="mt-1 text-sm text-muted-foreground">Linked facilitator profile and account details.</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link href={facilitators.index().url}>
              <Button variant="outline">Back to Facilitators</Button>
            </Link>
            {canManageFacilitators ? (
              <Link href={facilitators.edit(facilitator.id).url}>
                <Button className="bg-red-600 text-white hover:bg-red-700">Edit Facilitator</Button>
              </Link>
            ) : null}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Core profile</CardTitle>
              <CardDescription>Current operational details used across facilitator workflows.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">First Name</div><div className="mt-1 font-medium">{facilitator.name ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Surname</div><div className="mt-1 font-medium">{facilitator.surname ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Email</div><div className="mt-1 font-medium">{facilitator.email ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Cell</div><div className="mt-1 font-medium">{facilitator.cell ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Province</div><div className="mt-1 font-medium">{facilitator.province_name ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Specialization</div><div className="mt-1 font-medium">{facilitator.specialization ?? "-"}</div></div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <CardTitle>LMS Teaching</CardTitle>
                  <CardDescription>Read-only LMS delivery activity for this ERP facilitator.</CardDescription>
                </div>
                <div className="flex flex-wrap gap-2">
                {flashActivationUrl ? (
                  <button
                    type="button"
                    onClick={copyActivationUrl}
                    className="rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                  >
                    Copy Activation Link
                  </button>
                ) : null}
                {canManageFacilitators && String(learningSummary?.access_state ?? learningSummary?.lms_access) === "not_provisioned" ? (
                  <button
                    type="button"
                    onClick={provisionLmsAccess}
                    className="rounded-md border border-indigo-200 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                  >
                    Provision LMS Access
                  </button>
                ) : null}
                {canManageFacilitators && ["invitation_pending", "invitation_expired"].includes(String(learningSummary?.access_state ?? learningSummary?.lms_access)) ? (
                  <button
                    type="button"
                    onClick={resendLmsInvitation}
                    className="rounded-md border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50"
                  >
                    Resend Invitation
                  </button>
                ) : null}
                {learningSummary?.deep_link ? (
                  <a
                    href={learningSummary.deep_link}
                    target="_blank"
                    rel="noreferrer"
                    className="rounded-md border border-sky-200 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50"
                  >
                    Open Facilitator in LMS
                  </a>
                ) : null}
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-3 text-sm sm:grid-cols-2">
                {[
                  ["LMS Access", String(learningSummary?.access_state ?? learningSummary?.lms_access ?? "No LMS data").replaceAll("_", " ")],
                  ["Invitation", learningSummary?.invitation?.expires_at ? `${learningSummary?.invitation_status ?? "-"} until ${learningSummary.invitation.expires_at}` : learningSummary?.invitation_status ?? "No invitation"],
                  ["Activated", learningSummary?.activated_at ?? "Not activated"],
                  ["Last Login", learningSummary?.last_login_at ?? "Never"],
                  ["Active Cohorts", learningSummary?.assigned_offerings ?? "No data"],
                  ["Active Learners", learningSummary?.active_learners ?? "No data"],
                  ["Assessments Awaiting Review", learningSummary?.assessments_awaiting_review ?? "No data"],
                ].map(([label, value]) => (
                  <div key={label} className="rounded-md border bg-slate-50 p-3">
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                    <div className="mt-1 font-semibold text-slate-900">{value}</div>
                  </div>
                ))}
              </div>

              {(learningSummary?.offerings ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  {learningSummary?.lms_access === "not_provisioned"
                    ? "This facilitator does not currently have LMS access."
                    : "No current LMS teaching assignments are available."}
                </p>
              ) : (
                <div className="space-y-2 text-sm">
                  {learningSummary.offerings.map((offering: any) => (
                    <div key={offering.id} className="rounded-md border p-3">
                      <div className="font-medium">{offering.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {offering.programme?.name ?? "No programme"} | {offering.status}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Extended details</CardTitle>
              <CardDescription>Optional identity and address details when they have been captured.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Date of Birth</div><div className="mt-1 font-medium">{facilitator.dob ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">ID Number</div><div className="mt-1 font-medium">{facilitator.id_number ?? "-"}</div></div>
              <div className="sm:col-span-2"><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Address</div><div className="mt-1 whitespace-pre-wrap font-medium">{facilitator.address ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Linked User ID</div><div className="mt-1 font-medium">{facilitator.user_id ?? "-"}</div></div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
