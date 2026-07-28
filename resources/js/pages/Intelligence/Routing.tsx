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
  { title: "Model Routing", href: "/intelligence/model-routing" },
];

export default function IntelligenceRouting({
  rules,
}: {
  rules: Array<Record<string, unknown>>;
}) {
  const form = useForm({
    provider: "stub",
    model: "stub-chat-v1",
    capability: "chat",
    priority: 1,
    max_context_tokens: 8000,
    cost_tier: "stub",
    enabled: true,
    fallback_provider: "stub",
    fallback_model: "stub-chat-v1",
    metadata: {},
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Model Routing" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Model Routing</h1>
            <p className="text-sm text-muted-foreground">Capability-aware provider/model routing with explicit fallback posture.</p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
          <Card>
            <CardHeader>
              <CardTitle>Add Routing Rule</CardTitle>
              <CardDescription>Order rules by priority and keep a deterministic fallback path.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4" onSubmit={(event) => {
                event.preventDefault();
                form.post("/intelligence/model-routing");
              }}>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2"><Label htmlFor="provider">Provider</Label><Input id="provider" value={form.data.provider} onChange={(event) => form.setData("provider", event.target.value)} /></div>
                  <div className="space-y-2"><Label htmlFor="model">Model</Label><Input id="model" value={form.data.model} onChange={(event) => form.setData("model", event.target.value)} /></div>
                </div>
                <div className="space-y-2"><Label htmlFor="capability">Capability</Label><Input id="capability" value={form.data.capability} onChange={(event) => form.setData("capability", event.target.value)} /></div>
                <Button type="submit" disabled={form.processing}>Create Routing Rule</Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Routing Rules</CardTitle>
              <CardDescription>Current capability resolution order.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {rules.map((rule, index) => (
                <div key={String(rule.id ?? index)} className="border p-4">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="font-semibold">{String(rule.capability)} → {String(rule.provider)} / {String(rule.model)}</div>
                    <div className="text-xs text-muted-foreground">priority {String(rule.priority)} • enabled {String(rule.enabled)}</div>
                  </div>
                  <div className="mt-2 text-sm text-muted-foreground">
                    fallback {String(rule.fallback_provider)} / {String(rule.fallback_model)}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
