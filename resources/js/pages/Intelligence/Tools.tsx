import { Head, useForm } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { intelligenceNavItems } from "@/config/domain-nav/intelligence";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Intelligence", href: "/intelligence" },
  { title: "Tools", href: "/intelligence/tools" },
];

export default function IntelligenceTools({
  tools,
}: {
  tools: Array<Record<string, unknown>>;
}) {
  const form = useForm({
    name: "",
    slug: "",
    description: "",
    category: "utility",
    handler_class: "",
    input_schema: { type: "object" },
    output_schema: { type: "object" },
    status: "draft",
    requires_approval: false,
    permission_key: "domain.intelligence.manage",
    timeout_seconds: 10,
    metadata: {},
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Tool Registry" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Tool Runtime</h1>
            <p className="text-sm text-muted-foreground">Safe tool registry with approval flags, schemas, handlers, and execution time limits.</p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
          <Card>
            <CardHeader>
              <CardTitle>Register Tool</CardTitle>
              <CardDescription>Keep tools non-destructive and provider-neutral.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4" onSubmit={(event) => {
                event.preventDefault();
                form.post("/intelligence/tools");
              }}>
                <div className="space-y-2"><Label htmlFor="name">Name</Label><Input id="name" value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} /></div>
                <div className="space-y-2"><Label htmlFor="slug">Slug</Label><Input id="slug" value={form.data.slug} onChange={(event) => form.setData("slug", event.target.value)} /></div>
                <div className="space-y-2"><Label htmlFor="handler_class">Handler Class</Label><Input id="handler_class" value={form.data.handler_class} onChange={(event) => form.setData("handler_class", event.target.value)} /></div>
                <Button type="submit" disabled={form.processing}>Register Tool</Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Registered Tools</CardTitle>
              <CardDescription>Initial safe stubs are seeded by default.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {tools.map((tool, index) => (
                <div key={String(tool.id ?? index)} className="border p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <div className="font-semibold">{String(tool.name)}</div>
                      <div className="text-xs text-muted-foreground">{String(tool.slug)} • {String(tool.status)} • {String(tool.category)}</div>
                    </div>
                    <div className="text-xs text-muted-foreground">approval: {String(tool.requires_approval)}</div>
                  </div>
                  <p className="mt-3 text-sm text-muted-foreground">{String(tool.description ?? "")}</p>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
