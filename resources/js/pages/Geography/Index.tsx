import { Head, useForm } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { humanCapitalNavItems } from "@/config/domain-nav/human-capital";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Geography", href: "/geography" }];

type RegistryPayload = {
  provinces: Array<{ id: number; name: string }>;
  municipalities: Array<{ id: number; name: string; province_name?: string | null }>;
  regions: Array<{ id: number; name: string; municipality_name?: string | null }>;
  townships: Array<{ id: number; name: string; region_name?: string | null }>;
  wards: Array<{ id: number; name: string; township_name?: string | null }>;
  branches: Array<{ id: number; name: string; ward_name?: string | null }>;
};

export default function GeographyIndex({ registry }: { registry: RegistryPayload }) {
  const form = useForm({
    type: "municipality",
    province_id: "",
    municipality_id: "",
    region_id: "",
    township_id: "",
    ward_id: "",
    name: "",
    code: "",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Geography Registry" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Geographic Registry</h1>
            <p className="text-sm text-muted-foreground">Maintain the province-to-branch hierarchy used by member reporting and township intelligence.</p>
          </div>
          <DomainNav items={humanCapitalNavItems} />
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Add Geographic Record</CardTitle>
            <CardDescription>Use the correct parent hierarchy so township reporting rolls up cleanly.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-4">
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={form.data.type} onChange={(event) => form.setData("type", event.target.value)}>
              <option value="municipality">Municipality</option>
              <option value="region">Region</option>
              <option value="township">Township</option>
              <option value="ward">Ward</option>
              <option value="branch">Branch</option>
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={form.data.province_id} onChange={(event) => form.setData("province_id", event.target.value)}>
              <option value="">Province</option>
              {registry.provinces.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={form.data.municipality_id} onChange={(event) => form.setData("municipality_id", event.target.value)}>
              <option value="">Municipality</option>
              {registry.municipalities.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={form.data.region_id} onChange={(event) => form.setData("region_id", event.target.value)}>
              <option value="">Region</option>
              {registry.regions.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={form.data.township_id} onChange={(event) => form.setData("township_id", event.target.value)}>
              <option value="">Township</option>
              {registry.townships.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={form.data.ward_id} onChange={(event) => form.setData("ward_id", event.target.value)}>
              <option value="">Ward</option>
              {registry.wards.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
            <input className="rounded-md border bg-card px-3 py-2 text-sm" placeholder="Name" value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} />
            <div className="flex gap-3">
              <input className="w-full rounded-md border bg-card px-3 py-2 text-sm" placeholder="Code" value={form.data.code} onChange={(event) => form.setData("code", event.target.value)} />
              <Button type="button" onClick={() => form.post("/geography")}>
                Save
              </Button>
            </div>
          </CardContent>
        </Card>

        <div className="grid gap-6 xl:grid-cols-2">
          <RegistryCard title="Municipalities" description="Linked to provinces" items={registry.municipalities.map((item) => `${item.name} - ${item.province_name ?? "No province"}`)} />
          <RegistryCard title="Regions" description="Linked to municipalities" items={registry.regions.map((item) => `${item.name} - ${item.municipality_name ?? "No municipality"}`)} />
          <RegistryCard title="Townships" description="Linked to regions" items={registry.townships.map((item) => `${item.name} - ${item.region_name ?? "No region"}`)} />
          <RegistryCard title="Wards" description="Linked to townships" items={registry.wards.map((item) => `${item.name} - ${item.township_name ?? "No township"}`)} />
          <RegistryCard title="Branches" description="Linked to wards" items={registry.branches.map((item) => `${item.name} - ${item.ward_name ?? "No ward"}`)} />
        </div>
      </div>
    </AppLayout>
  );
}

function RegistryCard({ title, description, items }: { title: string; description: string; items: string[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-2">
        {items.length === 0 ? <p className="text-sm text-muted-foreground">No records captured yet.</p> : items.map((item) => <div key={item} className="rounded-lg border p-3 text-sm">{item}</div>)}
      </CardContent>
    </Card>
  );
}
