import { Head, Link, useForm } from "@inertiajs/react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

type FacilitatorFormData = {
  name: string;
  surname: string;
  email: string;
  cell: string;
  specialization: string;
  province_id: string;
  dob: string;
  id_number: string;
  address: string;
};

type Props = {
  pageTitle: string;
  title: string;
  description: string;
  breadcrumbs: BreadcrumbItem[];
  submitLabel: string;
  submitRoute: { url: string; method: "post" | "put" };
  provinces: { id: number; name: string }[];
  initialData: FacilitatorFormData;
  backHref?: string;
};

function Field({
  label,
  error,
  required,
  children,
}: {
  label: string;
  error?: string;
  required?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="grid gap-2">
      <Label className="text-sm font-medium text-slate-700">
        {label}
        {required ? <span className="ml-1 text-red-600">*</span> : null}
      </Label>
      {children}
      {error ? <p className="text-xs text-red-600">{error}</p> : null}
    </div>
  );
}

export function FacilitatorFormPage({
  pageTitle,
  title,
  description,
  breadcrumbs,
  submitLabel,
  submitRoute,
  provinces,
  initialData,
  backHref = facilitators.index().url,
}: Props) {
  const form = useForm<FacilitatorFormData>(initialData);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Facilitator workflow</div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
              <p className="max-w-3xl text-sm text-muted-foreground">{description}</p>
            </div>
          </div>
          <Link href={backHref}>
            <Button variant="outline">Back to Facilitators</Button>
          </Link>
        </div>

        <form
          onSubmit={(event) => {
            event.preventDefault();
            form.submit(submitRoute.method, submitRoute.url, { preserveScroll: true });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
          <div className="space-y-5">
            <Card className="border-red-100 shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Core identity</CardTitle>
                <CardDescription>Keep the required facilitator record lean. Extra profile details can be added later.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                <Field label="First Name" required error={form.errors.name}>
                  <Input value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} />
                </Field>
                <Field label="Surname" required error={form.errors.surname}>
                  <Input value={form.data.surname} onChange={(event) => form.setData("surname", event.target.value)} />
                </Field>
                <Field label="Email Address" required error={form.errors.email}>
                  <Input type="email" value={form.data.email} onChange={(event) => form.setData("email", event.target.value)} />
                </Field>
                <Field label="Cell Number" error={form.errors.cell}>
                  <Input type="tel" value={form.data.cell} onChange={(event) => form.setData("cell", event.target.value)} />
                </Field>
                <Field label="Specialization" error={form.errors.specialization}>
                  <Input value={form.data.specialization} onChange={(event) => form.setData("specialization", event.target.value)} />
                </Field>
                <Field label="Province" error={form.errors.province_id}>
                  <select
                    value={form.data.province_id}
                    onChange={(event) => form.setData("province_id", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">Select province</option>
                    {provinces.map((province) => (
                      <option key={province.id} value={province.id}>
                        {province.name}
                      </option>
                    ))}
                  </select>
                </Field>
              </CardContent>
            </Card>

            <Card className="border-red-100 shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Extended profile</CardTitle>
                <CardDescription>Optional identity and address details for HR or compliance capture when available.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                <Field label="Date of Birth" error={form.errors.dob}>
                  <Input type="date" value={form.data.dob} onChange={(event) => form.setData("dob", event.target.value)} />
                </Field>
                <Field label="ID Number" error={form.errors.id_number}>
                  <Input value={form.data.id_number} onChange={(event) => form.setData("id_number", event.target.value)} />
                </Field>
                <Field label="Address" error={form.errors.address}>
                  <textarea
                    rows={4}
                    value={form.data.address}
                    onChange={(event) => form.setData("address", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  />
                </Field>
              </CardContent>
            </Card>

            <div className="flex flex-wrap items-center justify-end gap-3">
              <Link href={backHref}>
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" disabled={form.processing} className="bg-red-600 text-white hover:bg-red-700">
                {form.processing ? "Saving..." : submitLabel}
              </Button>
            </div>
          </div>

          <div className="space-y-5">
            <Card className="border-slate-200 bg-slate-900 text-white shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Account effect</CardTitle>
                <CardDescription className="text-slate-300">
                  Saving this facilitator also syncs or creates the linked user account by email and ensures the facilitator role is applied.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm text-slate-200">
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Display name</div>
                  <div className="mt-1 font-medium text-white">{[form.data.name, form.data.surname].filter(Boolean).join(" ") || "Not set"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Linked email</div>
                  <div className="mt-1 font-medium text-white">{form.data.email || "Not set"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Province</div>
                  <div className="mt-1 font-medium text-white">{provinces.find((province) => String(province.id) === form.data.province_id)?.name ?? "Not set"}</div>
                </div>
              </CardContent>
            </Card>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
