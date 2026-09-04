import { Head, router, useForm } from "@inertiajs/react";
import { FormEvent, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type Criterion = {
  id: number;
  name: string;
  code: string;
  description: string | null;
  sequence: number;
  weighting: string | number;
  required: boolean;
  active: boolean;
  evidence_required: boolean;
  guidance: string | null;
  expires: boolean;
};

type Dimension = {
  id: number;
  name: string;
  code: string;
  description: string | null;
  sequence: number;
  weighting: string | number;
  active: boolean;
  criteria: Criterion[];
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Development Framework", href: "/business-development/development-framework" },
];

function boolLabel(value: boolean): string {
  return value ? "Yes" : "No";
}

export default function DevelopmentFrameworkIndex({ dimensions }: { dimensions: Dimension[] }) {
  const [selectedDimension, setSelectedDimension] = useState<Dimension | null>(dimensions[0] ?? null);

  const dimensionForm = useForm({
    name: "",
    code: "",
    description: "",
    sequence: dimensions.length + 1,
    weighting: "1",
    active: true,
  });

  const criterionForm = useForm({
    name: "",
    code: "",
    description: "",
    sequence: 1,
    weighting: "1",
    required: false,
    active: true,
    evidence_required: false,
    guidance: "",
    expires: false,
  });

  function submitDimension(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    dimensionForm.post("/business-development/development-framework/dimensions", {
      preserveScroll: true,
      onSuccess: () => dimensionForm.reset(),
    });
  }

  function submitCriterion(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedDimension) return;
    criterionForm.post(`/business-development/development-framework/dimensions/${selectedDimension.id}/criteria`, {
      preserveScroll: true,
      onSuccess: () => criterionForm.reset(),
    });
  }

  function toggleDimension(dimension: Dimension) {
    router.put(
      `/business-development/development-framework/dimensions/${dimension.id}`,
      { ...dimension, active: !dimension.active },
      { preserveScroll: true },
    );
  }

  function toggleCriterion(criterion: Criterion) {
    router.put(
      `/business-development/development-framework/criteria/${criterion.id}`,
      { ...criterion, active: !criterion.active },
      { preserveScroll: true },
    );
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Enterprise Development Framework" />
      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p className="text-sm text-muted-foreground">Enterprise Development</p>
            <h1 className="text-2xl font-semibold">Framework Configuration</h1>
          </div>
          <DomainNav items={businessDevelopmentNavItems} />
        </div>

        <section className="grid gap-4 xl:grid-cols-[1fr_1.2fr]">
          <div className="rounded-lg border bg-card p-4">
            <h2 className="text-base font-semibold">Dimensions</h2>
            <div className="mt-4 space-y-2">
              {dimensions.map((dimension) => (
                <button
                  key={dimension.id}
                  type="button"
                  onClick={() => setSelectedDimension(dimension)}
                  className={`w-full rounded-md border px-3 py-2 text-left text-sm ${selectedDimension?.id === dimension.id ? "border-orange-500 bg-orange-50" : "hover:bg-muted"}`}
                >
                  <div className="flex items-center justify-between gap-3">
                    <span className="font-medium">{dimension.sequence}. {dimension.name}</span>
                    <span className="text-xs text-muted-foreground">Weight {dimension.weighting}</span>
                  </div>
                  <div className="mt-1 text-xs text-muted-foreground">{dimension.active ? "Active" : "Inactive"} - {dimension.criteria.length} criteria</div>
                </button>
              ))}
            </div>
          </div>

          <div className="rounded-lg border bg-card p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 className="text-base font-semibold">{selectedDimension?.name ?? "Select a dimension"}</h2>
              {selectedDimension ? (
                <button type="button" onClick={() => toggleDimension(selectedDimension)} className="rounded-md border px-3 py-1.5 text-sm hover:bg-muted">
                  {selectedDimension.active ? "Deactivate" : "Activate"}
                </button>
              ) : null}
            </div>
            <div className="mt-4 overflow-x-auto">
              <table className="min-w-full text-sm">
                <thead className="bg-muted/50 text-xs uppercase text-muted-foreground">
                  <tr>
                    <th className="px-3 py-2 text-left">Criterion</th>
                    <th className="px-3 py-2 text-left">Required</th>
                    <th className="px-3 py-2 text-left">Evidence</th>
                    <th className="px-3 py-2 text-left">Status</th>
                    <th className="px-3 py-2 text-left">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {(selectedDimension?.criteria ?? []).map((criterion) => (
                    <tr key={criterion.id} className="border-t">
                      <td className="px-3 py-2">
                        <div className="font-medium">{criterion.name}</div>
                        <div className="text-xs text-muted-foreground">{criterion.guidance ?? criterion.description}</div>
                      </td>
                      <td className="px-3 py-2">{boolLabel(criterion.required)}</td>
                      <td className="px-3 py-2">{boolLabel(criterion.evidence_required)}</td>
                      <td className="px-3 py-2">{criterion.active ? "Active" : "Inactive"}</td>
                      <td className="px-3 py-2">
                        <button type="button" onClick={() => toggleCriterion(criterion)} className="rounded-md border px-2.5 py-1 text-xs hover:bg-muted">
                          {criterion.active ? "Deactivate" : "Activate"}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section className="grid gap-4 xl:grid-cols-2">
          <form onSubmit={submitDimension} className="rounded-lg border bg-card p-4">
            <h2 className="text-base font-semibold">Add Dimension</h2>
            <div className="mt-4 grid gap-3 md:grid-cols-2">
              <input className="rounded-md border bg-background px-3 py-2 text-sm" value={dimensionForm.data.name} onChange={(event) => dimensionForm.setData("name", event.target.value)} placeholder="Name" required />
              <input className="rounded-md border bg-background px-3 py-2 text-sm" value={dimensionForm.data.code} onChange={(event) => dimensionForm.setData("code", event.target.value)} placeholder="Code" required />
              <input className="rounded-md border bg-background px-3 py-2 text-sm" type="number" value={dimensionForm.data.sequence} onChange={(event) => dimensionForm.setData("sequence", Number(event.target.value))} placeholder="Sequence" />
              <input className="rounded-md border bg-background px-3 py-2 text-sm" type="number" step="0.01" value={dimensionForm.data.weighting} onChange={(event) => dimensionForm.setData("weighting", event.target.value)} placeholder="Weighting" />
              <textarea className="min-h-20 rounded-md border bg-background px-3 py-2 text-sm md:col-span-2" value={dimensionForm.data.description} onChange={(event) => dimensionForm.setData("description", event.target.value)} placeholder="Description" />
            </div>
            <button type="submit" disabled={dimensionForm.processing} className="mt-4 rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Create Dimension</button>
          </form>

          <form onSubmit={submitCriterion} className="rounded-lg border bg-card p-4">
            <h2 className="text-base font-semibold">Add Criterion</h2>
            <div className="mt-4 grid gap-3 md:grid-cols-2">
              <input className="rounded-md border bg-background px-3 py-2 text-sm" value={criterionForm.data.name} onChange={(event) => criterionForm.setData("name", event.target.value)} placeholder="Name" required />
              <input className="rounded-md border bg-background px-3 py-2 text-sm" value={criterionForm.data.code} onChange={(event) => criterionForm.setData("code", event.target.value)} placeholder="Code" required />
              <input className="rounded-md border bg-background px-3 py-2 text-sm" type="number" value={criterionForm.data.sequence} onChange={(event) => criterionForm.setData("sequence", Number(event.target.value))} placeholder="Sequence" />
              <input className="rounded-md border bg-background px-3 py-2 text-sm" type="number" step="0.01" value={criterionForm.data.weighting} onChange={(event) => criterionForm.setData("weighting", event.target.value)} placeholder="Weighting" />
              <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={criterionForm.data.required} onChange={(event) => criterionForm.setData("required", event.target.checked)} /> Required</label>
              <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={criterionForm.data.evidence_required} onChange={(event) => criterionForm.setData("evidence_required", event.target.checked)} /> Evidence required</label>
              <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={criterionForm.data.expires} onChange={(event) => criterionForm.setData("expires", event.target.checked)} /> Expiry tracked</label>
              <textarea className="min-h-20 rounded-md border bg-background px-3 py-2 text-sm md:col-span-2" value={criterionForm.data.guidance} onChange={(event) => criterionForm.setData("guidance", event.target.value)} placeholder="Guidance" />
            </div>
            <button type="submit" disabled={criterionForm.processing || !selectedDimension} className="mt-4 rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Create Criterion</button>
          </form>
        </section>
      </div>
    </AppLayout>
  );
}
