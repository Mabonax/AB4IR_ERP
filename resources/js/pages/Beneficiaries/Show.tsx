import { useState } from "react";
import { Head, Link } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { BeneficiaryModelFormConfig } from "@/config/forms/beneficiary-model-form";
import beneficiaries from "@/routes/beneficiaries";
import { type BreadcrumbItem } from "@/types";

export default function BeneficiaryShow({
  beneficiary,
  canManageBeneficiary,
  provinces,
  projects,
  projectLocations,
}: {
  beneficiary: any;
  canManageBeneficiary: boolean;
  provinces: { id: number; name: string }[];
  projects: { id: number; name: string }[];
  projectLocations: { id: number; project_id: number; name: string }[];
}) {
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);

  const mappedBeneficiaryData = {
    name: beneficiary.name ?? "",
    surname: beneficiary.surname ?? "",
    dob: beneficiary.dob ?? "",
    age: beneficiary.age ?? "",
    id_number: beneficiary.id_number ?? "",
    email: beneficiary.email ?? "",
    phone: beneficiary.phone ?? "",
    gender: beneficiary.gender ?? "",
    project_id:
      beneficiary.project_id !== null && beneficiary.project_id !== undefined
        ? String(beneficiary.project_id)
        : "",
    project_location_id:
      beneficiary.project_location_id !== null && beneficiary.project_location_id !== undefined
        ? String(beneficiary.project_location_id)
        : "",
    street_address: beneficiary.street_address ?? "",
    address_line_2: beneficiary.address_line_2 ?? "",
    city: beneficiary.city ?? "",
    province_id:
      beneficiary.province_id !== null && beneficiary.province_id !== undefined
        ? String(beneficiary.province_id)
        : "",
    postal_code: beneficiary.postal_code ?? "",
    highest_qualification: beneficiary.highest_qualification ?? "",
    attendance_status: beneficiary.attendance_status ?? "active",
    nok_name: beneficiary.next_of_kin?.name ?? "",
    nok_surname: beneficiary.next_of_kin?.surname ?? "",
    nok_relationship: beneficiary.next_of_kin?.relationship ?? "",
    nok_phone: beneficiary.next_of_kin?.phone ?? "",
    nok_email: beneficiary.next_of_kin?.email ?? "",
  };

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Beneficiaries", href: beneficiaries.index() },
    { title: beneficiary.full_name ?? "Beneficiary File", href: `/beneficiaries/${beneficiary.id}` },
  ];

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
              <button
                type="button"
                onClick={() => setEditOpen(true)}
                className="rounded-md border border-orange-500 px-4 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
              >
                Edit Beneficiary
              </button>
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
            <div className="text-sm text-muted-foreground">Attendance Status</div>
            <div className="mt-1 text-xl font-semibold capitalize">{beneficiary.attendance_status ?? "-"}</div>
          </section>
        </div>

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

        <div className="grid gap-4 lg:grid-cols-2">
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

        <CustomModelForm
          hideTrigger
          open={editOpen}
          onOpenChange={setEditOpen}
          title="Edit Beneficiary"
          fields={BeneficiaryModelFormConfig.fields}
          mode="edit"
          initialData={mappedBeneficiaryData}
          submitRoute={beneficiaries.update}
          routeParams={beneficiary.id}
          options={{ provinces, projects, projectLocations }}
        />

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
