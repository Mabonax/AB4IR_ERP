import { Head, Link } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

export default function FacilitatorShow({
  facilitator,
  canManageFacilitators,
}: {
  facilitator: any;
  canManageFacilitators: boolean;
}) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Facilitators", href: facilitators.index() },
    { title: facilitator.full_name ?? "Facilitator", href: facilitators.show(facilitator.id) },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={facilitator.full_name ?? "Facilitator"} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Facilitator file</div>
            <h1 className="text-2xl font-semibold tracking-tight">{facilitator.full_name ?? "-"}</h1>
            <p className="mt-1 text-sm text-muted-foreground">Linked facilitator profile and account details.</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link href={facilitators.index().url}>
              <Button variant="outline">Back to Facilitators</Button>
            </Link>
            {canManageFacilitators ? (
              <Link href={facilitators.edit(facilitator.id).url}>
                <Button className="bg-red-600 text-white hover:bg-red-700">Edit Facilitator</Button>
              </Link>
            ) : null}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Core profile</CardTitle>
              <CardDescription>Current operational details used across facilitator workflows.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">First Name</div><div className="mt-1 font-medium">{facilitator.name ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Surname</div><div className="mt-1 font-medium">{facilitator.surname ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Email</div><div className="mt-1 font-medium">{facilitator.email ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Cell</div><div className="mt-1 font-medium">{facilitator.cell ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Province</div><div className="mt-1 font-medium">{facilitator.province_name ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Specialization</div><div className="mt-1 font-medium">{facilitator.specialization ?? "-"}</div></div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Extended details</CardTitle>
              <CardDescription>Optional identity and address details when they have been captured.</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Date of Birth</div><div className="mt-1 font-medium">{facilitator.dob ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">ID Number</div><div className="mt-1 font-medium">{facilitator.id_number ?? "-"}</div></div>
              <div className="sm:col-span-2"><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Address</div><div className="mt-1 whitespace-pre-wrap font-medium">{facilitator.address ?? "-"}</div></div>
              <div><div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Linked User ID</div><div className="mt-1 font-medium">{facilitator.user_id ?? "-"}</div></div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
