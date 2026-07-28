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
  { title: "Memory", href: "/intelligence/memory" },
];

export default function IntelligenceMemory({
  memoryRecords,
}: {
  memoryRecords: Array<Record<string, unknown>>;
}) {
  const form = useForm({
    subject_type: "organization",
    subject_id: 1,
    memory_type: "fact",
    content: "",
    confidence_score: 0.75,
    visibility: "organization",
    expires_at: "",
    metadata: {},
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Memory Runtime" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Memory Runtime</h1>
            <p className="text-sm text-muted-foreground">Curated enterprise-safe memory with review, confidence, recency, and visibility controls.</p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
          <Card>
            <CardHeader>
              <CardTitle>Create Memory Record</CardTitle>
              <CardDescription>Memories are not auto-injected unless the agent and retrieval filters allow them.</CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-4" onSubmit={(event) => {
                event.preventDefault();
                form.post("/intelligence/memory");
              }}>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2"><Label htmlFor="subject_type">Subject Type</Label><Input id="subject_type" value={form.data.subject_type} onChange={(event) => form.setData("subject_type", event.target.value)} /></div>
                  <div className="space-y-2"><Label htmlFor="subject_id">Subject ID</Label><Input id="subject_id" type="number" value={form.data.subject_id} onChange={(event) => form.setData("subject_id", Number(event.target.value))} /></div>
                </div>
                <div className="space-y-2"><Label htmlFor="content">Content</Label><textarea id="content" value={form.data.content} onChange={(event) => form.setData("content", event.target.value)} className="min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm" /></div>
                <Button type="submit" disabled={form.processing}>Store Memory</Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Memory Review Queue</CardTitle>
              <CardDescription>Review and approve selective memory records before they can become trusted enterprise context.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {memoryRecords.map((memory, index) => (
                <div key={String(memory.id ?? index)} className="border p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <div className="font-semibold">{String(memory.memory_type)} for {String(memory.subject_type)} #{String(memory.subject_id)}</div>
                      <div className="text-xs text-muted-foreground">confidence {String(memory.confidence_score)} • visibility {String(memory.visibility)}</div>
                    </div>
                    {!memory.reviewed_at ? (
                      <Button type="button" variant="outline" onClick={() => router.post(`/intelligence/memory/${memory.id}/review`)}>
                        Review
                      </Button>
                    ) : null}
                  </div>
                  <p className="mt-3 text-sm text-muted-foreground">{String(memory.content)}</p>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
