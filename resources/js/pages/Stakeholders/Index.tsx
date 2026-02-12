import { useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomTable } from "@/components/custom-table";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";

import { StakeholderModelFormConfig } from "@/config/forms/stakeholder-model-form";
import { StakeholderTableConfig } from "@/config/tables/stakeholder-table";

import stakeholders from "@/routes/stakeholders";
import { type BreadcrumbItem } from "@/types";

/* =========================================================
| BREADCRUMBS
========================================================= */

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Stakeholders", href: stakeholders.index() },
];

/* =========================================================
| PAGE
========================================================= */

export default function StakeholderIndex({
  stakeholders: stakeholderPagination,
}: {
  stakeholders: { data: any[] };
}) {
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit" | "view">("create");
  const [selectedStakeholder, setSelectedStakeholder] = useState<any | null>(null);

  const [deleteOpen, setDeleteOpen] = useState(false);
  const [stakeholderToDelete, setStakeholderToDelete] = useState<any | null>(null);
  const contactForm = useForm({
    full_name: "",
    email: "",
    contact_number: "",
    position: "",
  });

  const mappedStakeholderData = selectedStakeholder
    ? {
        "stakeholder.organization_name": selectedStakeholder.organization_name ?? "",
        "stakeholder.name": selectedStakeholder.name ?? "",
        "stakeholder.email": selectedStakeholder.email ?? "",
        "stakeholder.contact_number": selectedStakeholder.contact_number ?? "",
        "stakeholder.status": selectedStakeholder.status ?? "active",
        "contact.full_name": selectedStakeholder.contact?.full_name ?? "",
        "contact.email": selectedStakeholder.contact?.email ?? "",
        "contact.contact_number": selectedStakeholder.contact?.contact_number ?? "",
        "contact.position": selectedStakeholder.contact?.position ?? "",
      }
    : {};

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stakeholders" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between">
          <h1 className="text-xl font-semibold">Stakeholders</h1>

          <CustomModelForm
            addButton={StakeholderModelFormConfig.addButton}
            title="Add Stakeholder"
            description={StakeholderModelFormConfig.description}
            fields={StakeholderModelFormConfig.fields}
            submitRoute={stakeholders.store}
          />
        </div>

        <CustomTable
          columns={StakeholderTableConfig.columns}
          data={stakeholderPagination.data}
          actions={[
            {
              icon: "Eye",
              onClick: (row) => {
                setSelectedStakeholder(row);
                setMode("view");
                setOpen(true);
              },
            },
            {
              icon: "PencilIcon",
              onClick: (row) => {
                setSelectedStakeholder(row);
                setMode("edit");
                setOpen(true);
              },
            },
            {
              icon: "Users",
              onClick: (row) => {
                setSelectedStakeholder(row);
                setMode("view");
                setOpen(false);
              },
            },
            {
              icon: "Trash2",
              variant: "danger",
              onClick: (row) => {
                setStakeholderToDelete(row);
                setDeleteOpen(true);
              },
            },
          ]}
        />

        {selectedStakeholder && (
          <CustomModelForm
            hideTrigger
            open={open}
            onOpenChange={setOpen}
            title={mode === "view" ? "Stakeholder Details" : "Edit Stakeholder"}
            fields={StakeholderModelFormConfig.fields}
            mode={mode}
            initialData={mappedStakeholderData}
            submitRoute={stakeholders.update}
            routeParams={selectedStakeholder.id}
          />
        )}

        {stakeholderToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Stakeholder"
            submitRoute={stakeholders.destroy}
            routeParams={stakeholderToDelete.id}
          />
        )}

        {selectedStakeholder && (
          <div className="rounded-xl border bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold">Contacts: {selectedStakeholder.organization_name}</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Add one or more contact persons for this stakeholder organization.
            </p>

            <div className="mt-4 space-y-3">
              {selectedStakeholder.contacts?.length ? (
                selectedStakeholder.contacts.map((contact: any) => (
                  <div key={contact.id} className="flex items-center justify-between rounded-md border p-3">
                    <div>
                      <div className="font-medium">{contact.full_name || "Unnamed contact"}</div>
                      <div className="text-sm text-muted-foreground">
                        {contact.position || "-"} | {contact.email || "-"} | {contact.contact_number || "-"}
                      </div>
                    </div>
                    <button
                      type="button"
                      className="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700"
                      onClick={() => {
                        router.delete(`/stakeholders/${selectedStakeholder.id}/contacts/${contact.id}`, {
                          preserveScroll: true,
                        });
                      }}
                    >
                      Remove
                    </button>
                  </div>
                ))
              ) : (
                <div className="text-sm text-muted-foreground">No contacts added yet.</div>
              )}
            </div>

            <form
              className="mt-5 grid gap-3 md:grid-cols-2"
              onSubmit={(e) => {
                e.preventDefault();
                contactForm.post(`/stakeholders/${selectedStakeholder.id}/contacts`, {
                  preserveScroll: true,
                  onSuccess: () => {
                    contactForm.reset();
                  },
                });
              }}
            >
              <input
                type="text"
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Full name"
                value={contactForm.data.full_name}
                onChange={(e) => contactForm.setData("full_name", e.target.value)}
                required
              />
              <input
                type="email"
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Email (optional)"
                value={contactForm.data.email}
                onChange={(e) => contactForm.setData("email", e.target.value)}
              />
              <input
                type="text"
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Contact number"
                value={contactForm.data.contact_number}
                onChange={(e) => contactForm.setData("contact_number", e.target.value)}
                required
              />
              <input
                type="text"
                className="rounded-md border px-3 py-2 text-sm"
                placeholder="Position (optional)"
                value={contactForm.data.position}
                onChange={(e) => contactForm.setData("position", e.target.value)}
              />
              <div className="md:col-span-2">
                <button
                  type="submit"
                  className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                  disabled={contactForm.processing}
                >
                  Add Contact
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
