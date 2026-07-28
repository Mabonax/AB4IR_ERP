import { Head, Link, router } from "@inertiajs/react";
import { useMemo, useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomModelForm } from "@/components/custom-model-form";
import { CustomTable } from "@/components/custom-table";
import { DomainNav } from "@/components/domain-nav";
import { businessDevelopmentNavItems } from "@/config/domain-nav/business-development";
import { BdsIncubateeModelFormConfig } from "@/config/forms/bds-incubatee-model-form";
import { BdsIncubateeTableConfig } from "@/config/tables/bds-incubatee-table";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Business Development", href: "/business-development" },
  { title: "Incubatees", href: "/business-development/incubatees" },
];

type IncubateeRow = {
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
  province_id: number | null;
  has_business_plan: boolean;
  relevant_skill_set: string;
  technology_product_service: string;
  technology_stage_of_development: string;
  status: "active" | "inactive";
  incubated_date: string | null;
};

export default function BdsIncubateesIndex({
  incubatees,
  provinces,
  filters,
}: {
  incubatees: {
    data: IncubateeRow[];
    links?: unknown;
    meta?: {
      total?: number;
      links?: Array<{ url: string | null; label: string; active: boolean }>;
    };
  };
  provinces: { id: number; name: string }[];
  filters: { search?: string; per_page?: number };
}) {
  const [open, setOpen] = useState(false);
  const [selectedIncubatee, setSelectedIncubatee] = useState<IncubateeRow | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [incubateeToDelete, setIncubateeToDelete] = useState<IncubateeRow | null>(null);
  const [search, setSearch] = useState(filters.search ?? "");
  const [perPage, setPerPage] = useState(String(filters.per_page ?? 15));

  const queryParams = useMemo(
    () => ({
      search: search.trim(),
      per_page: Number(perPage) || 15,
    }),
    [search, perPage]
  );

  const paginationLinks = useMemo(() => {
    if (Array.isArray(incubatees.links)) {
      return incubatees.links as Array<{ url: string | null; label: string; active: boolean }>;
    }

    if (Array.isArray(incubatees.meta?.links)) {
      return incubatees.meta.links;
    }

    return [];
  }, [incubatees.links, incubatees.meta?.links]);

  const mappedData = selectedIncubatee
    ? {
        full_name: selectedIncubatee.full_name ?? "",
        id_number: selectedIncubatee.id_number ?? "",
        gender: selectedIncubatee.gender ?? "",
        mobile_number: selectedIncubatee.mobile_number ?? "",
        email: selectedIncubatee.email ?? "",
        company_name: selectedIncubatee.company_name ?? "",
        company_registration_number: selectedIncubatee.company_registration_number ?? "",
        position_in_company: selectedIncubatee.position_in_company ?? "",
        majority_shareholding: selectedIncubatee.majority_shareholding ?? "",
        current_number_of_employees: selectedIncubatee.current_number_of_employees ?? "",
        physical_address: selectedIncubatee.physical_address ?? "",
        website_address: selectedIncubatee.website_address ?? "",
        years_in_operation: selectedIncubatee.years_in_operation ?? "",
        province_id: selectedIncubatee.province_id ? String(selectedIncubatee.province_id) : "",
        has_business_plan: selectedIncubatee.has_business_plan ? "1" : "0",
        relevant_skill_set: selectedIncubatee.relevant_skill_set ?? "",
        technology_product_service: selectedIncubatee.technology_product_service ?? "",
        technology_stage_of_development: selectedIncubatee.technology_stage_of_development ?? "",
        status: selectedIncubatee.status ?? "active",
        incubated_date: selectedIncubatee.incubated_date ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="BDS Incubatees" />

      <div className="space-y-4 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-semibold">Incubatees</h1>
          <div className="flex items-center gap-2">
            <DomainNav items={businessDevelopmentNavItems} />

            <CustomModelForm
              addButton={BdsIncubateeModelFormConfig.addButton}
              title="Add Incubatee"
              description={BdsIncubateeModelFormConfig.description}
              fields={BdsIncubateeModelFormConfig.fields}
              submitRoute={() => ({ url: "/business-development/incubatees", method: "post" })}
              options={{ provinces }}
            />
          </div>
        </div>

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="flex flex-wrap items-end gap-3">
            <div className="min-w-[220px] flex-1">
              <label className="mb-1 block text-sm font-medium">Search</label>
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.currentTarget.value)}
                placeholder="Name, company, ID, email, mobile"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Per page</label>
              <select
                value={perPage}
                onChange={(e) => setPerPage(e.currentTarget.value)}
                className="rounded-md border bg-background px-3 py-2 text-sm"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>
            <button
              type="button"
              onClick={() => router.get("/business-development/incubatees", queryParams, { preserveState: true })}
              className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
            >
              Apply
            </button>
            <button
              type="button"
              onClick={() => {
                setSearch("");
                setPerPage("15");
                router.get("/business-development/incubatees", { search: "", per_page: 15 }, { preserveState: true });
              }}
              className="rounded-md border border-red-500 px-4 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white"
            >
              Reset
            </button>
          </div>
        </section>

        <CustomTable
          columns={BdsIncubateeTableConfig.columns}
          data={incubatees.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                router.visit(`/business-development/incubatees/${row.id}`);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedIncubatee(row);
                setOpen(true);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setIncubateeToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        <div className="flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-muted-foreground">
            Showing {incubatees.data.length} of {incubatees.meta?.total ?? incubatees.data.length}
          </p>
          <div className="flex flex-wrap gap-2">
            {paginationLinks.map((link, index) =>
              link.url ? (
                <Link
                  key={`${link.label}-${index}`}
                  href={link.url}
                  preserveState
                  preserveScroll
                  className={`rounded-md border px-3 py-1.5 text-sm ${
                    link.active
                      ? "border-red-600 bg-red-600 text-white"
                      : "border-red-500 text-red-600 hover:bg-red-500 hover:text-white"
                  }`}
                  dangerouslySetInnerHTML={{ __html: link.label }}
                />
              ) : (
                <span
                  key={`${link.label}-${index}`}
                  className="rounded-md border border-muted px-3 py-1.5 text-sm text-muted-foreground"
                  dangerouslySetInnerHTML={{ __html: link.label }}
                />
              )
            )}
          </div>
        </div>

        {selectedIncubatee && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title="Edit Incubatee"
            fields={BdsIncubateeModelFormConfig.fields}
            mode="edit"
            initialData={mappedData}
            submitRoute={(id) => ({ url: `/business-development/incubatees/${id}`, method: "put" })}
            routeParams={selectedIncubatee.id}
            options={{ provinces }}
          />
        )}

        {incubateeToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Incubatee"
            submitRoute={(id) => ({ url: `/business-development/incubatees/${id}`, method: "delete" })}
            routeParams={incubateeToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
