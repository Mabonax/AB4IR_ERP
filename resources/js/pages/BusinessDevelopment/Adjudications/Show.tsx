import { Head, router } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type Section = {
  id: number;
  title: string;
  description: string;
  max_points: number;
  sort_order: number;
};

type Score = { section_id: number; score: number; comment: string | null };

type Assessment = {
  id: number;
  platform_name: string;
  adjudication_date: string;
  development_stage: "mvp" | "prototype" | "complete_product";
  status: "draft" | "submitted";
  total_score: number;
  additional_notes: string | null;
  judge?: { id: number | null; name: string | null } | null;
  smme?: { id: number | null; name: string | null } | null;
  sections?: Section[];
  scores?: Score[];
};

const stageLabel: Record<Assessment["development_stage"], string> = {
  mvp: "MVP",
  prototype: "Prototype",
  complete_product: "Complete product",
};

export default function ShowAdjudication({
  assessment,
  can,
}: {
  assessment: Assessment | { data: Assessment };
  can: { can_update: boolean; can_submit: boolean; can_unlock: boolean; can_delete: boolean };
}) {
  const appData: Assessment =
    assessment && typeof assessment === "object" && "data" in assessment
      ? assessment.data
      : (assessment as Assessment);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Adjudications", href: "/business-development/adjudications" },
    { title: `Assessment #${appData.id}`, href: `/business-development/adjudications/${appData.id}` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Assessment #${appData.id}`} />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-semibold">Assessment #{appData.id}</h1>
            <Badge variant={appData.status === "submitted" ? "secondary" : "outline"}>
              {appData.status === "submitted" ? "Submitted" : "Draft"}
            </Badge>
          </div>
          <DomainNav items={businessDevelopmentNavItems} />
        </div>

        <section className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-2">
          <div>
            <p className="text-xs text-muted-foreground">Judge</p>
            <p className="text-sm font-medium">{appData.judge?.name ?? "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">SMME</p>
            <p className="text-sm font-medium">{appData.smme?.name ?? "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Date</p>
            <p className="text-sm font-medium">{appData.adjudication_date ?? "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Platform Name</p>
            <p className="text-sm font-medium">{appData.platform_name ?? "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Development Stage</p>
            <p className="text-sm font-medium">{stageLabel[appData.development_stage] ?? "-"}</p>
          </div>
        </section>

        <section className="overflow-x-auto rounded-xl border bg-card">
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left">Business Element</th>
                <th className="px-3 py-2 text-left">Comment</th>
                <th className="px-3 py-2 text-left">Max Points</th>
                <th className="px-3 py-2 text-left">Score</th>
              </tr>
            </thead>
            <tbody>
              {(appData.sections ?? [])
                .slice()
                .sort((a, b) => a.sort_order - b.sort_order)
                .map((section) => {
                  const score = (appData.scores ?? []).find((item) => item.section_id === section.id);

                  return (
                    <tr className="border-t align-top" key={section.id}>
                      <td className="px-3 py-2">
                        <div className="font-medium">{section.title}</div>
                        <p className="text-xs text-muted-foreground">{section.description}</p>
                      </td>
                      <td className="px-3 py-2">{score?.comment || "-"}</td>
                      <td className="px-3 py-2">{section.max_points}</td>
                      <td className="px-3 py-2">{score?.score ?? 0}</td>
                    </tr>
                  );
                })}
              <tr className="border-t font-semibold">
                <td className="px-3 py-2">Total</td>
                <td className="px-3 py-2" />
                <td className="px-3 py-2">50</td>
                <td className="px-3 py-2">{appData.total_score ?? 0}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section className="rounded-xl border bg-card p-4">
          <p className="text-xs text-muted-foreground">Additional Comments / Notes</p>
          <p className="mt-1 text-sm">{appData.additional_notes || "-"}</p>
        </section>

        <div className="flex flex-wrap items-center gap-2">
          {can.can_update ? (
            <Button onClick={() => router.visit(`/business-development/adjudications/${appData.id}/edit`)}>
              Edit
            </Button>
          ) : null}
          {appData.status === "draft" && can.can_submit ? (
            <>
              <Button
                type="button"
                onClick={() =>
                  router.post(
                    `/business-development/adjudications/${appData.id}/submit`,
                    { result: "incubated" },
                    { preserveScroll: true }
                  )
                }
              >
                Submit as Incubated
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() =>
                  router.post(
                    `/business-development/adjudications/${appData.id}/submit`,
                    { result: "rejected" },
                    { preserveScroll: true }
                  )
                }
              >
                Submit as Rejected
              </Button>
            </>
          ) : null}
          {appData.status === "submitted" && can.can_unlock ? (
            <Button
              type="button"
              variant="secondary"
              onClick={() =>
                router.post(`/business-development/adjudications/${appData.id}/unlock`, {}, { preserveScroll: true })
              }
            >
              Unlock
            </Button>
          ) : null}
          {can.can_delete ? (
            <Button
              type="button"
              variant="destructive"
              onClick={() => router.delete(`/business-development/adjudications/${appData.id}`)}
            >
              Delete
            </Button>
          ) : null}
        </div>
      </div>
    </AppLayout>
  );
}
