import { Head, router } from "@inertiajs/react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type AssessmentRow = {
  id: number;
  platform_name: string;
  adjudication_date: string;
  development_stage: "mvp" | "prototype" | "complete_product";
  status: "draft" | "submitted";
  total_score: number;
  judge: { id: number; name: string };
  smme: { id: number; name: string };
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Adjudications", href: "/business-development/adjudications" },
];

const stageLabel: Record<AssessmentRow["development_stage"], string> = {
  mvp: "MVP",
  prototype: "Prototype",
  complete_product: "Complete product",
};

export default function AdjudicationsIndex({
  assessments,
}: {
  assessments: { data: AssessmentRow[] };
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Adjudication Assessments" />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Adjudication Assessments</h1>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <Button onClick={() => router.visit("/business-development/adjudications/create")}>New Assessment</Button>
          </div>
        </div>

        <CustomTable
          data={assessments.data}
          columns={[
            { key: "smme", label: "SMME", className: "px-3 py-2", render: (row: AssessmentRow) => row.smme?.name ?? "-" },
            { key: "judge", label: "Judge", className: "px-3 py-2", render: (row: AssessmentRow) => row.judge?.name ?? "-" },
            { key: "platform_name", label: "Platform", className: "px-3 py-2" },
            { key: "development_stage", label: "Stage", className: "px-3 py-2", render: (row: AssessmentRow) => stageLabel[row.development_stage] },
            { key: "total_score", label: "Total", className: "px-3 py-2", render: (row: AssessmentRow) => `${row.total_score}/50` },
            {
              key: "status",
              label: "Status",
              className: "px-3 py-2",
              render: (row: AssessmentRow) => (
                <Badge variant={row.status === "submitted" ? "secondary" : "outline"}>
                  {row.status === "submitted" ? "Submitted" : "Draft"}
                </Badge>
              ),
            },
            { key: "actions", label: "Actions", className: "px-3 py-2", isAction: true },
          ]}
          actions={[
            {
              icon: "Eye",
              onClick: (row: AssessmentRow) => router.visit(`/business-development/adjudications/${row.id}`),
            },
            {
              icon: "PencilIcon",
              onClick: (row: AssessmentRow) => router.visit(`/business-development/adjudications/${row.id}/edit`),
            },
            {
              icon: "Send",
              onClick: (row: AssessmentRow) => {
                if (row.status === "submitted") return;
                router.visit(`/business-development/adjudications/${row.id}`);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row: AssessmentRow) => router.delete(`/business-development/adjudications/${row.id}`),
            },
          ]}
        />
      </div>
    </AppLayout>
  );
}
