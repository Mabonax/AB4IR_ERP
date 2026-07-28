import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { MemberRegistryForm, type AssignmentOption, type MemberFormOptions } from "@/components/member-registry-form";
import { humanCapitalNavItems } from "@/config/domain-nav/human-capital";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Members", href: "/members" },
  { title: "Register", href: "/members/create" },
];

export default function CreateMember({
  options,
  assignmentOptions,
}: {
  options: MemberFormOptions;
  assignmentOptions: Record<string, AssignmentOption[]>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Register Member" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Register Member</h1>
            <p className="text-sm text-muted-foreground">Build the township human capital registry one verified profile at a time.</p>
          </div>
          <DomainNav items={humanCapitalNavItems} />
        </div>

        <MemberRegistryForm mode="create" options={options} assignmentOptions={assignmentOptions} />
      </div>
    </AppLayout>
  );
}
