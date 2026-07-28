import { Head } from "@inertiajs/react";

import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { staffNavItems } from "@/config/domain-nav/staff";
import { StaffTableConfig } from "@/config/tables/staff-table";
import AppLayout from "@/layouts/app-layout";
import staff from "@/routes/staff";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Staff", href: "/staff" },
  { title: "List", href: "/staff/list" },
];

/* =========================================================
| PAGE
========================================================= */

export default function StaffIndex({
  staffMembers,
  selectedDepartmentId,
}: {
  staffMembers: { data: any[] };
  selectedDepartmentId: number | null;
}) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Staff" />

      <div className="p-4 space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Staff</h1>
          <DomainNav items={staffNavItems} />

          <a
            href={staff.create.url(
              selectedDepartmentId
                ? { query: { department_id: selectedDepartmentId } }
                : undefined
            )}
            className="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
          >
            Add Staff Member
          </a>
        </div>

        <CustomTable
          columns={StaffTableConfig.columns}
          data={staffMembers.data}
          actions={[
            {
              icon: "Eye",
              label: "View staff member",
              onClick: (row) => {
                window.location.href = `/staff/${row.id}/profile`;
              },
            },
          ]}
        />
      </div>
    </AppLayout>
  );
}
