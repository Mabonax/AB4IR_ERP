import { Head, router, useForm } from "@inertiajs/react";
import { useMemo } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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

type SmmeOption = { id: number; name: string };
type ScoreInput = { section_id: number; score: number; comment: string | null };

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
  scores?: ScoreInput[];
  sections?: Section[];
};

export default function EditAdjudication({
  assessment,
  sections,
  smmes,
  can,
}: {
  assessment: Assessment | { data: Assessment };
  sections: Section[];
  smmes: SmmeOption[];
  can: { can_update: boolean; can_submit: boolean; can_unlock: boolean };
}) {
  const appData: Assessment =
    assessment && typeof assessment === "object" && "data" in assessment
      ? assessment.data
      : (assessment as Assessment);

  const isLocked = appData.status === "submitted" || !can.can_update;

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Adjudications", href: "/business-development/adjudications" },
    { title: `Assessment #${appData.id}`, href: `/business-development/adjudications/${appData.id}/edit` },
  ];

  const form = useForm({
    smme_id: appData.smme?.id ?? smmes[0]?.id ?? 0,
    platform_name: appData.platform_name ?? "",
    adjudication_date: appData.adjudication_date ?? "",
    development_stage: appData.development_stage,
    additional_notes: appData.additional_notes ?? "",
    scores: sections
      .slice()
      .sort((a, b) => a.sort_order - b.sort_order)
      .map((section) => {
        const existing = (appData.scores ?? []).find((score) => score.section_id === section.id);

        return {
          section_id: section.id,
          score: existing?.score ?? 0,
          comment: existing?.comment ?? "",
        };
      }),
  });

  const total = useMemo(
    () => form.data.scores.reduce((sum, item) => sum + (Number(item.score) || 0), 0),
    [form.data.scores]
  );

  const setScore = (sectionId: number, patch: Partial<{ score: number; comment: string }>) => {
    form.setData(
      "scores",
      form.data.scores.map((item) => (item.section_id === sectionId ? { ...item, ...patch } : item))
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Edit Assessment #${appData.id}`} />

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

        <form
          className="space-y-4"
          onSubmit={(e) => {
            e.preventDefault();
            form.put(`/business-development/adjudications/${appData.id}`);
          }}
        >
          <section className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium">Judge</label>
              <Input value={appData.judge?.name ?? "-"} readOnly />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">SMME</label>
              <select
                disabled={isLocked}
                value={form.data.smme_id}
                onChange={(e) => form.setData("smme_id", Number(e.currentTarget.value))}
                className="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:opacity-70"
              >
                {smmes.map((smme) => (
                  <option key={smme.id} value={smme.id}>
                    {smme.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Date</label>
              <Input
                type="date"
                disabled={isLocked}
                value={form.data.adjudication_date}
                onChange={(e) => form.setData("adjudication_date", e.currentTarget.value)}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Platform Name</label>
              <Input
                disabled={isLocked}
                value={form.data.platform_name}
                onChange={(e) => form.setData("platform_name", e.currentTarget.value)}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Development Stage</label>
              <select
                disabled={isLocked}
                value={form.data.development_stage}
                onChange={(e) =>
                  form.setData(
                    "development_stage",
                    e.currentTarget.value as "mvp" | "prototype" | "complete_product"
                  )
                }
                className="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:opacity-70"
              >
                <option value="mvp">MVP</option>
                <option value="prototype">Prototype</option>
                <option value="complete_product">Complete product</option>
              </select>
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
                {sections
                  .slice()
                  .sort((a, b) => a.sort_order - b.sort_order)
                  .map((section) => {
                    const row = form.data.scores.find((item) => item.section_id === section.id);

                    return (
                      <tr className="border-t align-top" key={section.id}>
                        <td className="px-3 py-2">
                          <div className="font-medium">{section.title}</div>
                          <p className="text-xs text-muted-foreground">{section.description}</p>
                        </td>
                        <td className="px-3 py-2">
                          <textarea
                            disabled={isLocked}
                            value={row?.comment ?? ""}
                            onChange={(e) => setScore(section.id, { comment: e.currentTarget.value })}
                            rows={3}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:opacity-70"
                          />
                        </td>
                        <td className="px-3 py-2">{section.max_points}</td>
                        <td className="px-3 py-2">
                          <Input
                            type="number"
                            min={0}
                            max={section.max_points}
                            disabled={isLocked}
                            value={row?.score ?? 0}
                            onChange={(e) => setScore(section.id, { score: Number(e.currentTarget.value) })}
                          />
                        </td>
                      </tr>
                    );
                  })}
                <tr className="border-t font-semibold">
                  <td className="px-3 py-2">Total</td>
                  <td className="px-3 py-2" />
                  <td className="px-3 py-2">50</td>
                  <td className="px-3 py-2">{total}</td>
                </tr>
              </tbody>
            </table>
          </section>

          <section className="rounded-xl border bg-card p-4">
            <label className="mb-1 block text-sm font-medium">Additional Comments / Notes</label>
            <textarea
              disabled={isLocked}
              value={form.data.additional_notes}
              onChange={(e) => form.setData("additional_notes", e.currentTarget.value)}
              rows={4}
              className="w-full rounded-md border bg-background px-3 py-2 text-sm disabled:opacity-70"
            />
          </section>

          <div className="flex flex-wrap items-center gap-2">
            <Button type="button" variant="outline" onClick={() => router.visit(`/business-development/adjudications/${appData.id}`)}>
              View
            </Button>
            {!isLocked ? (
              <Button type="submit" disabled={form.processing}>
                Save Draft
              </Button>
            ) : null}
            {!isLocked && can.can_submit ? (
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
            {appData.status === "submitted" ? (
              <span className="text-sm text-muted-foreground">Locked after submit</span>
            ) : null}
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
