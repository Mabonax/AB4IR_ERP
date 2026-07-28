import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { financeNavItems } from "@/config/domain-nav/finance";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "/finance/travel-claims" },
  { title: "Travel Claims", href: "/finance/travel-claims" },
];

export default function TravelClaimShow({
  claim,
}: {
  claim: any;
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const [financeComment, setFinanceComment] = useState(claim.finance_comment ?? "");
  const [approvalComment, setApprovalComment] = useState(claim.approval_comment ?? "");

  const submitAction = (action: "receive" | "pay" | "reject") => {
    router.post(`/finance/travel-claims/${claim.id}/${action}`, {
      finance_comment: financeComment,
    });
  };

  const submitApprovalAction = (action: "approve" | "approval-reject") => {
    router.post(`/finance/travel-claims/${claim.id}/${action}`, {
      approval_comment: approvalComment,
    });
  };

  return (
    <AppLayout
      breadcrumbs={[
        ...breadcrumbs,
        { title: claim.claim_number, href: `/finance/travel-claims/${claim.id}` },
      ]}
    >
      <Head title={claim.claim_number} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{claim.claim_number}</h1>
            <p className="text-sm text-muted-foreground">
              Transport claim for private vehicle usage.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <DomainNav items={financeNavItems} />
            <a
              href={`/finance/travel-claims/${claim.id}/pdf`}
              className="inline-flex items-center rounded-md border border-red-500 px-3 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white"
            >
              Download PDF
            </a>
          </div>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        <div className="grid gap-4 lg:grid-cols-3">
          <div className="rounded-xl border bg-card p-4 shadow-sm lg:col-span-2">
            <h2 className="font-semibold">Claim Form</h2>
            <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3 text-sm">
              <div>
                <div className="text-muted-foreground">Claimant</div>
                <div className="font-medium">{claim.claimant?.name ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Department</div>
                <div className="font-medium">{claim.claimant?.department_name ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Month</div>
                <div className="font-medium">{claim.claim_month ?? "-"}</div>
              </div>
              <div className="md:col-span-2 xl:col-span-3">
                <div className="text-muted-foreground">Address</div>
                <div className="font-medium">{claim.claimant?.address ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Make and Model</div>
                <div className="font-medium">{claim.vehicle?.make_model ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Vehicle Type</div>
                <div className="font-medium">{claim.vehicle?.type ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Year</div>
                <div className="font-medium">{claim.vehicle?.year ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Engine Volume</div>
                <div className="font-medium">{claim.vehicle?.engine_volume ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Tariff / KM</div>
                <div className="font-medium">R{Number(claim.vehicle?.tariff_per_km ?? 0).toFixed(2)}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Home Distance</div>
                <div className="font-medium">{claim.vehicle?.home_distance_km ?? 0} KM</div>
              </div>
              <div>
                <div className="text-muted-foreground">Checked By</div>
                <div className="font-medium">{claim.checked_by ?? "-"}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Status</div>
                <div className="font-medium">{claim.status_label}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Approval Status</div>
                <div className="font-medium">{claim.approval_status_label}</div>
              </div>
              <div>
                <div className="text-muted-foreground">Approver</div>
                <div className="font-medium">{claim.approver ?? "-"}</div>
              </div>
            </div>

            <div className="mt-5 overflow-x-auto rounded-lg border">
              <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gradient-to-r from-red-600 to-red-500 text-white">
                  <tr>
                    {["Date", "From", "To", "Starting", "Ending", "Nature of Duty", "Actual KM", "Claimable KM", "Total"].map((label) => (
                      <th key={label} className="px-3 py-2 text-left font-semibold">
                        {label}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {claim.trips.map((trip: any) => (
                    <tr key={trip.id}>
                      <td className="px-3 py-2">{trip.travel_date}</td>
                      <td className="px-3 py-2">{trip.route_from}</td>
                      <td className="px-3 py-2">{trip.route_to}</td>
                      <td className="px-3 py-2">{trip.start_time}</td>
                      <td className="px-3 py-2">{trip.end_time}</td>
                      <td className="px-3 py-2">{trip.nature_of_duty}</td>
                      <td className="px-3 py-2">{trip.actual_distance_km}</td>
                      <td className="px-3 py-2">{trip.claimable_distance_km}</td>
                      <td className="px-3 py-2">R{Number(trip.line_total).toFixed(2)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div className="space-y-4">
            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="font-semibold">Totals</h2>
              <div className="mt-3 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Actual Distance</span>
                  <span>{claim.totals?.actual_distance_km ?? 0} KM</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Claimable Distance</span>
                  <span>{claim.totals?.claimable_distance_km ?? 0} KM</span>
                </div>
                <div className="flex justify-between text-base font-semibold">
                  <span>Total Amount</span>
                  <span>R{Number(claim.totals?.amount ?? 0).toFixed(2)}</span>
                </div>
              </div>
            </div>

            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="font-semibold">Approval Workflow</h2>
              <div className="mt-3 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Approval Status</span>
                  <span>{claim.approval_status_label}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Approver</span>
                  <span>{claim.approver ?? "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Decision Time</span>
                  <span>{claim.approval_decided_at ?? "-"}</span>
                </div>
                <div>
                  <div className="text-muted-foreground">Approval Comment</div>
                  <div className="mt-1 rounded-md border bg-muted/30 px-3 py-2">
                    {claim.approval_comment ?? "-"}
                  </div>
                </div>
              </div>

              {claim.permissions?.can_approve || claim.permissions?.can_reject_approval ? (
                <div className="mt-4 space-y-3">
                  <textarea
                    rows={3}
                    value={approvalComment}
                    onChange={(e) => setApprovalComment(e.currentTarget.value)}
                    placeholder="Approval comment"
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                  <div className="flex flex-wrap gap-2">
                    {claim.permissions?.can_approve ? (
                      <button
                        type="button"
                        onClick={() => submitApprovalAction("approve")}
                        className="rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700"
                      >
                        Approve Claim
                      </button>
                    ) : null}
                    {claim.permissions?.can_reject_approval ? (
                      <button
                        type="button"
                        onClick={() => submitApprovalAction("approval-reject")}
                        className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                      >
                        Reject Claim
                      </button>
                    ) : null}
                  </div>
                </div>
              ) : null}
            </div>

            <div className="rounded-xl border bg-card p-4 shadow-sm">
              <h2 className="font-semibold">Finance Workflow</h2>
              <div className="mt-3 space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Submitted</span>
                  <span>{claim.submitted_at ?? "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Received</span>
                  <span>{claim.finance_received_at ?? "-"}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Paid</span>
                  <span>{claim.finance_paid_at ?? "-"}</span>
                </div>
              </div>

              {claim.permissions?.can_receive || claim.permissions?.can_pay || claim.permissions?.can_reject ? (
                <div className="mt-4 space-y-3">
                  <textarea
                    rows={3}
                    value={financeComment}
                    onChange={(e) => setFinanceComment(e.currentTarget.value)}
                    placeholder="Finance comment"
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                  <div className="flex flex-wrap gap-2">
                    {claim.permissions?.can_receive ? (
                      <button
                        type="button"
                        onClick={() => submitAction("receive")}
                        className="rounded-md border border-red-500 px-3 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white"
                      >
                        Mark Received
                      </button>
                    ) : null}
                    {claim.permissions?.can_pay ? (
                      <button
                        type="button"
                        onClick={() => submitAction("pay")}
                        className="rounded-md bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700"
                      >
                        Mark Paid
                      </button>
                    ) : null}
                    {claim.permissions?.can_reject ? (
                      <button
                        type="button"
                        onClick={() => submitAction("reject")}
                        className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                      >
                        Reject
                      </button>
                    ) : null}
                  </div>
                </div>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
