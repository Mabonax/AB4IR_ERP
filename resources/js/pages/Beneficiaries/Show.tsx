import { useMemo, useState } from "react";
import { Head, Link, router } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import beneficiaries from "@/routes/beneficiaries";
import { type BreadcrumbItem } from "@/types";

export default function BeneficiaryShow({
  beneficiary,
  canManageBeneficiary,
  lifecycleOptions,
}: {
  beneficiary: any;
  canManageBeneficiary: boolean;
  lifecycleOptions: {
    outcomeTypes: Array<{ value: string; label: string }>;
    projects: Array<{ id: number; name: string; program_id: number; status: string }>;
    projectLocations: Array<{ id: number; project_id: number; name: string }>;
  };
}) {
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [action, setAction] = useState<null | "suspend" | "reinstate" | "graduate" | "exit" | "transfer" | "archive">(null);
  const [reason, setReason] = useState("");
  const [outcomeType, setOutcomeType] = useState("unknown_outcome");
  const [outcomeNotes, setOutcomeNotes] = useState("");
  const [transferProjectId, setTransferProjectId] = useState("");
  const [transferLocationId, setTransferLocationId] = useState("");

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Beneficiaries", href: beneficiaries.index() },
    { title: beneficiary.full_name ?? "Beneficiary File", href: `/beneficiaries/${beneficiary.id}` },
  ];
  const availableLocations = useMemo(
    () => lifecycleOptions.projectLocations.filter((location) => String(location.project_id) === transferProjectId),
    [lifecycleOptions.projectLocations, transferProjectId],
  );
  const statusChipClass = beneficiary.status === "graduated"
    ? "border-emerald-200 bg-emerald-50 text-emerald-700"
    : beneficiary.status === "suspended"
      ? "border-amber-200 bg-amber-50 text-amber-700"
      : beneficiary.status === "exited" || beneficiary.status === "archived"
        ? "border-rose-200 bg-rose-50 text-rose-700"
        : "border-sky-200 bg-sky-50 text-sky-700";

  const resetLifecycleForm = () => {
    setAction(null);
    setReason("");
    setOutcomeType("unknown_outcome");
    setOutcomeNotes("");
    setTransferProjectId("");
    setTransferLocationId("");
  };

  const submitLifecycleAction = () => {
    if (!action || !reason.trim()) {
      return;
    }

    const payload: Record<string, unknown> = { reason };

    if (action === "graduate" || action === "exit") {
      payload.outcome_type = outcomeType;
      payload.outcome_notes = outcomeNotes || null;
    }

    if (action === "transfer") {
      payload.project_id = transferProjectId;
      payload.project_location_id = transferLocationId;
    }

    router.post(`/beneficiaries/${beneficiary.id}/${action}`, payload, {
      preserveScroll: true,
      onSuccess: () => resetLifecycleForm(),
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={beneficiary.full_name ?? "Beneficiary File"} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="space-y-1">
            <div className="text-sm text-muted-foreground">
              <Link href={beneficiaries.index().url} className="hover:underline">
                Back to beneficiaries
              </Link>
            </div>
            <h1 className="text-2xl font-semibold">{beneficiary.full_name ?? "-"}</h1>
            <p className="text-sm text-muted-foreground">
              {beneficiary.program_title ?? "No current program"} | {beneficiary.project_name ?? "No current project"}
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            {canManageBeneficiary ? (
              <Link
                href={beneficiaries.edit(beneficiary.id).url}
                className="rounded-md border border-orange-500 px-4 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
              >
                Edit Beneficiary
              </Link>
            ) : null}
            {canManageBeneficiary ? (
              <button
                type="button"
                onClick={() => setDeleteOpen(true)}
                className="rounded-md border border-red-600 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white"
              >
                Delete Beneficiary
              </button>
            ) : null}
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Current Program</div>
            <div className="mt-1 text-xl font-semibold">{beneficiary.current_participation?.program_title ?? "-"}</div>
          </section>
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Current Project</div>
            <div className="mt-1 text-xl font-semibold">{beneficiary.current_participation?.project_name ?? "-"}</div>
          </section>
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Current Site</div>
            <div className="mt-1 text-xl font-semibold">{beneficiary.current_participation?.location_name ?? "-"}</div>
          </section>
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Lifecycle Status</div>
            <div className={`mt-2 inline-flex rounded-full border px-3 py-1 text-sm font-medium capitalize ${statusChipClass}`}>
              {String(beneficiary.status ?? "enrolled").replaceAll("_", " ")}
            </div>
            <div className="mt-2 text-xs text-muted-foreground">{beneficiary.status_reason ?? "No lifecycle reason recorded yet."}</div>
          </section>
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="text-sm text-muted-foreground">Attendance Status</div>
            <div className="mt-1 text-xl font-semibold capitalize">{beneficiary.attendance_status ?? "-"}</div>
          </section>
        </div>

        {canManageBeneficiary ? (
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 className="text-base font-semibold">Lifecycle Actions</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                  Govern beneficiary transitions through explicit transactions with reason capture and audit history.
                </p>
              </div>

              <div className="flex flex-wrap gap-2">
                <button type="button" className="rounded-md border px-3 py-2 text-sm hover:bg-accent" onClick={() => setAction("suspend")}>Suspend</button>
                <button type="button" className="rounded-md border px-3 py-2 text-sm hover:bg-accent" onClick={() => setAction("reinstate")}>Reinstate</button>
                <button type="button" className="rounded-md border px-3 py-2 text-sm hover:bg-accent" onClick={() => setAction("transfer")}>Transfer</button>
                <button type="button" className="rounded-md border px-3 py-2 text-sm hover:bg-accent" onClick={() => setAction("graduate")}>Graduate</button>
                <button type="button" className="rounded-md border px-3 py-2 text-sm hover:bg-accent" onClick={() => setAction("exit")}>Exit</button>
                <button type="button" className="rounded-md border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50" onClick={() => setAction("archive")}>Archive</button>
              </div>
            </div>

            {action ? (
              <div className="mt-4 grid gap-4 rounded-xl border bg-muted/30 p-4 lg:grid-cols-2">
                <div className="space-y-2 lg:col-span-2">
                  <div className="text-sm font-medium capitalize">{action.replaceAll("_", " ")} Beneficiary</div>
                  <textarea
                    className="min-h-28 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    placeholder="Capture the business reason for this transaction."
                    value={reason}
                    onChange={(event) => setReason(event.target.value)}
                  />
                </div>

                {(action === "graduate" || action === "exit") ? (
                  <>
                    <div className="space-y-2">
                      <label className="block text-sm font-medium">Outcome</label>
                      <select
                        className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                        value={outcomeType}
                        onChange={(event) => setOutcomeType(event.target.value)}
                      >
                        {lifecycleOptions.outcomeTypes.map((option) => (
                          <option key={option.value} value={option.value}>{option.label}</option>
                        ))}
                      </select>
                    </div>
                    <div className="space-y-2">
                      <label className="block text-sm font-medium">Outcome Notes</label>
                      <textarea
                        className="min-h-24 w-full rounded-md border bg-card px-3 py-2 text-sm"
                        placeholder="Optional context about the outcome."
                        value={outcomeNotes}
                        onChange={(event) => setOutcomeNotes(event.target.value)}
                      />
                    </div>
                  </>
                ) : null}

                {action === "transfer" ? (
                  <>
                    <div className="space-y-2">
                      <label className="block text-sm font-medium">Target Project</label>
                      <select
                        className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                        value={transferProjectId}
                        onChange={(event) => {
                          setTransferProjectId(event.target.value);
                          setTransferLocationId("");
                        }}
                      >
                        <option value="">Select project</option>
                        {lifecycleOptions.projects.map((project) => (
                          <option key={project.id} value={project.id}>{project.name}</option>
                        ))}
                      </select>
                    </div>
                    <div className="space-y-2">
                      <label className="block text-sm font-medium">Target Location</label>
                      <select
                        className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                        value={transferLocationId}
                        onChange={(event) => setTransferLocationId(event.target.value)}
                      >
                        <option value="">{transferProjectId ? "Select location" : "Choose a project first"}</option>
                        {availableLocations.map((location) => (
                          <option key={location.id} value={location.id}>{location.name}</option>
                        ))}
                      </select>
                    </div>
                  </>
                ) : null}

                <div className="flex flex-wrap gap-2 lg:col-span-2">
                  <button
                    type="button"
                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={action === "transfer" ? !transferProjectId || !transferLocationId || !reason.trim() : !reason.trim()}
                    onClick={submitLifecycleAction}
                  >
                    Submit Transaction
                  </button>
                  <button type="button" className="rounded-md border px-4 py-2 text-sm hover:bg-accent" onClick={resetLifecycleForm}>Cancel</button>
                </div>
              </div>
            ) : null}
          </section>
        ) : null}

        <div className="grid gap-4 lg:grid-cols-3">
          <section className="rounded-xl border bg-card p-4 shadow-sm lg:col-span-2">
            <h2 className="text-base font-semibold">Beneficiary Profile</h2>
            <dl className="mt-3 grid gap-3 text-sm md:grid-cols-2">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Full Name</dt>
                <dd>{beneficiary.full_name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Date of Birth</dt>
                <dd>{beneficiary.dob ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Age</dt>
                <dd>{beneficiary.age ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Gender</dt>
                <dd>{beneficiary.gender ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">ID Number</dt>
                <dd>{beneficiary.id_number ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Qualification</dt>
                <dd>{beneficiary.highest_qualification ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Email</dt>
                <dd>{beneficiary.email ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Phone</dt>
                <dd>{beneficiary.phone ?? "-"}</dd>
              </div>
            </dl>
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Current Placement</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Lifecycle Status</dt>
                <dd className="capitalize">{String(beneficiary.status ?? "enrolled").replaceAll("_", " ")}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Program</dt>
                <dd>{beneficiary.current_participation?.program_title ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Project</dt>
                <dd>{beneficiary.current_participation?.project_name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Location</dt>
                <dd>{beneficiary.current_participation?.location_name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Enrollment Status</dt>
                <dd className="capitalize">{beneficiary.current_participation?.status ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Enrolled At</dt>
                <dd>{beneficiary.current_participation?.enrolled_at ?? "-"}</dd>
              </div>
            </dl>
          </section>
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Address and Contact</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Street Address</dt>
                <dd>{beneficiary.street_address ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Address Line 2</dt>
                <dd>{beneficiary.address_line_2 ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">City</dt>
                <dd>{beneficiary.city ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Postal Code</dt>
                <dd>{beneficiary.postal_code ?? "-"}</dd>
              </div>
            </dl>
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Next of Kin</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Full Name</dt>
                <dd>
                  {beneficiary.next_of_kin
                    ? `${beneficiary.next_of_kin.name ?? ""} ${beneficiary.next_of_kin.surname ?? ""}`.trim()
                    : "-"}
                </dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Relationship</dt>
                <dd>{beneficiary.next_of_kin?.relationship ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Phone</dt>
                <dd>{beneficiary.next_of_kin?.phone ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Email</dt>
                <dd>{beneficiary.next_of_kin?.email ?? "-"}</dd>
              </div>
            </dl>
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Latest Outcome</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Outcome</dt>
                <dd className="capitalize">{String(beneficiary.latest_outcome?.outcome_type ?? "-").replaceAll("_", " ")}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Recorded At</dt>
                <dd>{beneficiary.latest_outcome?.recorded_at ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Recorded By</dt>
                <dd>{beneficiary.latest_outcome?.recorded_by_name ?? "-"}</dd>
              </div>
              <div className="pt-2 text-sm text-muted-foreground">
                {beneficiary.latest_outcome?.notes ?? "No beneficiary outcome has been recorded yet."}
              </div>
            </dl>
          </section>
        </div>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Participation History</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Historical participation across programs, project iterations, and delivery sites.
          </p>

          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="px-3 py-2 text-left">Program</th>
                  <th className="px-3 py-2 text-left">Project</th>
                  <th className="px-3 py-2 text-left">Location</th>
                  <th className="px-3 py-2 text-left">Status</th>
                  <th className="px-3 py-2 text-left">Project Window</th>
                  <th className="px-3 py-2 text-left">Enrolled At</th>
                </tr>
              </thead>
              <tbody>
                {(beneficiary.participation_history ?? []).map((entry: any) => (
                  <tr key={entry.id} className="border-b">
                    <td className="px-3 py-2">{entry.program_title ?? "-"}</td>
                    <td className="px-3 py-2">{entry.project_name ?? "-"}</td>
                    <td className="px-3 py-2">{entry.location_name ?? "-"}</td>
                    <td className="px-3 py-2 capitalize">{entry.status ?? "-"}</td>
                    <td className="px-3 py-2">
                      {entry.project_start_date ?? "-"} to {entry.project_end_date ?? "ongoing"}
                    </td>
                    <td className="px-3 py-2">{entry.enrolled_at ?? "-"}</td>
                  </tr>
                ))}
                {(beneficiary.participation_history ?? []).length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-3 py-3 text-muted-foreground">
                      No participation history recorded yet.
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </section>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Lifecycle Timeline</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Creation, transfers, suspensions, outcomes, and other lifecycle transitions are recorded here.
          </p>

          <div className="mt-4 space-y-3">
            {(beneficiary.timeline ?? []).length === 0 ? (
              <div className="text-sm text-muted-foreground">No lifecycle history has been recorded yet.</div>
            ) : (
              beneficiary.timeline.map((item: any) => (
                <div key={item.id} className="rounded-lg border p-3">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="font-medium">{item.summary}</div>
                    <div className="text-xs text-muted-foreground">{item.created_at ?? "-"}</div>
                  </div>
                  <div className="mt-1 text-sm text-muted-foreground">
                    {item.actor_name ?? "System"} | {String(item.from_status ?? "new").replaceAll("_", " ")} to {String(item.to_status ?? "-").replaceAll("_", " ")}
                  </div>
                  {item.reason ? <div className="mt-2 text-sm">{item.reason}</div> : null}
                </div>
              ))
            )}
          </div>
        </section>

        <ConfirmDeleteModal
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          title="Delete Beneficiary"
          submitRoute={beneficiaries.destroy}
          routeParams={beneficiary.id}
        />
      </div>
    </AppLayout>
  );
}
