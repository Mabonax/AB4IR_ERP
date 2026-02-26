import { Head, useForm, usePage } from "@inertiajs/react";
import { useMemo } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

type Section = {
  id: number;
  title: string;
  description: string;
  max_points: number;
  sort_order: number;
};

type SmmeOption = { id: number; name: string };

type ScoreInput = { section_id: number; score: number; comment: string };

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Adjudications", href: "/business-development/adjudications" },
  { title: "Create", href: "/business-development/adjudications/create" },
];

export default function CreateAdjudication({
  sections,
  smmes,
}: {
  sections: Section[];
  smmes: SmmeOption[];
}) {
  const { props } = usePage<SharedData>();
  const judgeName = props.auth?.user?.name ?? "Current user";

  const form = useForm({
    smme_id: smmes[0]?.id ?? 0,
    platform_name: "",
    adjudication_date: new Date().toISOString().slice(0, 10),
    development_stage: "mvp" as "mvp" | "prototype" | "complete_product",
    additional_notes: "",
    scores: sections
      .sort((a, b) => a.sort_order - b.sort_order)
      .map((section) => ({
        section_id: section.id,
        score: 0,
        comment: "",
      })) as ScoreInput[],
  });

  const total = useMemo(
    () => form.data.scores.reduce((sum, item) => sum + (Number(item.score) || 0), 0),
    [form.data.scores]
  );

  const setScore = (sectionId: number, patch: Partial<ScoreInput>) => {
    form.setData(
      "scores",
      form.data.scores.map((item) => (item.section_id === sectionId ? { ...item, ...patch } : item))
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Create Adjudication Assessment" />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Create Adjudication Assessment</h1>
          <DomainNav items={businessDevelopmentNavItems} />
        </div>

        <form
          className="space-y-4"
          onSubmit={(e) => {
            e.preventDefault();
            form.post("/business-development/adjudications");
          }}
        >
          <section className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium">Judge</label>
              <Input value={judgeName} readOnly />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">SMME</label>
              <select
                value={form.data.smme_id}
                onChange={(e) => form.setData("smme_id", Number(e.currentTarget.value))}
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
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
                value={form.data.adjudication_date}
                onChange={(e) => form.setData("adjudication_date", e.currentTarget.value)}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Platform Name</label>
              <Input
                value={form.data.platform_name}
                onChange={(e) => form.setData("platform_name", e.currentTarget.value)}
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Development Stage</label>
              <select
                value={form.data.development_stage}
                onChange={(e) =>
                  form.setData(
                    "development_stage",
                    e.currentTarget.value as "mvp" | "prototype" | "complete_product"
                  )
                }
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
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
                            value={row?.comment ?? ""}
                            onChange={(e) => setScore(section.id, { comment: e.currentTarget.value })}
                            rows={3}
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                          />
                        </td>
                        <td className="px-3 py-2">{section.max_points}</td>
                        <td className="px-3 py-2">
                          <Input
                            type="number"
                            min={0}
                            max={section.max_points}
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
              value={form.data.additional_notes}
              onChange={(e) => form.setData("additional_notes", e.currentTarget.value)}
              rows={4}
              className="w-full rounded-md border bg-background px-3 py-2 text-sm"
            />
          </section>

          <div className="flex items-center gap-2">
            <Button type="submit" disabled={form.processing}>
              Save Draft
            </Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
