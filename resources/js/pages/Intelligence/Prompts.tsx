import { Head, router, useForm } from "@inertiajs/react";

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
  { title: "Prompts", href: "/intelligence/prompts" },
];

export default function IntelligencePrompts({
  prompts,
}: {
  prompts: Array<Record<string, unknown>>;
}) {
  const form = useForm({
    name: "",
    slug: "",
    description: "",
    category: "operations",
    status: "draft",
    system_prompt: "",
    developer_prompt: "",
    user_prompt_template: "{{message}}",
    variables_schema: { properties: { message: { type: "string" } } },
    output_schema: { type: "object" },
    is_default: false,
    metadata: {},
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Prompt Registry" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Prompt Registry</h1>
            <p className="text-sm text-muted-foreground">Versioned system, developer, and user prompt assets for consistent orchestration.</p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
          <Card>
            <CardHeader>
              <CardTitle>Create Prompt Template</CardTitle>
              <CardDescription>Register reusable prompt assets with version tracking.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4" onSubmit={(event) => {
                event.preventDefault();
                form.post("/intelligence/prompts");
              }}>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2"><Label htmlFor="name">Name</Label><Input id="name" value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} /></div>
                  <div className="space-y-2"><Label htmlFor="slug">Slug</Label><Input id="slug" value={form.data.slug} onChange={(event) => form.setData("slug", event.target.value)} /></div>
                </div>
                <div className="space-y-2"><Label htmlFor="system_prompt">System Prompt</Label><textarea id="system_prompt" value={form.data.system_prompt} onChange={(event) => form.setData("system_prompt", event.target.value)} className="min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm" /></div>
                <div className="space-y-2"><Label htmlFor="developer_prompt">Developer Prompt</Label><textarea id="developer_prompt" value={form.data.developer_prompt} onChange={(event) => form.setData("developer_prompt", event.target.value)} className="min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm" /></div>
                <div className="space-y-2"><Label htmlFor="user_prompt_template">User Template</Label><textarea id="user_prompt_template" value={form.data.user_prompt_template} onChange={(event) => form.setData("user_prompt_template", event.target.value)} className="min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm" /></div>
                <Button type="submit" disabled={form.processing}>Create Prompt</Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Prompt Templates</CardTitle>
              <CardDescription>Activate the latest approved prompt per slug.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {prompts.map((prompt, index) => (
                <div key={String(prompt.id ?? index)} className="border p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <div className="font-semibold">{String(prompt.name)}</div>
                      <div className="text-xs text-muted-foreground">{String(prompt.slug)} • v{String(prompt.version)} • {String(prompt.status)}</div>
                    </div>
                    <Button type="button" variant="outline" onClick={() => router.post(`/intelligence/prompts/${prompt.id}/activate`)}>
                      Activate
                    </Button>
                  </div>
                  <p className="mt-3 text-sm text-muted-foreground">{String(prompt.description ?? "")}</p>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
