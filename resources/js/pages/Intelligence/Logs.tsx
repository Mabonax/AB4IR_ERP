import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { intelligenceNavItems } from "@/config/domain-nav/intelligence";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Intelligence", href: "/intelligence" },
  { title: "Tool Logs", href: "/intelligence/tool-logs" },
];

export default function IntelligenceLogs({
  logs,
}: {
  logs: Array<Record<string, unknown>>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Tool Logs" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Tool Logs</h1>
            <p className="text-sm text-muted-foreground">Audit-safe tool execution history with user, agent, approval, and status context.</p>
          </div>
          <DomainNav items={intelligenceNavItems} />
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Recent Executions</CardTitle>
            <CardDescription>Most recent 100 tool execution attempts.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {logs.map((log, index) => (
              <div key={String(log.id ?? index)} className="border p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="font-semibold">{String(log.tool ?? "Unknown Tool")}</div>
                  <div className="text-xs text-muted-foreground">{String(log.status)} • {String(log.duration_ms ?? 0)}ms</div>
                </div>
                <div className="mt-2 text-xs text-muted-foreground">
                  Agent {String(log.agent ?? "-")} • User {String(log.user ?? "-")} • Approved {String(log.approved)}
                </div>
                {log.error_message ? <p className="mt-2 text-sm text-red-600">{String(log.error_message)}</p> : null}
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
