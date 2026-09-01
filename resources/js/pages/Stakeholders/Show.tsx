import { useState } from "react";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { Building2, Mail, Phone, Plus, Trash2, UserRound, UsersRound } from "lucide-react";

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

      <div className="space-y-6 bg-white p-4 text-slate-950 md:p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-center gap-4">
            <div className="grid h-16 w-16 place-items-center rounded-full bg-red-50 text-red-600">
              <Building2 className="h-8 w-8" />
            </div>
            <div className="space-y-1">
              <div className="text-sm text-slate-500">
              <Link href={stakeholders.index().url} className="hover:underline">
                Back to stakeholders
              </Link>
              </div>
              <h1 className="text-3xl font-semibold tracking-normal">{stakeholder.organization_name}</h1>
              <p className="text-sm text-slate-500">
                {stakeholder.name ?? "-"} | {stakeholder.email ?? "-"} | {stakeholder.contact_number ?? "-"}
              </p>
            </div>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => setEditOpen(true)}
              className="rounded-lg border border-orange-500 px-4 py-2 text-sm font-semibold text-orange-600 hover:bg-orange-500 hover:text-white"
            >
              Edit Stakeholder
            </button>
            <button
              type="button"
              onClick={() => setDeleteOpen(true)}
              className="inline-flex items-center gap-2 rounded-lg border border-red-600 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-600 hover:text-white"
            >
              <Trash2 className="h-4 w-4" />
              Delete Stakeholder
            </button>
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {[
            { label: "Organization", value: stakeholder.organization_name ?? "-", icon: Building2, tone: "bg-red-50 text-red-600" },
            { label: "Representative", value: stakeholder.name ?? "-", icon: UserRound, tone: "bg-orange-50 text-orange-600" },
            { label: "Contacts", value: stakeholder.contacts?.length ?? 0, icon: UsersRound, tone: "bg-blue-50 text-blue-600" },
            { label: "Status", value: stakeholder.status ?? "active", icon: Plus, tone: "bg-emerald-50 text-emerald-600" },
          ].map((item) => (
            <section key={item.label} className="rounded-lg border bg-white p-5 shadow-sm">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="text-sm font-medium text-slate-500">{item.label}</p>
                  <p className="mt-2 truncate text-xl font-semibold capitalize">{item.value}</p>
                </div>
                <span className={`inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full ${item.tone}`}>
                  <item.icon className="h-5 w-5" />
                </span>
              </div>
            </section>
          ))}
        </div>

        <div className="grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
          <section className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Stakeholder Profile</h2>
            <p className="text-sm text-slate-500">Core account and contact details.</p>
            <dl className="mt-5 space-y-4 text-sm">
              {[
                ["Organization", stakeholder.organization_name],
                ["Representative", stakeholder.name],
                ["Email", stakeholder.email],
                ["Contact Number", stakeholder.contact_number],
                ["Status", stakeholder.status],
              ].map(([label, value]) => (
                <div key={label} className="flex items-center justify-between gap-4 border-b pb-3 last:border-b-0">
                  <dt className="text-slate-500">{label}</dt>
                  <dd className="text-right font-medium capitalize">{value ?? "-"}</dd>
                </div>
              ))}
            </dl>
          </section>

          <section className="rounded-lg border bg-white p-5 shadow-sm">
            <h2 className="text-lg font-semibold">Stakeholder Contacts</h2>
            <p className="mt-1 text-sm text-slate-500">
              Contacts associated with this stakeholder organization.
            </p>

            <div className="mt-4 space-y-3">
              {stakeholder.contacts?.length ? (
                stakeholder.contacts.map((contact: any) => (
                  <div key={contact.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4">
                    <div className="flex items-start gap-3">
                      <div className="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600">
                        <UserRound className="h-5 w-5" />
                      </div>
                      <div>
                      <div className="font-medium">{contact.full_name || "Unnamed contact"}</div>
                      <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                        <span className="inline-flex items-center gap-1"><Mail className="h-3.5 w-3.5" />{contact.email || "-"}</span>
                        <span className="inline-flex items-center gap-1"><Phone className="h-3.5 w-3.5" />{contact.contact_number || "-"}</span>
                        <span>{contact.position || "-"}</span>
                      </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      className="rounded-lg bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-700"
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
                <div className="rounded-lg border border-dashed p-6 text-sm text-slate-500">No contacts added yet.</div>
              )}
            </div>

            <form
              className="mt-5 grid gap-3 rounded-lg border bg-slate-50 p-4 md:grid-cols-2"
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
                className="rounded-lg border px-3 py-2 text-sm"
                placeholder="Full name"
                value={contactForm.data.full_name}
                onChange={(e) => contactForm.setData("full_name", e.target.value)}
                required
              />
              <input
                type="email"
                className="rounded-lg border px-3 py-2 text-sm"
                placeholder="Email (optional)"
                value={contactForm.data.email}
                onChange={(e) => contactForm.setData("email", e.target.value)}
              />
              <input
                type="text"
                className="rounded-lg border px-3 py-2 text-sm"
                placeholder="Contact number"
                value={contactForm.data.contact_number}
                onChange={(e) => contactForm.setData("contact_number", e.target.value)}
                required
              />
              <input
                type="text"
                className="rounded-lg border px-3 py-2 text-sm"
                placeholder="Position (optional)"
                value={contactForm.data.position}
                onChange={(e) => contactForm.setData("position", e.target.value)}
              />
              <div className="md:col-span-2">
                <button
                  type="submit"
                  className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                  disabled={contactForm.processing}
                >
                  <Plus className="h-4 w-4" />
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
