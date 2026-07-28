import { Head } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { MemberRegistryForm, type AssignmentOption, type MemberFormOptions } from "@/components/member-registry-form";
import { humanCapitalNavItems } from "@/config/domain-nav/human-capital";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Members", href: "/members" },
  { title: "Edit Member", href: "/members/edit" },
];

export default function EditMember({
  member,
  options,
  assignmentOptions,
}: {
  member: Record<string, unknown>;
  options: MemberFormOptions;
  assignmentOptions: Record<string, AssignmentOption[]>;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Edit Member" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Edit Member</h1>
            <p className="text-sm text-muted-foreground">Maintain the detailed profile, skills inventory, and opportunity-readiness data for this member.</p>
          </div>
          <DomainNav items={humanCapitalNavItems} />
        </div>

        <MemberRegistryForm mode="edit" initialData={member as never} options={options} assignmentOptions={assignmentOptions} memberId={Number(member.id)} />
      </div>
    </AppLayout>
  );
}
