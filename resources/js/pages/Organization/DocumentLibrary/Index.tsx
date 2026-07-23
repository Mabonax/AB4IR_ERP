import { Head, Link, router } from "@inertiajs/react";
import { Eye, File, FileImage, FileSpreadsheet, FileText, Folder, FolderOpen, Layers3, List, Presentation, Search, ShieldCheck } from "lucide-react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { organizationNavItems } from "@/config/domain-nav/organization";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type TreeNode = {
  id: number;
  name: string;
  folder_type: string;
  children: TreeNode[];
};

type FolderItem = {
  id: number;
  name: string;
  parent_id: number | null;
  folder_type: string;
  file_count?: number;
  can?: {
    manage: boolean;
    delete: boolean;
  };
};

type FileVersion = {
  id: number;
  version_number: number;
  original_name: string;
  notes: string | null;
  uploaded_by_name: string | null;
  created_at: string | null;
};

type FileLink = {
  id: number;
  relationship_type: string;
  linkable_type: string;
  linkable_name: string;
};

type FileApproval = {
  status: string;
  comments: string | null;
  approver_name: string | null;
  approved_at: string | null;
};

type FileItem = {
  id: number;
  folder_id: number;
  title: string;
  description: string | null;
  original_name: string;
  mime_type: string | null;
  size_bytes: number | null;
  version: number;
  status: string;
  uploaded_by_name: string | null;
  checked_out_by_name: string | null;
  checked_out_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  download_url: string;
  preview_url: string;
  preview: {
    kind: string;
    inline_url: string | null;
    excerpt: string | null;
    thumbnail_label: string;
  };
  versions: FileVersion[];
  links: FileLink[];
  latest_approval: FileApproval | null;
  approval_history: Array<FileApproval & { id: number; created_at: string | null }>;
  activity: Array<{ id: number; action: string; user_name: string | null; entity_context: string | null; created_at: string | null }>;
  can?: {
    download: boolean;
    manage: boolean;
    version: boolean;
    approve: boolean;
    checkout: boolean;
  };
};

type OwnerOptionGroup = {
  label: string;
  owner_type: string;
  items: Array<{ id: number; name: string }>;
};

type LinkOptionGroup = {
  label: string;
  linkable_type: string;
  items: Array<{ id: number; name: string }>;
};

type TemplateItem = {
  id: number;
  name: string;
  description: string | null;
  owner_type: string | null;
  is_system: boolean;
  items: string[];
};

type StatusOption = {
  value: string;
  label: string;
};

type ResourceProp<T> = { data: T } | T;

function unwrapResource<T extends object>(value: ResourceProp<T> | null): T | null {
  if (!value) {
    return null;
  }

  return "data" in value ? value.data : value;
}

function unwrapCollection<T>(value: { data?: T[] } | T[]): T[] {
  return Array.isArray(value) ? value : (value.data ?? []);
}

function formatBytes(value: number | null): string {
  if (!value) return "-";
  if (value < 1024) return `${value} B`;
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
  return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

function formatLabel(value: string | null | undefined): string {
  if (!value) return "-";
  return value.replace(/_/g, " ").replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function fileIcon(file: FileItem) {
  const kind = file.preview.kind;

  if (kind === "image") return <FileImage className="h-4 w-4 text-emerald-600" />;
  if (kind === "spreadsheet") return <FileSpreadsheet className="h-4 w-4 text-green-700" />;
  if (kind === "word" || kind === "pdf" || kind === "text") return <FileText className="h-4 w-4 text-blue-700" />;
  if (kind === "presentation") return <Presentation className="h-4 w-4 text-orange-600" />;

  return <File className="h-4 w-4 text-slate-500" />;
}

function TreeBranch({ node, selectedFolderId }: { node: TreeNode; selectedFolderId: number | null }) {
  const selected = selectedFolderId === node.id;
  const Icon = selected ? FolderOpen : Folder;

  return (
    <div className="space-y-1">
      <button
        type="button"
        onClick={() => router.get("/organization/document-library", { folder: node.id }, { preserveState: true, preserveScroll: true })}
        className={`flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm ${selected ? "bg-slate-900 text-white" : "text-slate-700 hover:bg-slate-100"}`}
      >
        <Icon className={`h-4 w-4 shrink-0 ${selected ? "text-amber-300" : "text-amber-500"}`} />
        <span className="truncate">{node.name}</span>
      </button>
      {node.children.length > 0 ? (
        <div className="ml-4 space-y-1 border-l border-slate-200 pl-3">
          {node.children.map((child) => (
            <TreeBranch key={child.id} node={child} selectedFolderId={selectedFolderId} />
          ))}
        </div>
      ) : null}
    </div>
  );
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Document Workspace", href: "/organization/document-library" },
];

export default function DocumentLibraryIndex({
  tree,
  breadcrumbs: currentBreadcrumbs,
  selectedFolder,
  folders,
  files,
  moveTargets,
  users,
  vaultDocumentTypes,
  vaultSlotOptions,
  canPublishToVault,
  ownerOptions,
  linkOptions,
  relationshipTypes,
  templates,
  search,
  statusOptions,
}: {
  tree: TreeNode[];
  breadcrumbs: Array<{ id: number; name: string }>;
  selectedFolder: ResourceProp<FolderItem> | null;
  folders: { data?: FolderItem[] } | FolderItem[];
  files: { data?: FileItem[] } | FileItem[];
  moveTargets: { data?: FolderItem[] } | FolderItem[];
  users: Array<{ id: number; name: string }>;
  vaultDocumentTypes: Array<{ value: string; label: string }>;
  vaultSlotOptions: Array<{ value: string; label: string; document_type: string }>;
  canPublishToVault: boolean;
  ownerOptions: OwnerOptionGroup[];
  linkOptions: LinkOptionGroup[];
  relationshipTypes: Array<{ value: string; label: string }>;
  templates: TemplateItem[];
  search: { term: string; status: string; results: { data?: FileItem[] } | FileItem[] };
  statusOptions: StatusOption[];
}) {
  const currentFolder = unwrapResource(selectedFolder);
  const contentFolders = unwrapCollection(folders);
  const contentFiles = unwrapCollection(files);
  const searchResults = unwrapCollection(search.results);

  const [viewMode, setViewMode] = useState<"list" | "grid">("list");
  const [selectedFileId, setSelectedFileId] = useState<number | null>(contentFiles[0]?.id ?? null);
  const [selectedLinkType, setSelectedLinkType] = useState(linkOptions[0]?.linkable_type ?? "");
  const [selectedOwnerType, setSelectedOwnerType] = useState(ownerOptions[0]?.owner_type ?? "");
  const [searchTerm, setSearchTerm] = useState(search.term);
  const [searchStatus, setSearchStatus] = useState(search.status);
  const [selectedVaultType, setSelectedVaultType] = useState(vaultDocumentTypes[0]?.value ?? "");

  const activeFile = useMemo(
    () => contentFiles.find((file) => file.id === selectedFileId) ?? searchResults.find((file) => file.id === selectedFileId) ?? null,
    [contentFiles, searchResults, selectedFileId],
  );
  const activeLinkGroup = useMemo(
    () => linkOptions.find((group) => group.linkable_type === selectedLinkType) ?? linkOptions[0] ?? null,
    [linkOptions, selectedLinkType],
  );
  const activeOwnerGroup = useMemo(
    () => ownerOptions.find((group) => group.owner_type === selectedOwnerType) ?? ownerOptions[0] ?? null,
    [ownerOptions, selectedOwnerType],
  );
  const filteredSlots = useMemo(
    () => vaultSlotOptions.filter((slot) => slot.document_type === selectedVaultType),
    [vaultSlotOptions, selectedVaultType],
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Enterprise Document Workspace" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Enterprise Document Workspace</h1>
            <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
              Unified explorer-style repositories for programs, projects, events, operations, and approved vault publishing.
            </p>
          </div>
          <DomainNav items={organizationNavItems} />
        </div>

        <div className="grid gap-5 xl:grid-cols-[280px_minmax(0,1fr)_380px]">
          <section className="rounded-2xl border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
              <div>
                <div className="text-sm font-semibold">Repositories</div>
                <div className="text-xs text-muted-foreground">Folder tree</div>
              </div>
              <button
                type="button"
                onClick={() => router.get("/organization/document-library", {}, { preserveScroll: true })}
                className="rounded-md border px-3 py-2 text-xs hover:bg-slate-50"
              >
                Root
              </button>
            </div>
            <div className="space-y-1">
              {tree.map((node) => (
                <TreeBranch key={node.id} node={node} selectedFolderId={currentFolder?.id ?? null} />
              ))}
            </div>
          </section>

          <section className="rounded-2xl border bg-card shadow-sm">
            <div className="border-b p-4">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    <span>Workspace</span>
                    {currentBreadcrumbs.map((crumb) => (
                      <button
                        key={crumb.id}
                        type="button"
                        onClick={() => router.get("/organization/document-library", { folder: crumb.id }, { preserveScroll: true, preserveState: true })}
                        className="rounded-full border px-2 py-1 hover:bg-slate-50"
                      >
                        {crumb.name}
                      </button>
                    ))}
                  </div>
                  <h2 className="mt-2 text-xl font-semibold">{currentFolder?.name ?? "Library Root"}</h2>
                </div>

                <div className="flex items-center gap-2">
                  <button type="button" onClick={() => setViewMode("list")} className={`rounded-md border p-2 ${viewMode === "list" ? "bg-slate-900 text-white" : ""}`}><List className="h-4 w-4" /></button>
                  <button type="button" onClick={() => setViewMode("grid")} className={`rounded-md border p-2 ${viewMode === "grid" ? "bg-slate-900 text-white" : ""}`}><Layers3 className="h-4 w-4" /></button>
                </div>
              </div>

              <form
                className="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_auto]"
                onSubmit={(event) => {
                  event.preventDefault();
                  router.get("/organization/document-library", { folder: currentFolder?.id, search: searchTerm, status: searchStatus || undefined }, { preserveState: true, preserveScroll: true });
                }}
              >
                <div className="relative">
                  <Search className="absolute left-3 top-3.5 h-4 w-4 text-slate-400" />
                  <input value={searchTerm} onChange={(event) => setSearchTerm(event.target.value)} placeholder="Search by document, owner, linked record, or description" className="w-full rounded-lg border bg-background py-3 pl-10 pr-3 text-sm" />
                </div>
                <select value={searchStatus} onChange={(event) => setSearchStatus(event.target.value)} className="rounded-lg border bg-background px-3 py-3 text-sm">
                  <option value="">All statuses</option>
                  {statusOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                </select>
                <button type="submit" className="rounded-lg bg-slate-900 px-4 py-3 text-sm text-white hover:bg-slate-800">Search</button>
              </form>
            </div>

            <div className="space-y-5 p-4">
              <div className="grid gap-4 lg:grid-cols-3">
                <form
                  className="rounded-xl border p-4"
                  onSubmit={(event) => {
                    event.preventDefault();
                    router.post("/organization/document-library/root-folders", new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: () => event.currentTarget.reset() });
                  }}
                >
                  <div className="text-sm font-semibold">Create Workspace</div>
                  <input name="name" className="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Workspace name" />
                  <select
                    name="owner_type"
                    value={selectedOwnerType}
                    onChange={(event) => setSelectedOwnerType(event.target.value)}
                    className="mt-3 w-full rounded-md border px-3 py-2 text-sm"
                  >
                    {ownerOptions.map((group) => <option key={group.owner_type} value={group.owner_type}>{group.label}</option>)}
                  </select>
                  <select name="owner_id" className="mt-3 w-full rounded-md border px-3 py-2 text-sm">
                    {activeOwnerGroup?.items.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                  </select>
                  <button type="submit" className="mt-3 rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Create</button>
                </form>

                <form
                  className="rounded-xl border p-4"
                  onSubmit={(event) => {
                    event.preventDefault();
                    if (!currentFolder) return;
                    router.post("/organization/document-library/folders", new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: () => event.currentTarget.reset() });
                  }}
                >
                  <div className="text-sm font-semibold">Create Subfolder</div>
                  <input type="hidden" name="parent_id" value={currentFolder?.id ?? ""} />
                  <input name="name" disabled={!currentFolder?.can?.manage} className="mt-3 w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60" placeholder="Folder name" />
                  <button type="submit" disabled={!currentFolder?.can?.manage} className="mt-3 rounded-md border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-60">Add Folder</button>
                </form>

                <form
                  className="rounded-xl border p-4"
                  onSubmit={(event) => {
                    event.preventDefault();
                    if (!currentFolder) return;
                    router.post("/organization/document-library/files", new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: () => event.currentTarget.reset() });
                  }}
                >
                  <div className="text-sm font-semibold">Upload Document</div>
                  <input type="hidden" name="folder_id" value={currentFolder?.id ?? ""} />
                  <input name="title" className="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Title" />
                  <input name="description" className="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Description" />
                  <input type="file" name="file" className="mt-3 w-full rounded-md border px-3 py-2 text-sm" />
                  <button type="submit" disabled={!currentFolder} className="mt-3 rounded-md border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-60">Upload</button>
                </form>
              </div>

              {currentFolder ? (
                <div className="rounded-xl border p-4">
                  <div className="mb-3 flex items-center justify-between gap-3">
                    <div className="text-sm font-semibold">Repository Template</div>
                    <span className="text-xs text-muted-foreground">{currentFolder.name}</span>
                  </div>
                  <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                    <form
                      className="flex gap-3"
                      onSubmit={(event) => {
                        event.preventDefault();
                        router.post(`/organization/document-library/folders/${currentFolder.id}/apply-template`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
                      }}
                    >
                      <select name="template_id" className="min-w-0 flex-1 rounded-md border px-3 py-2 text-sm">
                        {templates.map((template) => <option key={template.id} value={template.id}>{template.name}</option>)}
                      </select>
                      <button type="submit" disabled={!currentFolder.can?.manage} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-60">Apply Template</button>
                    </form>
                    <details className="rounded-md border px-3 py-2 text-sm">
                      <summary className="cursor-pointer font-medium">Create Template</summary>
                      <form
                        className="mt-3 space-y-3"
                        onSubmit={(event) => {
                          event.preventDefault();
                          router.post("/organization/document-library/templates", new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: () => event.currentTarget.reset() });
                        }}
                      >
                        <input name="name" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Template name" />
                        <input name="description" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Description" />
                        <input name="owner_type" defaultValue={currentFolder.folder_type} className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Owner type (optional)" />
                        {["Folder 1", "Folder 2", "Folder 3", "Folder 4"].map((placeholder, index) => (
                          <input key={placeholder} name={`items[${index}]`} className="w-full rounded-md border px-3 py-2 text-sm" placeholder={placeholder} />
                        ))}
                        <button type="submit" className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Save Template</button>
                      </form>
                    </details>
                  </div>
                </div>
              ) : null}

              {search.term || search.status ? (
                <div className="rounded-xl border p-4">
                  <div className="mb-3 text-sm font-semibold">Search Results</div>
                  <div className="space-y-2">
                    {searchResults.length === 0 ? <div className="text-sm text-muted-foreground">No matching documents found.</div> : searchResults.map((file) => (
                      <button key={file.id} type="button" onClick={() => setSelectedFileId(file.id)} className="flex w-full items-center justify-between rounded-lg border px-3 py-3 text-left hover:bg-slate-50">
                        <div className="flex min-w-0 items-center gap-3">
                          {fileIcon(file)}
                          <div className="min-w-0">
                            <div className="truncate font-medium">{file.title}</div>
                            <div className="truncate text-xs text-muted-foreground">{file.original_name} • {formatLabel(file.status)} • v{file.version}</div>
                          </div>
                        </div>
                        <div className="text-xs text-muted-foreground">{file.links.length} links</div>
                      </button>
                    ))}
                  </div>
                </div>
              ) : null}

              <div className={viewMode === "grid" ? "grid gap-3 md:grid-cols-2 xl:grid-cols-3" : "overflow-hidden rounded-xl border"}>
                {contentFolders.map((folder) => (
                  <button
                    key={`folder-${folder.id}`}
                    type="button"
                    onClick={() => router.get("/organization/document-library", { folder: folder.id }, { preserveState: true, preserveScroll: true })}
                    className={viewMode === "grid" ? "rounded-xl border p-4 text-left hover:bg-slate-50" : "flex w-full items-center justify-between border-b px-4 py-3 text-left hover:bg-slate-50 last:border-b-0"}
                  >
                    <div className="flex min-w-0 items-center gap-3">
                      <Folder className="h-5 w-5 shrink-0 text-amber-500" />
                      <div className="min-w-0">
                        <div className="truncate font-medium">{folder.name}</div>
                        <div className="text-xs text-muted-foreground">{folder.file_count ?? 0} files</div>
                      </div>
                    </div>
                    <span className="text-xs text-muted-foreground">Open</span>
                  </button>
                ))}

                {contentFiles.map((file) => (
                  <button
                    key={`file-${file.id}`}
                    type="button"
                    onClick={() => setSelectedFileId(file.id)}
                    className={viewMode === "grid" ? `rounded-xl border p-4 text-left hover:bg-slate-50 ${activeFile?.id === file.id ? "border-slate-900 bg-slate-50" : ""}` : `flex w-full items-center justify-between border-b px-4 py-3 text-left hover:bg-slate-50 last:border-b-0 ${activeFile?.id === file.id ? "bg-slate-50" : ""}`}
                  >
                    <div className="flex min-w-0 items-center gap-3">
                      {fileIcon(file)}
                      <div className="min-w-0">
                        <div className="truncate font-medium">{file.title}</div>
                        <div className="truncate text-xs text-muted-foreground">
                          {file.original_name} • {formatBytes(file.size_bytes)} • v{file.version} • {formatLabel(file.status)}
                        </div>
                      </div>
                    </div>
                    {file.checked_out_by_name ? <span className="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] text-amber-700">Checked out</span> : null}
                  </button>
                ))}

                {contentFolders.length === 0 && contentFiles.length === 0 ? (
                  <div className="rounded-xl border border-dashed p-8 text-sm text-muted-foreground">No folders or documents in this location yet.</div>
                ) : null}
              </div>
            </div>
          </section>

          <section className="rounded-2xl border bg-card p-4 shadow-sm">
            {activeFile ? (
              <div className="space-y-4">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <div className="text-lg font-semibold">{activeFile.title}</div>
                    <div className="mt-1 text-xs text-muted-foreground">{activeFile.original_name}</div>
                  </div>
                  <div className="rounded-full border px-2 py-1 text-xs">{formatLabel(activeFile.status)}</div>
                </div>

                <div className="rounded-xl border bg-slate-50 p-3">
                  <div className="mb-2 flex items-center gap-2 text-sm font-medium"><Eye className="h-4 w-4" /> Preview</div>
                  {activeFile.preview.kind === "pdf" || activeFile.preview.kind === "image" || activeFile.preview.kind === "text" ? (
                    <iframe title={activeFile.title} src={activeFile.preview.inline_url ?? activeFile.preview_url} className="h-56 w-full rounded-lg border bg-white" />
                  ) : (
                    <div className="rounded-lg border bg-white p-4 text-sm text-muted-foreground">
                      <div className="mb-2 font-medium text-slate-700">{activeFile.preview.thumbnail_label} preview summary</div>
                      <div className="whitespace-pre-wrap">{activeFile.preview.excerpt ?? "Inline rendering is not available for this format. The workspace is showing an extracted preview summary instead."}</div>
                    </div>
                  )}
                </div>

                <div className="grid gap-2 rounded-xl border p-3 text-sm">
                  <div className="flex justify-between gap-3"><span className="text-muted-foreground">Owner</span><span>{activeFile.uploaded_by_name ?? "-"}</span></div>
                  <div className="flex justify-between gap-3"><span className="text-muted-foreground">Size</span><span>{formatBytes(activeFile.size_bytes)}</span></div>
                  <div className="flex justify-between gap-3"><span className="text-muted-foreground">Modified</span><span>{activeFile.updated_at ?? "-"}</span></div>
                  <div className="flex justify-between gap-3"><span className="text-muted-foreground">Checkout</span><span>{activeFile.checked_out_by_name ? `By ${activeFile.checked_out_by_name}` : "Available"}</span></div>
                </div>

                <div className="flex flex-wrap gap-2">
                  {activeFile.can?.download ? <Link href={activeFile.download_url} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Download</Link> : null}
                  {activeFile.can?.checkout && !activeFile.checked_out_by_name ? (
                    <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/checkout`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Check Out</button>
                  ) : null}
                  {activeFile.can?.checkout && activeFile.checked_out_by_name ? (
                    <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/force-release`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Force Release</button>
                  ) : null}
                  {activeFile.can?.manage ? (
                    <button type="button" onClick={() => router.delete(`/organization/document-library/files/${activeFile.id}`, { preserveScroll: true })} className="rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 hover:bg-rose-50">Delete</button>
                  ) : null}
                </div>

                {activeFile.can?.version ? (
                  <form
                    className="rounded-xl border p-3"
                    onSubmit={(event) => {
                      event.preventDefault();
                      router.post(`/organization/document-library/files/${activeFile.id}/versions`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: () => event.currentTarget.reset() });
                    }}
                  >
                    <div className="text-sm font-semibold">Upload New Version</div>
                    <input type="file" name="file" className="mt-3 w-full rounded-md border px-3 py-2 text-sm" />
                    <input name="notes" className="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Version notes" />
                    <button type="submit" className="mt-3 rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Upload Version</button>
                  </form>
                ) : null}

                <div className="rounded-xl border p-3">
                  <div className="mb-2 text-sm font-semibold">Version History</div>
                  <div className="space-y-2">
                    {activeFile.versions.map((version) => (
                      <div key={version.id} className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                          <div className="truncate text-sm font-medium">v{version.version_number} • {version.original_name}</div>
                          <div className="truncate text-xs text-muted-foreground">{version.uploaded_by_name ?? "-"} • {version.created_at ?? "-"}</div>
                        </div>
                        {activeFile.can?.version ? (
                          <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/versions/${version.id}/restore`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-xs hover:bg-slate-50">
                            Restore
                          </button>
                        ) : null}
                      </div>
                    ))}
                  </div>
                </div>

                <div className="rounded-xl border p-3">
                  <div className="mb-2 text-sm font-semibold">Linked Records</div>
                  <div className="space-y-2">
                    {activeFile.links.map((link) => (
                      <div key={link.id} className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2">
                        <div>
                          <div className="text-sm font-medium">{link.linkable_name}</div>
                          <div className="text-xs text-muted-foreground">{formatLabel(link.linkable_type)} • {formatLabel(link.relationship_type)}</div>
                        </div>
                        {activeFile.can?.manage ? (
                          <button type="button" onClick={() => router.delete(`/organization/document-library/files/${activeFile.id}/links/${link.id}`, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-xs hover:bg-slate-50">
                            Remove
                          </button>
                        ) : null}
                      </div>
                    ))}
                  </div>
                  {activeFile.can?.manage ? (
                    <form
                      className="mt-3 space-y-3"
                      onSubmit={(event) => {
                        event.preventDefault();
                        router.post(`/organization/document-library/files/${activeFile.id}/links`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
                      }}
                    >
                      <select name="linkable_type" value={selectedLinkType} onChange={(event) => setSelectedLinkType(event.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                        {linkOptions.map((group) => <option key={group.linkable_type} value={group.linkable_type}>{group.label}</option>)}
                      </select>
                      <select name="linkable_id" className="w-full rounded-md border px-3 py-2 text-sm">
                        {activeLinkGroup?.items.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                      </select>
                      <select name="relationship_type" className="w-full rounded-md border px-3 py-2 text-sm">
                        {relationshipTypes.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}
                      </select>
                      <button type="submit" className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Link Record</button>
                    </form>
                  ) : null}
                </div>

                <div className="rounded-xl border p-3">
                  <div className="mb-2 flex items-center gap-2 text-sm font-semibold"><ShieldCheck className="h-4 w-4" /> Approval Workflow</div>
                  {activeFile.latest_approval ? (
                    <div className="mb-3 rounded-lg border bg-slate-50 p-3 text-sm">
                      <div className="font-medium">{formatLabel(activeFile.latest_approval.status)}</div>
                      <div className="mt-1 text-xs text-muted-foreground">{activeFile.latest_approval.approver_name ?? "Pending approver"} • {activeFile.latest_approval.approved_at ?? "-"}</div>
                      {activeFile.latest_approval.comments ? <div className="mt-2 text-xs">{activeFile.latest_approval.comments}</div> : null}
                    </div>
                  ) : null}
                  <div className="grid gap-2">
                    {activeFile.can?.manage ? (
                      <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/submit-review`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">
                        Submit For Review
                      </button>
                    ) : null}
                    {activeFile.can?.approve ? (
                      <>
                        <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/approve`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Approve</button>
                        <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/reject`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Reject</button>
                        <button type="button" onClick={() => router.post(`/organization/document-library/files/${activeFile.id}/archive`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Archive</button>
                      </>
                    ) : null}
                  </div>
                </div>

                {canPublishToVault ? (
                  <form
                    className="rounded-xl border p-3"
                    onSubmit={(event) => {
                      event.preventDefault();
                      router.post(`/organization/document-library/files/${activeFile.id}/publish-to-vault`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
                    }}
                  >
                    <div className="text-sm font-semibold">Publish To Organization Vault</div>
                    <input name="title" defaultValue={activeFile.title} className="mt-3 w-full rounded-md border px-3 py-2 text-sm" />
                    <select name="document_type" value={selectedVaultType} onChange={(event) => setSelectedVaultType(event.target.value)} className="mt-3 w-full rounded-md border px-3 py-2 text-sm">
                      {vaultDocumentTypes.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}
                    </select>
                    <select name="slot_key" className="mt-3 w-full rounded-md border px-3 py-2 text-sm">
                      <option value="">No replacement slot</option>
                      {filteredSlots.map((slot) => <option key={slot.value} value={slot.value}>{slot.label}</option>)}
                    </select>
                    <select name="audience_scope" className="mt-3 w-full rounded-md border px-3 py-2 text-sm">
                      <option value="all_staff">All staff</option>
                      <option value="selected_users">Selected users</option>
                    </select>
                    <textarea name="description" defaultValue={activeFile.description ?? ""} rows={3} className="mt-3 w-full rounded-md border px-3 py-2 text-sm" />
                    <div className="mt-3 max-h-32 overflow-auto rounded-md border p-2">
                      {users.map((item) => (
                        <label key={item.id} className="flex items-center gap-2 py-1 text-xs">
                          <input type="checkbox" name="selected_user_ids[]" value={item.id} />
                          <span>{item.name}</span>
                        </label>
                      ))}
                    </div>
                    <label className="mt-3 flex items-center gap-2 text-xs text-muted-foreground">
                      <input type="checkbox" name="is_active" value="1" defaultChecked />
                      Active immediately
                    </label>
                    <button type="submit" className="mt-3 rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Publish</button>
                  </form>
                ) : null}
              </div>
            ) : (
              <div className="rounded-xl border border-dashed p-8 text-sm text-muted-foreground">Select a document to preview metadata, version history, links, approvals, and publish actions.</div>
            )}
          </section>
        </div>
      </div>
    </AppLayout>
  );
}
