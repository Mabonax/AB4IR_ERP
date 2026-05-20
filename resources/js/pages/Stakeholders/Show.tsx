import { useState } from "react";
import { Head, Link, router, useForm } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { CustomModelForm } from "@/components/custom-model-form";
import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { StakeholderModelFormConfig } from "@/config/forms/stakeholder-model-form";
import stakeholders from "@/routes/stakeholders";
import { type BreadcrumbItem } from "@/types";

export default function StakeholderShow({
  stakeholder,
}: {
  stakeholder: any;
}) {
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const contactForm = useForm({
    full_name: "",
    email: "",
    contact_number: "",
    position: "",
  });

  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Stakeholders", href: stakeholders.index() },
    { title: stakeholder.organization_name ?? stakeholder.name ?? "Stakeholder", href: stakeholders.show(stakeholder.id).url },
  ];

  const mappedStakeholderData = {
    "stakeholder.organization_name": stakeholder.organization_name ?? "",
    "stakeholder.name": stakeholder.name ?? "",
    "stakeholder.email": stakeholder.email ?? "",
    "stakeholder.contact_number": stakeholder.contact_number ?? "",
    "stakeholder.status": stakeholder.status ?? "active",
    "contact.full_name": stakeholder.contact?.full_name ?? "",
    "contact.email": stakeholder.contact?.email ?? "",
    "contact.contact_number": stakeholder.contact?.contact_number ?? "",
    "contact.position": stakeholder.contact?.position ?? "",
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={stakeholder.organization_name ?? "Stakeholder"} />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="space-y-1">
            <div className="text-sm text-muted-foreground">
              <Link href={stakeholders.index().url} className="hover:underline">
                Back to stakeholders
              </Link>
            </div>
            <h1 className="text-2xl font-semibold">{stakeholder.organization_name}</h1>
            <p className="text-sm text-muted-foreground">
              {stakeholder.name ?? "-"} | {stakeholder.email ?? "-"} | {stakeholder.contact_number ?? "-"}
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => setEditOpen(true)}
              className="rounded-md border border-orange-500 px-4 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Edit Stakeholder
            </button>
            <button
              type="button"
              onClick={() => setDeleteOpen(true)}
              className="rounded-md border border-red-600 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white"
            >
              Delete Stakeholder
            </button>
          </div>
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Stakeholder Profile</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Organization</dt>
                <dd>{stakeholder.organization_name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Representative</dt>
                <dd>{stakeholder.name ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Email</dt>
                <dd>{stakeholder.email ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Contact Number</dt>
                <dd>{stakeholder.contact_number ?? "-"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted-foreground">Status</dt>
                <dd className="capitalize">{stakeholder.status ?? "-"}</dd>
              </div>
            </dl>
          </section>

          <section className="rounded-xl border bg-card p-4 shadow-sm lg:col-span-2">
            <h2 className="text-base font-semibold">Stakeholder Contacts</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Contacts associated with this stakeholder organization.
            </p>

            <div className="mt-4 space-y-3">
              {stakeholder.contacts?.length ? (
                stakeholder.contacts.map((contact: any) => (
                  <div key={contact.id} className="flex flex-wrap items-center justify-between gap-3 rounded-md border p-3">
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
                        router.delete(`/stakeholders/${stakeholder.id}/contacts/${contact.id}`, {
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
                contactForm.post(`/stakeholders/${stakeholder.id}/contacts`, {
                  preserveScroll: true,
                  onSuccess: () => contactForm.reset(),
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
          </section>
        </div>

        <CustomModelForm
          hideTrigger
          open={editOpen}
          onOpenChange={setEditOpen}
          title="Edit Stakeholder"
          fields={StakeholderModelFormConfig.fields}
          mode="edit"
          initialData={mappedStakeholderData}
          submitRoute={stakeholders.update}
          routeParams={stakeholder.id}
        />

        <ConfirmDeleteModal
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          title="Delete Stakeholder"
          submitRoute={stakeholders.destroy}
          routeParams={stakeholder.id}
        />
      </div>
    </AppLayout>
  );
}
