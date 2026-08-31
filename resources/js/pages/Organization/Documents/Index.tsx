import { Head, router, usePage } from "@inertiajs/react";
import { ChevronDown, ChevronRight, Download, Eye, File, FileSpreadsheet, FileText, Folder, FolderOpen, Presentation, Search, Trash2, X } from "lucide-react";
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
  mime_type: string | null;
  file_size: number | null;
  published_by_name: string | null;
  created_at: string | null;
  download_url: string;
  preview_url: string;
  can_preview: boolean;
  can: {
    download: boolean;
    manage: boolean;
  };
};

type DocumentTypeOption = {
  value: string;
  label: string;
};

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Official Vault", href: "/organization/documents" },
];

function unwrapCollection<T>(value: { data?: T[] } | T[]): T[] {
  return Array.isArray(value) ? value : (value.data ?? []);
}

function extension(fileName: string): string {
  return fileName.split(".").pop()?.toLowerCase() ?? "";
}

function VaultFileIcon({ document }: { document: Pick<OrganizationDocument, "file_name" | "mime_type"> }) {
  const ext = extension(document.file_name);

  if (["xls", "xlsx"].includes(ext) || document.mime_type?.includes("spreadsheet")) {
    return <FileSpreadsheet className="h-5 w-5 text-emerald-600" />;
  }

  if (["ppt", "pptx"].includes(ext) || document.mime_type?.includes("presentation")) {
    return <Presentation className="h-5 w-5 text-orange-600" />;
  }

  if (["pdf", "doc", "docx"].includes(ext) || document.mime_type?.includes("pdf") || document.mime_type?.includes("word")) {
    return <FileText className="h-5 w-5 text-blue-600" />;
  }

  return <File className="h-5 w-5 text-slate-500" />;
}

function formatAudience(value: string): string {
  return value.replaceAll("_", " ");
}

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
  documentTypes: DocumentTypeOption[];
  slotOptions: Array<{ value: string; label: string; document_type: string }>;
  can: { manage: boolean };
}) {
  const page = usePage<{ props: { errors?: Record<string, string> } }>();
  const errors = page.props.errors ?? {};
  const vaultDocuments = unwrapCollection(documents);
  const [documentType, setDocumentType] = useState(documentTypes[0]?.value ?? "");
  const [activeFolder, setActiveFolder] = useState<string>("all");
  const [query, setQuery] = useState("");
  const [audienceFilter, setAudienceFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");
  const [publishOpen, setPublishOpen] = useState(false);
  const [previewDocumentId, setPreviewDocumentId] = useState<number | null>(null);
  const [vaultUploadProgress, setVaultUploadProgress] = useState<number | null>(null);

  const filteredSlots = useMemo(
    () => slotOptions.filter((slot) => slot.document_type === documentType),
    [slotOptions, documentType],
  );

  const folderCounts = useMemo(() => {
    return vaultDocuments.reduce<Record<string, number>>((counts, document) => {
      counts[document.document_type] = (counts[document.document_type] ?? 0) + 1;

      return counts;
    }, {});
  }, [vaultDocuments]);

  const filteredDocuments = useMemo(() => {
    const search = query.trim().toLowerCase();

    return vaultDocuments.filter((document) => {
      const matchesFolder = activeFolder === "all" || document.document_type === activeFolder;
      const matchesAudience = audienceFilter === "all" || document.audience_scope === audienceFilter;
      const matchesStatus = statusFilter === "all"
        || (statusFilter === "active" && document.is_active)
        || (statusFilter === "inactive" && !document.is_active);
      const matchesSearch = !search
        || document.title.toLowerCase().includes(search)
        || document.file_name.toLowerCase().includes(search)
        || document.document_type_label.toLowerCase().includes(search)
        || (document.description?.toLowerCase().includes(search) ?? false);

      return matchesFolder && matchesAudience && matchesStatus && matchesSearch;
    });
  }, [activeFolder, audienceFilter, query, statusFilter, vaultDocuments]);

  const previewDocument = useMemo(
    () => vaultDocuments.find((document) => document.id === previewDocumentId) ?? null,
    [previewDocumentId, vaultDocuments],
  );

  const uploadErrors = ["title", "document_type", "audience_scope", "department_id", "slot_key", "file", "selected_user_ids"]
    .map((key) => errors[key])
    .filter(Boolean);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Official Vault" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Official Organization Vault</h1>
            <p className="text-sm text-muted-foreground">
              Approved institutional assets live here. This is the formal publication surface, not the working file library.
            </p>
          </div>
          <DomainNav items={organizationNavItems} />
        </div>

        {can.manage ? (
          <section className="rounded-lg border bg-card shadow-sm">
            <button
              type="button"
              onClick={() => setPublishOpen((open) => !open)}
              className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
            >
              <span className="text-base font-semibold">Publish Approved Asset To Vault</span>
              {publishOpen ? <ChevronDown className="h-4 w-4 text-slate-500" /> : <ChevronRight className="h-4 w-4 text-slate-500" />}
            </button>

            {publishOpen ? (
              <form
                className="grid gap-3 border-t p-4 md:grid-cols-2 xl:grid-cols-4"
                onSubmit={(event) => {
                  event.preventDefault();
                  const form = event.currentTarget;
                  setVaultUploadProgress(0);
                  router.post("/organization/documents", new FormData(form), {
                    forceFormData: true,
                    preserveScroll: true,
                    onProgress: (progress) => setVaultUploadProgress(progress?.percentage ?? 0),
                    onFinish: () => setVaultUploadProgress(null),
                    onSuccess: () => {
                      form.reset();
                      setPublishOpen(false);
                    },
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
                  {documentTypes.map((type) => (
                    <option key={type.value} value={type.value}>{type.label}</option>
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
                <input
                  name="file"
                  type="file"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                  className="rounded-md border bg-background px-3 py-2 text-sm"
                />
                <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-muted-foreground">
                  <input type="checkbox" name="replace_existing" value="1" />
                  Replace current document in slot
                </label>
                <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-muted-foreground">
                  <input type="checkbox" name="is_active" value="1" defaultChecked />
                  Active immediately
                </label>
                <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                  Available from
                  <input name="effective_from" type="date" className="rounded-md border bg-background px-3 py-2 text-sm font-normal text-slate-900" />
                </label>
                <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                  Retire after
                  <input name="effective_until" type="date" className="rounded-md border bg-background px-3 py-2 text-sm font-normal text-slate-900" />
                </label>
                <textarea name="description" rows={3} placeholder="What is this document for?" className="rounded-md border bg-background px-3 py-2 text-sm md:col-span-2 xl:col-span-4" />

                <div className="rounded-lg border p-3 md:col-span-2 xl:col-span-4">
                  <div className="text-sm font-medium">Selected users</div>
                  <div className="mt-3 grid max-h-44 gap-2 overflow-auto md:grid-cols-2 xl:grid-cols-4">
                    {users.map((user) => (
                      <label key={user.id} className="flex items-center gap-2 text-xs text-muted-foreground">
                        <input type="checkbox" name="selected_user_ids[]" value={user.id} />
                        <span>{user.name}</span>
                      </label>
                    ))}
                  </div>
                </div>

                {uploadErrors.length > 0 ? (
                  <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 md:col-span-2 xl:col-span-4">
                    {uploadErrors.map((message) => (
                      <div key={message}>{message}</div>
                    ))}
                  </div>
                ) : null}

                {vaultUploadProgress !== null ? (
                  <div className="rounded-md border bg-slate-50 p-3 md:col-span-2 xl:col-span-4">
                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                      <span>Uploading vault document</span>
                      <span>{vaultUploadProgress}%</span>
                    </div>
                    <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                      <div
                        className="h-full rounded-full bg-slate-900 transition-all"
                        style={{ width: `${vaultUploadProgress}%` }}
                      />
                    </div>
                  </div>
                ) : null}

                <div className="flex flex-wrap gap-2 md:col-span-2 xl:col-span-4">
                  <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                    Upload To Vault
                  </button>
                  <button type="button" onClick={() => setPublishOpen(false)} className="rounded-md border px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Cancel
                  </button>
                </div>
              </form>
            ) : null}
          </section>
        ) : null}

        <section className="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">
          <div className="rounded-lg border bg-card p-4 shadow-sm">
            <div className="mb-3 text-sm font-semibold">Vault Folders</div>
            <div className="space-y-1">
              <button
                type="button"
                onClick={() => setActiveFolder("all")}
                className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition ${
                  activeFolder === "all" ? "bg-slate-900 text-white" : "text-slate-700 hover:bg-slate-100"
                }`}
              >
                <span className="flex min-w-0 items-center gap-2">
                  {activeFolder === "all" ? <FolderOpen className="h-4 w-4 shrink-0 text-amber-300" /> : <Folder className="h-4 w-4 shrink-0 text-amber-500" />}
                  <span className="truncate">All Published Assets</span>
                </span>
                <span className="text-xs">{vaultDocuments.length}</span>
              </button>
              {documentTypes.map((type) => {
                const isActive = activeFolder === type.value;
                const count = folderCounts[type.value] ?? 0;

                return (
                  <button
                    key={type.value}
                    type="button"
                    onClick={() => setActiveFolder(type.value)}
                    className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition ${
                      isActive ? "bg-slate-900 text-white" : "text-slate-700 hover:bg-slate-100"
                    }`}
                  >
                    <span className="flex min-w-0 items-center gap-2">
                      {isActive ? <FolderOpen className="h-4 w-4 shrink-0 text-amber-300" /> : <Folder className="h-4 w-4 shrink-0 text-amber-500" />}
                      <span className="truncate">{type.label}</span>
                    </span>
                    <span className="text-xs">{count}</span>
                  </button>
                );
              })}
            </div>
          </div>

          <div className="rounded-lg border bg-card shadow-sm">
            <div className="border-b p-4">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-base font-semibold">Published Vault Assets</h2>
                <div className="text-sm text-muted-foreground">{filteredDocuments.length} shown</div>
              </div>
              <div className="mt-3 grid gap-3 lg:grid-cols-[minmax(0,1fr)_180px_180px]">
                <label className="flex items-center gap-2 rounded-md border bg-background px-3 py-2">
                  <Search className="h-4 w-4 text-slate-400" />
                  <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search title, file, type, description"
                    className="min-w-0 flex-1 bg-transparent text-sm outline-none"
                  />
                </label>
                <select value={audienceFilter} onChange={(event) => setAudienceFilter(event.target.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
                  <option value="all">All audiences</option>
                  <option value="all_staff">All staff</option>
                  <option value="department">Department</option>
                  <option value="selected_users">Selected users</option>
                </select>
                <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)} className="rounded-md border bg-background px-3 py-2 text-sm">
                  <option value="all">All statuses</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>

            <div className="divide-y">
              {previewDocument ? (
                <div className="border-b bg-slate-50 p-4">
                  <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex min-w-0 items-center gap-3">
                      <VaultFileIcon document={previewDocument} />
                      <div className="min-w-0">
                        <div className="truncate text-sm font-semibold text-slate-900">{previewDocument.title}</div>
                        <div className="truncate text-xs text-muted-foreground">{previewDocument.file_name}</div>
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => setPreviewDocumentId(null)}
                      className="inline-flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    >
                      <X className="h-4 w-4" />
                      Close Preview
                    </button>
                  </div>

                  {previewDocument.can_preview ? (
                    <iframe
                      title={`Preview ${previewDocument.title}`}
                      src={previewDocument.preview_url}
                      className="h-[70vh] w-full rounded-md border bg-white"
                    />
                  ) : (
                    <div className="rounded-md border bg-white p-6 text-sm text-muted-foreground">
                      Native in-page preview is available for PDF files. Word, Excel, and PowerPoint files need a conversion pipeline before the browser can render them inside this pane.
                    </div>
                  )}
                </div>
              ) : null}

              {filteredDocuments.length === 0 ? (
                <div className="px-4 py-8 text-sm text-muted-foreground">No organization documents match the current filters.</div>
              ) : filteredDocuments.map((document) => (
                <div key={document.id} className={`px-4 py-3 hover:bg-slate-50 ${previewDocumentId === document.id ? "bg-slate-50" : ""}`}>
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex min-w-0 items-start gap-3">
                      <VaultFileIcon document={document} />
                      <div className="min-w-0">
                        <div className="truncate font-medium text-slate-900">{document.title}</div>
                        <div className="mt-1 text-xs text-muted-foreground">
                          {document.document_type_label} | {formatAudience(document.audience_scope)}
                          {document.department_name ? ` | ${document.department_name}` : ""}
                          {document.slot_key ? ` | Slot ${document.slot_key}` : ""}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                          {document.is_active ? "Active" : "Inactive"}
                          {document.effective_from ? ` | Effective ${document.effective_from}` : ""}
                          {document.effective_until ? ` | Retires ${document.effective_until}` : ""}
                        </div>
                        <div className="mt-1 truncate text-xs text-muted-foreground">
                          {document.file_name} | {document.published_by_name ?? "-"} | {document.created_at ?? "-"}
                        </div>
                        {document.description ? <div className="mt-2 text-sm text-muted-foreground">{document.description}</div> : null}
                      </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                      <button
                        type="button"
                        onClick={() => setPreviewDocumentId(document.id)}
                        className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-white"
                      >
                        <Eye className="h-4 w-4" />
                        Preview
                      </button>
                      {document.can.download ? (
                        <a href={document.download_url} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-white">
                          <Download className="h-4 w-4" />
                          Download
                        </a>
                      ) : null}
                      {document.can.manage ? (
                        <>
                          <button
                            type="button"
                            onClick={() => router.post(`/organization/documents/${document.id}/lifecycle`, { action: document.is_active ? "deactivate" : "activate" }, { preserveScroll: true })}
                            className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-white"
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
                          <button
                            type="button"
                            onClick={() => {
                              if (window.confirm(`Delete "${document.title}" permanently?`)) {
                                router.delete(`/organization/documents/${document.id}`, { preserveScroll: true });
                              }
                            }}
                            className="inline-flex items-center gap-2 rounded-md border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                          >
                            <Trash2 className="h-4 w-4" />
                            Delete
                          </button>
                        </>
                      ) : null}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      </div>
    </AppLayout>
  );
}
