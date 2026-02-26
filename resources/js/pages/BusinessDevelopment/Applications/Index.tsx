import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import businessDevelopment from "@/routes/business-development";
import { type BreadcrumbItem, type SharedData } from "@/types";

type ApplicationRow = {
  id: number;
  full_name: string;
  id_number: string;
  gender: string;
  mobile_number: string;
  email: string;
  company_name: string;
  company_registration_number: string;
  current_number_of_employees: number;
  years_in_operation: number;
  province_name: string | null;
  application_date: string | null;
  assessment_status: "pending" | "accepted" | "rejected";
  assessed_by_staff_id: number | null;
  assessor_name: string | null;
  assessed_at: string | null;
  pitch_scheduled_at: string | null;
  pitch_notes: string | null;
  adjudication_result: "incubated" | "rejected" | null;
  adjudicated_at: string | null;
  has_submitted_adjudication: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Applications", href: businessDevelopment.applications.index().url },
];

export default function BdsApplicationsIndex({
  applications,
}: {
  applications: { data: ApplicationRow[] };
}) {
  const { props } = usePage<SharedData>();
  const flash = (props.flash ?? {}) as Record<string, unknown>;
  const importErrors = Array.isArray(flash.import_errors)
    ? (flash.import_errors as string[])
    : [];

  const [selected, setSelected] = useState<ApplicationRow | null>(null);

  const importForm = useForm<{ file: File | null }>({
    file: null,
  });
  const assessForm = useForm({
    assessment_status: "accepted",
  });
  const pitchForm = useForm({
    pitch_scheduled_at: "",
    pitch_notes: "",
  });

  const selectedCanPitch = useMemo(
    () => selected?.assessment_status === "accepted",
    [selected]
  );

  const submitImport = (e: React.FormEvent) => {
    e.preventDefault();
    importForm.post(businessDevelopment.applications.import().url, {
      forceFormData: true,
      preserveScroll: true,
    });
  };

  const submitAssessment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selected) return;

    assessForm.post(businessDevelopment.applications.assess(selected.id).url, {
      preserveScroll: true,
      onSuccess: () => setSelected(null),
    });
  };

  const submitPitch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selected) return;

    pitchForm.post(
      businessDevelopment.applications.schedulePitch(selected.id).url,
      {
        preserveScroll: true,
        onSuccess: () => setSelected(null),
      }
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Business Development Applications" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Business Development Applications</h1>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <button
              type="button"
              onClick={() => router.visit(businessDevelopment.applications.index().url)}
              className="rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Refresh
            </button>
          </div>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {flash.error ? (
          <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
            {String(flash.error)}
          </div>
        ) : null}

        {importErrors.length > 0 ? (
          <section className="rounded-xl border border-amber-300 bg-amber-50 p-4">
            <h2 className="text-base font-semibold text-amber-900">Import Errors</h2>
            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800">
              {importErrors.map((err) => (
                <li key={err}>{err}</li>
              ))}
            </ul>
          </section>
        ) : null}

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Import Applications (CSV/XLSX)</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            Upload application file from the website export. Required headers must match exactly.
          </p>
          <form onSubmit={submitImport} className="mt-3 flex flex-wrap items-center gap-3">
            <input
              type="file"
              accept=".csv,.txt,.xlsx"
              onChange={(e) =>
                importForm.setData("file", e.currentTarget.files?.[0] ?? null)
              }
              className="block rounded-md border px-3 py-2 text-sm"
              required
            />
            <button
              type="submit"
              disabled={importForm.processing || !importForm.data.file}
              className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {importForm.processing ? "Importing..." : "Import File"}
            </button>
          </form>
          {importForm.errors.file ? (
            <p className="mt-2 text-sm text-red-600">{importForm.errors.file}</p>
          ) : null}
        </section>

        <section className="overflow-x-auto rounded-xl border bg-card shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 text-left font-medium">Applicant</th>
                <th className="px-3 py-2 text-left font-medium">Company</th>
                <th className="px-3 py-2 text-left font-medium">Province</th>
                <th className="px-3 py-2 text-left font-medium">Status</th>
                <th className="px-3 py-2 text-left font-medium">Pitch</th>
                <th className="px-3 py-2 text-left font-medium">Adjudication</th>
                <th className="px-3 py-2 text-left font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              {applications.data.length === 0 ? (
                <tr>
                  <td className="px-3 py-4 text-muted-foreground" colSpan={7}>
                    No applications yet.
                  </td>
                </tr>
              ) : (
                applications.data.map((row) => (
                  <tr key={row.id} className="border-t">
                    <td className="px-3 py-2">
                      <div className="font-medium">{row.full_name}</div>
                      <div className="text-xs text-muted-foreground">
                        ID: {row.id_number} | {row.gender} | {row.mobile_number}
                      </div>
                    </td>
                    <td className="px-3 py-2">
                      <div>{row.company_name}</div>
                      <div className="text-xs text-muted-foreground">
                        Reg: {row.company_registration_number}
                      </div>
                    </td>
                    <td className="px-3 py-2">{row.province_name ?? "-"}</td>
                    <td className="px-3 py-2 capitalize">{row.assessment_status}</td>
                    <td className="px-3 py-2">
                      {row.pitch_scheduled_at ? (
                        <span className="text-green-700">{row.pitch_scheduled_at}</span>
                      ) : (
                        <span className="text-muted-foreground">Not scheduled</span>
                      )}
                    </td>
                    <td className="px-3 py-2">
                      {row.adjudication_result ? (
                        <span className="capitalize">{row.adjudication_result}</span>
                      ) : (
                        <span className="text-muted-foreground">Pending</span>
                      )}
                    </td>
                    <td className="px-3 py-2">
                      <button
                        type="button"
                        onClick={() =>
                          router.visit(businessDevelopment.applications.show(row.id).url)
                        }
                        className="mr-2 rounded-md border border-orange-500 px-3 py-1.5 text-xs text-orange-600 hover:bg-orange-500 hover:text-white"
                      >
                        View
                      </button>
                      {row.assessment_status === "accepted" && row.pitch_scheduled_at ? (
                        <button
                          type="button"
                          onClick={() =>
                            router.visit(`/business-development/adjudications/create?smme_id=${row.id}`)
                          }
                          className="rounded-md border border-orange-500 px-3 py-1.5 text-xs text-orange-600 hover:bg-orange-500 hover:text-white"
                        >
                          Start Adjudication
                        </button>
                      ) : (
                        <button
                          type="button"
                          onClick={() => {
                            setSelected(row);
                            assessForm.setData({
                              assessment_status: row.assessment_status === "rejected" ? "rejected" : "accepted",
                            });
                            pitchForm.setData({
                              pitch_scheduled_at: row.pitch_scheduled_at
                                ? row.pitch_scheduled_at.slice(0, 16)
                                : "",
                              pitch_notes: row.pitch_notes ?? "",
                            });
                          }}
                          className="rounded-md border border-orange-500 px-3 py-1.5 text-xs text-orange-600 hover:bg-orange-500 hover:text-white"
                        >
                          Assess / Pitch
                        </button>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </section>

        {selected ? (
          <section className="grid gap-4 rounded-xl border bg-card p-4 shadow-sm md:grid-cols-2">
            <div>
              <h3 className="text-base font-semibold">Assessment</h3>
              <p className="mb-3 text-sm text-muted-foreground">
                {selected.full_name} | {selected.company_name}
              </p>
              <form onSubmit={submitAssessment} className="space-y-3">
                <div>
                  <label className="mb-1 block text-sm font-medium">Assessment Status</label>
                  <select
                    value={assessForm.data.assessment_status}
                    onChange={(e) =>
                      assessForm.setData(
                        "assessment_status",
                        e.currentTarget.value as "accepted" | "rejected"
                      )
                    }
                    className="w-full rounded-md border px-3 py-2 text-sm"
                  >
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                  </select>
                  {assessForm.errors.assessment_status ? (
                    <p className="mt-1 text-sm text-red-600">{assessForm.errors.assessment_status}</p>
                  ) : null}
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Assessed By</label>
                  <div className="rounded-md border bg-muted px-3 py-2 text-sm">
                    Current logged-in user
                  </div>
                </div>
                <button
                  type="submit"
                  disabled={assessForm.processing}
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                >
                  {assessForm.processing ? "Saving..." : "Save Assessment"}
                </button>
              </form>
            </div>

            <div>
              <h3 className="text-base font-semibold">Pitch Scheduling</h3>
              <p className="mb-3 text-sm text-muted-foreground">
                Only accepted applications can be scheduled.
              </p>
              <form onSubmit={submitPitch} className="space-y-3">
                <div>
                  <label className="mb-1 block text-sm font-medium">Pitch Date & Time</label>
                  <input
                    type="datetime-local"
                    value={pitchForm.data.pitch_scheduled_at}
                    onChange={(e) => pitchForm.setData("pitch_scheduled_at", e.currentTarget.value)}
                    className="w-full rounded-md border px-3 py-2 text-sm"
                    required
                    disabled={!selectedCanPitch}
                  />
                  {pitchForm.errors.pitch_scheduled_at ? (
                    <p className="mt-1 text-sm text-red-600">{pitchForm.errors.pitch_scheduled_at}</p>
                  ) : null}
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium">Pitch Notes</label>
                  <textarea
                    rows={3}
                    value={pitchForm.data.pitch_notes}
                    onChange={(e) => pitchForm.setData("pitch_notes", e.currentTarget.value)}
                    className="w-full rounded-md border px-3 py-2 text-sm"
                    disabled={!selectedCanPitch}
                  />
                  {pitchForm.errors.pitch_notes ? (
                    <p className="mt-1 text-sm text-red-600">{pitchForm.errors.pitch_notes}</p>
                  ) : null}
                </div>
                <button
                  type="submit"
                  disabled={pitchForm.processing || !selectedCanPitch}
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                >
                  {pitchForm.processing ? "Scheduling..." : "Schedule Pitch"}
                </button>
                {!selectedCanPitch ? (
                  <p className="text-sm text-amber-700">
                    Set assessment status to accepted first.
                  </p>
                ) : null}
              </form>
            </div>
          </section>
        ) : null}
      </div>
    </AppLayout>
  );
}
