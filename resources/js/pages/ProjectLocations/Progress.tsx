import { Head, Link, router } from "@inertiajs/react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { projectNavItems } from "@/config/domain-nav/projects";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "Locations", href: "/project-locations" },
  { title: "Progress", href: "#" },
];

export default function ProjectLocationProgress({
  location,
  milestones,
  milestoneOptions,
  beneficiaries,
  totalMilestones,
}: {
  location: {
    id?: number;
    project_name: string | null;
    province: string | null;
    facilitator_name: string | null;
  };
  milestones: Array<{ id: number; title: string; total: number; assessed: number; passed: number }>;
  milestoneOptions: Array<{ id: number; title: string; max_score: number | null }>;
  beneficiaries: Array<{
    id: number;
    name: string;
    assessed_milestones: number;
    assessments: Record<
      number,
      { status: string; score: number; comments: string | null; assessed_at: string | null }
    >;
  }>;
  totalMilestones: number;
}) {
  const [open, setOpen] = useState(false);
  const [beneficiaryId, setBeneficiaryId] = useState<number | null>(null);
  const [milestoneId, setMilestoneId] = useState<number | null>(null);
  const [score, setScore] = useState<string>("");
  const [comments, setComments] = useState<string>("");

  const selectedBeneficiary = useMemo(
    () => beneficiaries.find((b) => b.id === beneficiaryId) ?? null,
    [beneficiaries, beneficiaryId]
  );

  const selectedMilestone = useMemo(
    () => milestoneOptions.find((m) => m.id === milestoneId) ?? null,
    [milestoneOptions, milestoneId]
  );

  const selectedAssessment =
    selectedBeneficiary && milestoneId
      ? selectedBeneficiary.assessments?.[milestoneId] ?? null
      : null;

  const openAssessmentModal = (beneficiary: any, milestone: any) => {
    const assessment = beneficiary.assessments?.[milestone.id] ?? null;
    setBeneficiaryId(beneficiary.id);
    setMilestoneId(milestone.id);
    setScore(
      assessment?.score !== undefined && assessment?.score !== null
        ? String(assessment.score)
        : ""
    );
    setComments(assessment?.comments ?? "");
    setOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!location.id || !beneficiaryId || !milestoneId || !score) return;

    router.post(`/project-locations/${location.id}/assessments`, {
      beneficiary_id: beneficiaryId,
      project_milestone_id: milestoneId,
      score: Number(score),
      comments,
    }, {
      onSuccess: () => {
        setOpen(false);
        setScore("");
        setComments("");
      },
    });
  };
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Location Progress" />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Location Progress</h1>
          <DomainNav items={projectNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Project</CardTitle>
              <CardDescription>Current</CardDescription>
            </CardHeader>
            <CardContent className="text-lg font-semibold">
              {location.project_name ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Location</CardTitle>
              <CardDescription>Province</CardDescription>
            </CardHeader>
            <CardContent className="text-lg font-semibold">
              {location.province ?? "-"}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Facilitator</CardTitle>
              <CardDescription>Assigned</CardDescription>
            </CardHeader>
            <CardContent className="text-lg font-semibold">
              {location.facilitator_name ?? "-"}
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Milestone Progress</CardTitle>
              <CardDescription>Assessed per milestone</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {milestones.length === 0 ? (
                <p className="text-muted-foreground">No milestones.</p>
              ) : (
                milestones.map((m) => (
                  <div key={m.id} className="flex justify-between">
                    <span>{m.title}</span>
                    <span>
                      {m.assessed}/{m.total}
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Beneficiaries</CardTitle>
              <CardDescription>Assessed milestones</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {beneficiaries.length === 0 ? (
                <p className="text-muted-foreground">No beneficiaries.</p>
              ) : (
                beneficiaries.map((b) => (
                  <div key={b.id} className="flex justify-between">
                    <Link href={`/beneficiaries/${b.id}`} className="text-red-600 hover:underline">
                      {b.name}
                    </Link>
                    <span>
                      {b.assessed_milestones}/{totalMilestones}
                    </span>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Assessments</CardTitle>
            <CardDescription>
              Click a cell to assess a beneficiary for a milestone. Scores may only be created or corrected while the project is active.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="min-w-full border text-sm">
                <thead className="bg-gradient-to-r from-red-600 to-orange-500 text-white">
                  <tr>
                    <th className="px-4 py-2 text-left">Beneficiary</th>
                    {milestoneOptions.map((m) => (
                      <th key={m.id} className="px-4 py-2 text-left">
                        <div className="text-sm font-semibold">{m.title}</div>
                        <div className="text-xs opacity-90">
                          Max: {m.max_score ?? "-"}
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {beneficiaries.map((b) => (
                    <tr key={b.id} className="border-t">
                      <td className="px-4 py-2 font-medium">
                        <Link href={`/beneficiaries/${b.id}`} className="text-red-600 hover:underline">
                          {b.name}
                        </Link>
                      </td>
                      {milestoneOptions.map((m) => {
                        const assessment = b.assessments?.[m.id];
                        const statusLabel = assessment
                          ? assessment.status === "completed"
                            ? "Passed"
                            : "Failed"
                          : "Not Assessed";
                        const statusClass = assessment
                          ? assessment.status === "completed"
                            ? "bg-green-100 text-green-700"
                            : "bg-red-100 text-red-700"
                          : "bg-gray-100 text-gray-600";

                        return (
                          <td key={m.id} className="px-4 py-2">
                            <button
                              type="button"
                              onClick={() => openAssessmentModal(b, m)}
                              className="w-full rounded-md border px-2 py-1 text-left hover:border-red-400"
                            >
                              <div className="text-xs">
                                {assessment?.score !== undefined && assessment?.score !== null
                                  ? `Score: ${assessment.score}`
                                  : "Score: -"}
                              </div>
                              <div className={`mt-1 inline-block rounded px-2 py-0.5 text-xs ${statusClass}`}>
                                {statusLabel}
                              </div>
                            </button>
                          </td>
                        );
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="sm:max-w-[520px]">
          <DialogHeader>
            <DialogTitle>Assess Beneficiary</DialogTitle>
            <DialogDescription>
              {selectedBeneficiary?.name ?? "-"} • {selectedMilestone?.title ?? "-"}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={handleSubmit} className="grid gap-3">
            <div className="text-sm text-muted-foreground">
              Max score: {selectedMilestone?.max_score ?? "-"}
            </div>

            <input
              type="number"
              min={0}
              value={score}
              onChange={(e) => setScore(e.target.value)}
              placeholder="Score"
              className="rounded-md border px-3 py-2 text-sm"
            />

            <input
              type="text"
              value={comments}
              onChange={(e) => setComments(e.target.value)}
              placeholder="Comments (optional)"
              className="rounded-md border px-3 py-2 text-sm"
            />

            <button
              type="submit"
              className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
            >
              Save Assessment
            </button>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
