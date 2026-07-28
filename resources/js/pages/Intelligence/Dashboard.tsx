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
];

export default function IntelligenceDashboard({
  summary,
  diagnostics,
}: {
  summary: Record<string, number>;
  diagnostics: Record<string, string | boolean>;
}) {
  const form = useForm({
    agent_slug: String(diagnostics.default_agent_slug ?? ""),
    subject_type: "organization",
    subject_id: 1,
    message: "Run an intelligence engine diagnostics pass for the current ERP platform.",
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Intelligence Workspace" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Intelligence Workspace</h1>
            <p className="text-sm text-muted-foreground">
              Agents, prompt registry, memory, tools, routing, and execution diagnostics for the POA intelligence engine.
            </p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {Object.entries(summary).map(([key, value]) => (
            <Card key={key}>
              <CardHeader>
                <CardTitle>{key.replaceAll("_", " ")}</CardTitle>
                <CardDescription>Current workspace count</CardDescription>
              </CardHeader>
              <CardContent className="text-2xl font-semibold">{value}</CardContent>
            </Card>
          ))}
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
          <Card>
            <CardHeader>
              <CardTitle>Engine Diagnostics</CardTitle>
              <CardDescription>Run the provider-neutral orchestration path without requiring external API keys.</CardDescription>
            </CardHeader>
            <CardContent>
              <form
                className="space-y-4"
                onSubmit={(event) => {
                  event.preventDefault();
                  form.post("/intelligence/conversations");
                }}
              >
                <div className="grid gap-4 md:grid-cols-3">
                  <div className="space-y-2">
                    <Label htmlFor="agent_slug">Agent Slug</Label>
                    <Input id="agent_slug" value={form.data.agent_slug} onChange={(event) => form.setData("agent_slug", event.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="subject_type">Subject Type</Label>
                    <Input id="subject_type" value={form.data.subject_type} onChange={(event) => form.setData("subject_type", event.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="subject_id">Subject ID</Label>
                    <Input id="subject_id" type="number" value={form.data.subject_id} onChange={(event) => form.setData("subject_id", Number(event.target.value))} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="message">Diagnostic Prompt</Label>
                  <textarea
                    id="message"
                    value={form.data.message}
                    onChange={(event) => form.setData("message", event.target.value)}
                    className="min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                  />
                </div>
                <Button type="submit" disabled={form.processing}>Run Diagnostic Conversation</Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Runtime Flags</CardTitle>
              <CardDescription>Current backend toggles and fallback posture.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              {Object.entries(diagnostics).map(([key, value]) => (
                <div key={key} className="flex items-center justify-between border-b pb-2 last:border-b-0">
                  <span className="font-medium">{key.replaceAll("_", " ")}</span>
                  <span className="text-muted-foreground">{String(value)}</span>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
