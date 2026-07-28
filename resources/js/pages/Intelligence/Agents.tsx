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
  { title: "Agents", href: "/intelligence/agents" },
];

export default function IntelligenceAgents({
  agents,
}: {
  agents: Array<Record<string, unknown>>;
}) {
  const form = useForm({
    name: "",
    slug: "",
    description: "",
    status: "active",
    purpose: "",
    system_instructions: "",
    default_provider: "stub",
    default_model: "stub-chat-v1",
    temperature: 0.2,
    max_tokens: 1024,
    allowed_tools: ["current_datetime", "platform_status"],
    allowed_knowledge_sources: ["organization", "projects"],
    memory_enabled: true,
    conversation_limit: 30,
    visibility: "organization",
    metadata: {},
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Agents" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Agents</h1>
            <p className="text-sm text-muted-foreground">Define runtime behavior, allowed tools, visibility, and orchestration defaults.</p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
          <Card>
            <CardHeader>
              <CardTitle>Create Agent</CardTitle>
              <CardDescription>Provider-neutral defaults with safe tool access.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4" onSubmit={(event) => {
                event.preventDefault();
                form.post("/intelligence/agents");
              }}>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2"><Label htmlFor="name">Name</Label><Input id="name" value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} /></div>
                  <div className="space-y-2"><Label htmlFor="slug">Slug</Label><Input id="slug" value={form.data.slug} onChange={(event) => form.setData("slug", event.target.value)} /></div>
                  <div className="space-y-2"><Label htmlFor="provider">Provider</Label><Input id="provider" value={form.data.default_provider} onChange={(event) => form.setData("default_provider", event.target.value)} /></div>
                  <div className="space-y-2"><Label htmlFor="model">Model</Label><Input id="model" value={form.data.default_model} onChange={(event) => form.setData("default_model", event.target.value)} /></div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="purpose">Purpose</Label>
                  <textarea id="purpose" value={form.data.purpose} onChange={(event) => form.setData("purpose", event.target.value)} className="min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="instructions">System Instructions</Label>
                  <textarea id="instructions" value={form.data.system_instructions} onChange={(event) => form.setData("system_instructions", event.target.value)} className="min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm" />
                </div>
                <Button type="submit" disabled={form.processing}>Create Agent</Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Registered Agents</CardTitle>
              <CardDescription>Operational registry for draft, active, disabled, and archived agents.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {agents.map((agent, index) => (
                <div key={String(agent.id ?? index)} className="border p-4">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                      <div className="font-semibold">{String(agent.name)}</div>
                      <div className="text-xs text-muted-foreground">{String(agent.slug)} • {String(agent.status)}</div>
                    </div>
                    <div className="text-xs text-muted-foreground">{String(agent.default_provider)} / {String(agent.default_model)}</div>
                  </div>
                  <p className="mt-3 text-sm text-muted-foreground">{String(agent.purpose ?? agent.description ?? "")}</p>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
