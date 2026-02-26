import { Head, Link } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import AppLayout from "@/layouts/app-layout";
import businessDevelopment from "@/routes/business-development";
import { type BreadcrumbItem } from "@/types";

type Application = {
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
  application_date: string | null;
  assessment_status: "pending" | "accepted" | "rejected";
  assessor_name: string | null;
  assessed_at: string | null;
  pitch_scheduled_at: string | null;
  pitch_notes: string | null;
  adjudication_result: "incubated" | "rejected" | null;
  adjudicated_at: string | null;
};

export default function BdsApplicationShow({
  application,
}: {
  application: Application | { data: Application };
}) {
  const appData: Application =
    application && typeof application === "object" && "data" in application
      ? application.data
      : (application as Application);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Business Development", href: "/business-development" },
    { title: "Applications", href: businessDevelopment.applications.index().url },
    { title: appData.full_name, href: businessDevelopment.applications.show(appData.id).url },
  ];

  const detailRows: Array<{ label: string; value: string | number | null | undefined }> = [
    { label: "Full Name", value: appData.full_name },
    { label: "ID Number", value: appData.id_number },
    { label: "Gender", value: appData.gender },
    { label: "Mobile Number", value: appData.mobile_number },
    { label: "Email", value: appData.email },
    { label: "Company Name", value: appData.company_name },
    { label: "Company Registration Number", value: appData.company_registration_number },
    { label: "Position in Company", value: appData.position_in_company },
    { label: "Majority Shareholding", value: appData.majority_shareholding },
    { label: "Current Employees", value: appData.current_number_of_employees },
    { label: "Physical Address", value: appData.physical_address },
    { label: "Website Address", value: appData.website_address },
    { label: "Years in Operation", value: appData.years_in_operation },
    { label: "Province", value: appData.province_name },
    { label: "Has Business Plan", value: appData.has_business_plan ? "Yes" : "No" },
    { label: "Relevant Skill Set", value: appData.relevant_skill_set },
    { label: "Technology/Product/Service", value: appData.technology_product_service },
    { label: "Stage of Development", value: appData.technology_stage_of_development },
    { label: "Application Date", value: appData.application_date },
    { label: "Assessment Status", value: appData.assessment_status },
    { label: "Assessed By", value: appData.assessor_name },
    { label: "Assessed At", value: appData.assessed_at },
    { label: "Pitch Scheduled At", value: appData.pitch_scheduled_at },
    { label: "Pitch Notes", value: appData.pitch_notes },
    { label: "Adjudication Result", value: appData.adjudication_result },
    { label: "Adjudicated At", value: appData.adjudicated_at },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Applicant: ${appData.full_name}`} />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Applicant Details</h1>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />
            <Link
              href={businessDevelopment.applications.index().url}
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
