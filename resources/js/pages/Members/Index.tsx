import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";

import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { humanCapitalNavItems } from "@/config/domain-nav/human-capital";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [{ title: "Members", href: "/members" }];

type MemberRow = {
  id: number;
  full_name: string;
  id_number: string;
  member_type: string;
  status: string;
  township_name?: string | null;
  branch_name?: string | null;
  province_name?: string | null;
  employment?: { employment_status?: string | null } | null;
  qualifications?: unknown[];
  skills?: unknown[];
};

export default function MembersIndex({
  members,
  filters,
  options,
}: {
  members: {
    data: MemberRow[];
    meta: { current_page: number; last_page: number; total: number };
  };
  filters: { search?: string; member_type?: string; status?: string; township_id?: string };
  options: {
    memberTypes: string[];
    memberStatuses: string[];
    townships: Array<{ id: number; name: string }>;
  };
}) {
  const [search, setSearch] = useState(filters.search ?? "");
  const [memberType, setMemberType] = useState(filters.member_type ?? "");
  const [status, setStatus] = useState(filters.status ?? "");
  const [townshipId, setTownshipId] = useState(filters.township_id ?? "");

  const applyFilters = () => {
    router.get("/members", { search, member_type: memberType, status, township_id: townshipId }, { preserveState: true, replace: true });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Members Registry" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="space-y-1">
            <h1 className="text-xl font-semibold">Members Registry</h1>
            <p className="text-sm text-muted-foreground">Township-level registry for members, volunteers, graduates, and community stakeholders.</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button asChild>
              <Link href="/members/create">Register Member</Link>
            </Button>
            <DomainNav items={humanCapitalNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Total Registered</CardTitle>
              <CardDescription>Visible registry size</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{members.meta.total}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Current Page</CardTitle>
              <CardDescription>Paginated registry slice</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{members.meta.current_page}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Last Page</CardTitle>
              <CardDescription>Available pagination range</CardDescription>
            </CardHeader>
            <CardContent className="text-2xl font-semibold">{members.meta.last_page}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-5">
            <input className="rounded-md border bg-card px-3 py-2 text-sm" placeholder="Search name, ID, email, phone" value={search} onChange={(event) => setSearch(event.target.value)} />
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={memberType} onChange={(event) => setMemberType(event.target.value)}>
              <option value="">All member types</option>
              {options.memberTypes.map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={status} onChange={(event) => setStatus(event.target.value)}>
              <option value="">All statuses</option>
              {options.memberStatuses.map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={townshipId} onChange={(event) => setTownshipId(event.target.value)}>
              <option value="">All townships</option>
              {options.townships.map((value) => (
                <option key={value.id} value={value.id}>
                  {value.name}
                </option>
              ))}
            </select>
            <Button type="button" onClick={applyFilters}>
              Apply Filters
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Registry</CardTitle>
            <CardDescription>Profile counts include qualifications, skills, and employment status captured on each member.</CardDescription>
          </CardHeader>
          <CardContent>
            <CustomTable
              columns={[
                { key: "full_name", label: "Member" },
                { key: "id_number", label: "ID Number" },
                { key: "member_type", label: "Type" },
                { key: "status", label: "Status" },
                { key: "province_name", label: "Province" },
                { key: "township_name", label: "Township" },
                { key: "branch_name", label: "Branch" },
                {
                  key: "employment",
                  label: "Employment",
                  render: (row: MemberRow) => row.employment?.employment_status ?? "Not captured",
                },
                {
                  key: "qualifications",
                  label: "Qualifications",
                  render: (row: MemberRow) => String(row.qualifications?.length ?? 0),
                },
                {
                  key: "skills",
                  label: "Skills",
                  render: (row: MemberRow) => String(row.skills?.length ?? 0),
                },
                { key: "actions", label: "Actions", isAction: true },
              ]}
              data={members.data}
              actions={[
                {
                  icon: "PencilIcon",
                  href: (row) => `/members/${row.id}/edit`,
                },
              ]}
            />
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
