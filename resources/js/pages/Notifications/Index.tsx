import { Head, Link, router, usePage } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Notifications", href: "/notifications" },
];

export default function NotificationsIndex({
  notifications,
}: {
  notifications: {
    data: Array<{
      id: string;
      type: string;
      title: string;
      message: string;
      url?: string | null;
      read_at?: string | null;
      created_at?: string | null;
    }>;
    links?: Array<{ url?: string | null; label: string; active: boolean }>;
  };
}) {
  const { props } = usePage<SharedData>();
  const flash = props.flash ?? {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Notifications" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Notifications</h1>
            <p className="text-sm text-muted-foreground">Review task, ticket, and workflow updates sent to your account.</p>
          </div>
          <Button onClick={() => router.post("/notifications/mark-all-read", {}, { preserveScroll: true })}>
            Mark All Read
          </Button>
        </div>

        {flash.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Inbox</CardTitle>
            <CardDescription>Unread items stay highlighted until you mark them as read.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {notifications.data.length === 0 ? (
              <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
                No notifications have been received yet.
              </div>
            ) : (
              notifications.data.map((notification) => (
                <div
                  key={notification.id}
                  className={`rounded-lg border p-4 ${notification.read_at ? "bg-card" : "border-orange-200 bg-orange-50/60"}`}
                >
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                      <div className="text-sm font-semibold">{notification.title}</div>
                      <div className="text-sm text-muted-foreground">{notification.message}</div>
                      <div className="text-xs text-muted-foreground">
                        {notification.created_at ?? "-"} | {notification.read_at ? "Read" : "Unread"}
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {notification.url ? (
                        <Button asChild variant="outline">
                          <Link href={`/notifications/${notification.id}/open`}>Open</Link>
                        </Button>
                      ) : null}
                      {!notification.read_at ? (
                        <Button
                          variant="outline"
                          onClick={() => router.post(`/notifications/${notification.id}/read`, {}, { preserveScroll: true })}
                        >
                          Mark Read
                        </Button>
                      ) : null}
                    </div>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
