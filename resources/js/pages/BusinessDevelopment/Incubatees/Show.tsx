import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type Incubatee = {
  id: number;
  full_name: string;
  id_number: string;
  gender: string;
  mobile_number: string;
  email: string;
  company_name: string;
  company_registration_number: string;
  position_in_company: string | null;
  majority_shareholding: string | null;
  current_number_of_employees: number;
  physical_address: string | null;
  website_address: string | null;
  years_in_operation: number;
  province_name: string | null;
  has_business_plan: boolean;
  relevant_skill_set: string;
  technology_product_service: string;
  technology_stage_of_development: string;
  status: "active" | "inactive";
  incubated_date: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export default function BdsIncubateeShow({
  incubatee,
}: {
  incubatee: Incubatee | { data: Incubatee };
}) {
  const incubateeData: Incubatee =
    incubatee && typeof incubatee === "object" && "data" in incubatee
      ? incubatee.data
      : (incubatee as Incubatee);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Incubatees", href: "/business-development/incubatees" },
    { title: incubateeData.full_name, href: `/business-development/incubatees/${incubateeData.id}` },
  ];

  const detailRows: Array<{ label: string; value: string | number | null | undefined }> = [
    { label: "Full Name", value: incubateeData.full_name },
    { label: "ID Number", value: incubateeData.id_number },
    { label: "Gender", value: incubateeData.gender },
    { label: "Mobile Number", value: incubateeData.mobile_number },
    { label: "Email", value: incubateeData.email },
    { label: "Company Name", value: incubateeData.company_name },
    { label: "Company Registration Number", value: incubateeData.company_registration_number },
    { label: "Position in Company", value: incubateeData.position_in_company },
    { label: "Majority Shareholding", value: incubateeData.majority_shareholding },
    { label: "Current Employees", value: incubateeData.current_number_of_employees },
    { label: "Physical Address", value: incubateeData.physical_address },
    { label: "Website Address", value: incubateeData.website_address },
    { label: "Years in Operation", value: incubateeData.years_in_operation },
    { label: "Province", value: incubateeData.province_name },
    { label: "Has Business Plan", value: incubateeData.has_business_plan ? "Yes" : "No" },
    { label: "Relevant Skill Set", value: incubateeData.relevant_skill_set },
    { label: "Technology/Product/Service", value: incubateeData.technology_product_service },
    { label: "Stage of Development", value: incubateeData.technology_stage_of_development },
    { label: "Status", value: incubateeData.status },
    { label: "Incubated Date", value: incubateeData.incubated_date },
    { label: "Created At", value: incubateeData.created_at },
    { label: "Updated At", value: incubateeData.updated_at },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Incubatee: ${incubateeData.full_name}`} />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Incubatee Details</h1>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <Link
              href="/business-development/incubatees"
              className="rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Back to List
            </Link>
          </div>
        </div>

        <section className="rounded-xl border bg-card shadow-sm">
          <div className="grid gap-0 md:grid-cols-2">
            {detailRows.map((row) => (
              <div key={row.label} className="border-b p-3 md:border-r [&:nth-child(2n)]:md:border-r-0">
                <div className="text-xs uppercase tracking-wide text-muted-foreground">{row.label}</div>
                <div className="mt-1 text-sm font-medium">{row.value ?? "-"}</div>
              </div>
            ))}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
