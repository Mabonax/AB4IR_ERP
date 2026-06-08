import { Head, router } from "@inertiajs/react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { organizationNavItems } from "@/config/domain-nav/organization";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type OrganizationDocument = {
  id: number;
  title: string;
  document_type: string;
  document_type_label: string;
  description: string | null;
  audience_scope: string;
  department_name: string | null;
  slot_key: string | null;
  is_active: boolean;
  effective_from: string | null;
  effective_until: string | null;
  file_name: string;
  published_by_name: string | null;
  created_at: string | null;
  download_url: string;
  can: {
    download: boolean;
    manage: boolean;
  };
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Document Vault", href: "/organization/documents" },
];

export default function OrganizationDocumentsIndex({
  documents,
  departments,
  users,
  documentTypes,
  slotOptions,
  can,
}: {
  documents: { data?: OrganizationDocument[] } | OrganizationDocument[];
  departments: Array<{ id: number; name: string }>;
  users: Array<{ id: number; name: string; email: string }>;
  documentTypes: Array<{ value: string; label: string }>;
  slotOptions: Array<{ value: string; label: string; document_type: string }>;
  can: { manage: boolean };
}) {
  const vaultDocuments = Array.isArray(documents) ? documents : (documents.data ?? []);
  const [documentType, setDocumentType] = useState("email_signature");
  const filteredSlots = useMemo(
    () => slotOptions.filter((slot) => slot.document_type === documentType),
    [slotOptions, documentType],
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Organization Document Vault" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Organization Document Vault</h1>
            <p className="text-sm text-muted-foreground">
              Official company documents, reusable design packs, and approved staff-facing assets.
            </p>
          </div>
          <DomainNav items={organizationNavItems} />
        </div>

        {can.manage ? (
          <section className="rounded-xl border bg-card p-4 shadow-sm">
            <h2 className="text-base font-semibold">Upload Official Document</h2>
            <form
              className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4"
              onSubmit={(event) => {
                event.preventDefault();
                router.post("/organization/documents", new FormData(event.currentTarget), {
                  forceFormData: true,
                  preserveScroll: true,
                });
              }}
            >
              <input name="title" placeholder="Document title" className="rounded-md border bg-background px-3 py-2 text-sm" />
              <select
                name="document_type"
                value={documentType}
                onChange={(event) => setDocumentType(event.target.value)}
                className="rounded-md border bg-background px-3 py-2 text-sm"
              >
                {documentTypes.map((documentType) => (
                  <option key={documentType.value} value={documentType.value}>{documentType.label}</option>
                ))}
              </select>
              <select name="slot_key" defaultValue="" className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No replacement slot</option>
                {filteredSlots.map((slot) => (
                  <option key={slot.value} value={slot.value}>{slot.label}</option>
                ))}
              </select>
              <select name="audience_scope" defaultValue="all_staff" className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="all_staff">All staff</option>
                <option value="department">Department</option>
                <option value="selected_users">Selected users</option>
              </select>
              <select name="department_id" defaultValue="" className="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">No department target</option>
                {departments.map((department) => (
                  <option key={department.id} value={department.id}>{department.name}</option>
                ))}
              </select>
              <input name="file" type="file" className="rounded-md border bg-background px-3 py-2 text-sm" />
              <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-muted-foreground">
                <input type="checkbox" name="replace_existing" value="1" />
                Replace current document in slot
              </label>
              <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-muted-foreground">
                <input type="checkbox" name="is_active" value="1" defaultChecked />
                Active immediately
              </label>
              <input name="effective_from" type="date" className="rounded-md border bg-background px-3 py-2 text-sm" />
              <input name="effective_until" type="date" className="rounded-md border bg-background px-3 py-2 text-sm" />
              <textarea name="description" rows={3} placeholder="What is this document for?" className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2 xl:col-span-4" />
              <div className="rounded-lg border p-3 md:col-span-2 xl:col-span-4">
                <div className="text-sm font-medium">Selected users</div>
                <div className="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                  {users.map((user) => (
                    <label key={user.id} className="flex items-center gap-2 text-xs text-muted-foreground">
                      <input type="checkbox" name="selected_user_ids[]" value={user.id} />
                      <span>{user.name}</span>
                    </label>
                  ))}
                </div>
              </div>
              <div className="md:col-span-2 xl:col-span-4">
                <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                  Upload To Vault
                </button>
              </div>
            </form>
          </section>
        ) : null}

        <section className="rounded-xl border bg-card p-4 shadow-sm">
          <h2 className="text-base font-semibold">Available Documents</h2>
          <div className="mt-4 space-y-3">
            {vaultDocuments.length === 0 ? (
              <div className="text-sm text-muted-foreground">No organization documents available to you yet.</div>
            ) : vaultDocuments.map((document) => (
              <div key={document.id} className="rounded-lg border p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="font-medium">{document.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {document.document_type_label} | {document.audience_scope.replaceAll("_", " ")}
                      {document.department_name ? ` | ${document.department_name}` : ""}
                      {document.slot_key ? ` | Slot ${document.slot_key}` : ""}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {document.is_active ? "Active" : "Inactive"}
                      {document.effective_from ? ` | Effective ${document.effective_from}` : ""}
                      {document.effective_until ? ` | Retires ${document.effective_until}` : ""}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                      {document.file_name} | {document.published_by_name ?? "-"} | {document.created_at ?? "-"}
                    </div>
                    {document.description ? <div className="mt-2 text-sm text-muted-foreground">{document.description}</div> : null}
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {document.can.download ? (
                      <a href={document.download_url} className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Download
                      </a>
                    ) : null}
                    {document.can.manage ? (
                      <>
                        <button
                          type="button"
                          onClick={() => router.post(`/organization/documents/${document.id}/lifecycle`, { action: document.is_active ? "deactivate" : "activate" }, { preserveScroll: true })}
                          className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        >
                          {document.is_active ? "Deactivate" : "Activate"}
                        </button>
                        <button
                          type="button"
                          onClick={() => router.post(`/organization/documents/${document.id}/lifecycle`, { action: "retire_now" }, { preserveScroll: true })}
                          className="rounded-md border border-amber-300 px-3 py-2 text-sm text-amber-700 hover:bg-amber-50"
                        >
                          Retire Now
                        </button>
                      </>
                    ) : null}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
