import { Head, usePage } from "@inertiajs/react";

import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { financeNavItems } from "@/config/domain-nav/finance";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "/finance/travel-claims" },
  { title: "Travel Claims", href: "/finance/travel-claims" },
];

export default function TravelClaimsIndex({
  claims,
  isFinanceUser,
}: {
  claims: any[];
  isFinanceUser: boolean;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;

  const rows = claims.map((claim) => ({
    id: claim.id,
    claim_number: claim.claim_number,
    claim_month: claim.claim_month,
    claimant_name: claim.claimant?.name ?? "-",
    department_name: claim.claimant?.department_name ?? "-",
    status_label: claim.status_label,
    approval_status_label: claim.approval_status_label,
    amount: `R${Number(claim.totals?.amount ?? 0).toFixed(2)}`,
    submitted_at: claim.submitted_at ?? "-",
  }));

  const columns = [
    { label: "Claim #", key: "claim_number", className: "px-4 py-2 text-left" },
    { label: "Month", key: "claim_month", className: "px-4 py-2 text-left" },
    { label: "Claimant", key: "claimant_name", className: "px-4 py-2 text-left" },
    { label: "Department", key: "department_name", className: "px-4 py-2 text-left" },
    { label: "Approval", key: "approval_status_label", className: "px-4 py-2 text-left" },
    { label: "Status", key: "status_label", className: "px-4 py-2 text-left" },
    { label: "Amount", key: "amount", className: "px-4 py-2 text-left" },
    { label: "Submitted", key: "submitted_at", className: "px-4 py-2 text-left" },
    { label: "Actions", key: "actions", isAction: true, className: "px-4 py-2 text-left" },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Travel Claims" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Travel Claims</h1>
            <p className="text-sm text-muted-foreground">
              Manager-submitted private vehicle claims routed through approval and then finance.
            </p>
          </div>
          <DomainNav items={financeNavItems} />
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Total Claims</div>
            <div className="mt-1 text-2xl font-semibold">{claims.length}</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Pending Approval</div>
            <div className="mt-1 text-2xl font-semibold">{claims.filter((claim) => claim.approval_status === "pending").length}</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Approved</div>
            <div className="mt-1 text-2xl font-semibold">{claims.filter((claim) => claim.approval_status === "approved").length}</div>
          </div>
          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Paid</div>
            <div className="mt-1 text-2xl font-semibold">{claims.filter((claim) => claim.status === "paid").length}</div>
          </div>
        </div>

        <div className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="mb-3 flex items-center justify-between gap-3">
            <div>
              <h2 className="font-semibold">Claim Register</h2>
              <p className="text-sm text-muted-foreground">
                {isFinanceUser ? "All finance-visible claims" : "Claims you submitted or can review for your team"}
              </p>
            </div>
          </div>

          <CustomTable
            columns={columns}
            data={rows}
            actions={[
              {
                icon: "Eye",
                label: "View claim",
                href: (row) => `/finance/travel-claims/${row.id}`,
              },
            ]}
          />
        </div>
      </div>
    </AppLayout>
  );
}
