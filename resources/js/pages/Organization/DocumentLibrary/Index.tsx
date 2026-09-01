import { Head, Link, router, usePage } from "@inertiajs/react";
import {
  Archive,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Download,
  Eye,
  File,
  FileImage,
  FilePlus2,
  FileSpreadsheet,
  FileText,
  Filter,
  Folder,
  FolderOpen,
  FolderPlus,
  History,
  Layers3,
  Link2,
  List,
  Lock,
  MoreHorizontal,
  Plus,
  RotateCcw,
  Search,
  ShieldCheck,
  Upload,
  X,
  XCircle,
} from "lucide-react";
import { FormEvent, ReactNode, useMemo, useState } from "react";

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
  id?: number;
  status: string;
  comments: string | null;
  approver_name: string | null;
  approved_at: string | null;
  created_at?: string | null;
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
  approval_history: FileApproval[];
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
type DialogName = "workspace" | "folder" | "upload" | "template" | null;
type SortKey = "name" | "modified" | "created" | "type" | "size";
type InspectorTab = "details" | "versions" | "links" | "approvals" | "activity" | "vault";
type FormErrors = Record<string, string | undefined>;

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Document Workspace", href: "/organization/document-library" },
];

function unwrapResource<T extends object>(value: ResourceProp<T> | null): T | null {
  if (!value) return null;
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

function fileIcon(file: FileItem, className = "h-4 w-4") {
  const kind = file.preview.kind;

  if (kind === "image") return <FileImage className={`${className} text-emerald-600`} />;
  if (kind === "spreadsheet") return <FileSpreadsheet className={`${className} text-green-700`} />;
  if (kind === "word" || kind === "pdf" || kind === "text") return <FileText className={`${className} text-blue-700`} />;
  if (kind === "presentation") return <FilePlus2 className={`${className} text-orange-600`} />;

  return <File className={`${className} text-slate-500`} />;
}

function statusTone(status: string): string {
  if (status === "approved") return "border-emerald-200 bg-emerald-50 text-emerald-700";
  if (status === "under_review") return "border-sky-200 bg-sky-50 text-sky-700";
  if (status === "rejected") return "border-rose-200 bg-rose-50 text-rose-700";
  if (status === "archived") return "border-slate-200 bg-slate-100 text-slate-600";
  return "border-amber-200 bg-amber-50 text-amber-700";
}

function Modal({ title, children, onClose }: { title: string; children: ReactNode; onClose: () => void }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-[2px]">
      <section role="dialog" aria-modal="true" aria-labelledby="document-dialog-title" className="w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/25">
        <div className="flex items-start justify-between gap-4 px-7 pb-4 pt-7">
          <div className="flex items-start gap-4">
            <span className="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-red-100 to-orange-100 text-red-600 ring-8 ring-orange-50">
              <FolderPlus className="h-7 w-7" />
            </span>
            <div className="pt-1">
              <h2 id="document-dialog-title" className="text-2xl font-semibold tracking-normal text-slate-950">{title}</h2>
              <p className="mt-1 text-sm text-slate-500">Fill in the details and save your changes.</p>
            </div>
          </div>
          <button type="button" onClick={onClose} className="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-950" aria-label="Close dialog">
            <X className="h-5 w-5" />
          </button>
        </div>
        <div className="max-h-[calc(90vh-9rem)] overflow-y-auto">{children}</div>
      </section>
    </div>
  );
}

function ProgressBar({ label, value }: { label: string; value: number | null }) {
  if (value === null) return null;

  return (
    <div className="rounded-md border bg-slate-50 p-2">
      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <span>{label}</span>
        <span>{value}%</span>
      </div>
      <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
        <div className="h-full rounded-full bg-slate-900 transition-all" style={{ width: `${value}%` }} />
      </div>
    </div>
  );
}

function FieldError({ message }: { message?: string }) {
  if (!message) return null;

  return <div className="text-xs font-medium text-rose-600">{message}</div>;
}

function RepositoryTree({ tree, selectedFolderId }: { tree: TreeNode[]; selectedFolderId: number | null }) {
  return (
    <aside className="min-h-0 border-r bg-white lg:w-72">
      <div className="flex items-center justify-between border-b px-3 py-2">
        <div>
          <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Repositories</div>
          <div className="text-sm font-semibold text-slate-900">Working library</div>
        </div>
        <button type="button" onClick={() => router.get("/organization/document-library", {}, { preserveScroll: true })} className="rounded-md border px-2 py-1 text-xs hover:bg-slate-50">
          Home
        </button>
      </div>
      <div className="max-h-[calc(100vh-245px)] overflow-auto p-2">
        {tree.length === 0 ? (
          <div className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">No repositories are visible for your account.</div>
        ) : tree.map((node) => (
          <TreeBranch key={node.id} node={node} selectedFolderId={selectedFolderId} />
        ))}
      </div>
    </aside>
  );
}

function TreeBranch({ node, selectedFolderId }: { node: TreeNode; selectedFolderId: number | null }) {
  const selected = selectedFolderId === node.id;
  const [open, setOpen] = useState(() => selected || node.children.some((child) => child.id === selectedFolderId));
  const Icon = selected ? FolderOpen : Folder;

  return (
    <div className="space-y-1">
      <div className={`group flex items-center gap-1 rounded-md ${selected ? "bg-slate-900 text-white" : "text-slate-700 hover:bg-slate-100"}`}>
        {node.children.length > 0 ? (
          <button type="button" onClick={() => setOpen((value) => !value)} className="rounded p-1" aria-label={open ? `Collapse ${node.name}` : `Expand ${node.name}`}>
            {open ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}
          </button>
        ) : <span className="w-5" />}
        <button
          type="button"
          onClick={() => router.get("/organization/document-library", { folder: node.id }, { preserveState: true, preserveScroll: true })}
          className="flex min-w-0 flex-1 items-center gap-2 px-1 py-1.5 text-left text-sm"
        >
          <Icon className={`h-4 w-4 shrink-0 ${selected ? "text-amber-300" : "text-amber-500"}`} />
          <span className="truncate">{node.name}</span>
        </button>
      </div>
      {open && node.children.length > 0 ? (
        <div className="ml-5 space-y-1 border-l border-slate-200 pl-2">
          {node.children.map((child) => (
            <TreeBranch key={child.id} node={child} selectedFolderId={selectedFolderId} />
          ))}
        </div>
      ) : null}
    </div>
  );
}

function WorkspaceBreadcrumbs({ crumbs }: { crumbs: Array<{ id: number; name: string }> }) {
  return (
    <nav aria-label="Document workspace breadcrumb" className="flex flex-wrap items-center gap-1 text-sm text-muted-foreground">
      <button type="button" onClick={() => router.get("/organization/document-library", {}, { preserveScroll: true })} className="rounded px-2 py-1 hover:bg-slate-100">
        Organization
      </button>
      <ChevronRight className="h-3.5 w-3.5" />
      <span className="rounded px-2 py-1">Document Workspace</span>
      {crumbs.map((crumb) => (
        <span key={crumb.id} className="inline-flex items-center gap-1">
          <ChevronRight className="h-3.5 w-3.5" />
          <button type="button" onClick={() => router.get("/organization/document-library", { folder: crumb.id }, { preserveScroll: true, preserveState: true })} className="rounded px-2 py-1 hover:bg-slate-100">
            {crumb.name}
          </button>
        </span>
      ))}
    </nav>
  );
}

type CommandBarProps = {
  currentFolder: FolderItem | null;
  searchTerm: string;
  searchStatus: string;
  typeFilter: string;
  sortKey: SortKey;
  viewMode: "list" | "grid";
  statusOptions: StatusOption[];
  onSearchTermChange: (value: string) => void;
  onSearchStatusChange: (value: string) => void;
  onTypeFilterChange: (value: string) => void;
  onSortKeyChange: (value: SortKey) => void;
  onViewModeChange: (value: "list" | "grid") => void;
  onDialogChange: (value: DialogName) => void;
};

function CommandBar(props: CommandBarProps) {
  const runSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.get("/organization/document-library", {
      folder: props.currentFolder?.id,
      search: props.searchTerm || undefined,
      status: props.searchStatus || undefined,
    }, { preserveState: true, preserveScroll: true });
  };

  return (
    <div className="border-b bg-white px-3 py-2">
      <div className="flex flex-wrap items-center gap-2">
        <details className="relative">
          <summary className="flex cursor-pointer list-none items-center gap-2 rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
            <Plus className="h-4 w-4" />
            New
          </summary>
          <div className="absolute left-0 top-10 z-20 w-64 rounded-md border bg-white p-1 shadow-lg">
            <button type="button" onClick={() => props.onDialogChange("folder")} disabled={!props.currentFolder?.can?.manage} className="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
              <FolderPlus className="h-4 w-4" /> New Folder
            </button>
            <button type="button" onClick={() => props.onDialogChange("workspace")} className="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-slate-50">
              <FolderOpen className="h-4 w-4" /> New Workspace / Repository
            </button>
            <button type="button" onClick={() => props.onDialogChange("upload")} disabled={!props.currentFolder} className="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
              <Upload className="h-4 w-4" /> Upload Document
            </button>
            <button type="button" onClick={() => props.onDialogChange("template")} disabled={!props.currentFolder?.can?.manage} className="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
              <Layers3 className="h-4 w-4" /> Repository Template
            </button>
          </div>
        </details>
        <button type="button" onClick={() => props.onDialogChange("upload")} disabled={!props.currentFolder} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
          <Upload className="h-4 w-4" /> Upload
        </button>
        <button type="button" onClick={() => props.onDialogChange("folder")} disabled={!props.currentFolder?.can?.manage} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
          <FolderPlus className="h-4 w-4" /> New Folder
        </button>

        <form onSubmit={runSearch} className="ml-auto grid min-w-[280px] flex-1 gap-2 md:grid-cols-[minmax(260px,1fr)_150px_140px_130px_auto]">
          <label className="relative min-w-0">
            <span className="sr-only">Search files and folders</span>
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input value={props.searchTerm} onChange={(event) => props.onSearchTermChange(event.target.value)} placeholder="Search files and folders..." className="w-full rounded-md border bg-background py-2 pl-9 pr-3 text-sm" />
          </label>
          <label className="relative">
            <span className="sr-only">Filter status</span>
            <Filter className="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <select value={props.searchStatus} onChange={(event) => props.onSearchStatusChange(event.target.value)} className="w-full rounded-md border bg-background py-2 pl-9 pr-3 text-sm">
              <option value="">All statuses</option>
              {props.statusOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
          </label>
          <select value={props.typeFilter} onChange={(event) => props.onTypeFilterChange(event.target.value)} className="rounded-md border bg-background px-3 py-2 text-sm" aria-label="Filter by type">
            <option value="">All types</option>
            <option value="folder">Folders</option>
            <option value="pdf">PDF</option>
            <option value="image">Images</option>
            <option value="word">Word</option>
            <option value="spreadsheet">Spreadsheets</option>
            <option value="presentation">Presentations</option>
            <option value="text">Text</option>
          </select>
          <select value={props.sortKey} onChange={(event) => props.onSortKeyChange(event.target.value as SortKey)} className="rounded-md border bg-background px-3 py-2 text-sm" aria-label="Sort files">
            <option value="name">Sort: Name</option>
            <option value="modified">Modified</option>
            <option value="created">Created</option>
            <option value="type">Type</option>
            <option value="size">Size</option>
          </select>
          <button type="submit" className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Apply</button>
        </form>

        <div className="flex items-center gap-1 rounded-md border p-1">
          <button type="button" aria-label="List view" onClick={() => props.onViewModeChange("list")} className={`rounded p-1.5 ${props.viewMode === "list" ? "bg-slate-900 text-white" : "text-slate-600 hover:bg-slate-100"}`}>
            <List className="h-4 w-4" />
          </button>
          <button type="button" aria-label="Grid view" onClick={() => props.onViewModeChange("grid")} className={`rounded p-1.5 ${props.viewMode === "grid" ? "bg-slate-900 text-white" : "text-slate-600 hover:bg-slate-100"}`}>
            <Layers3 className="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  );
}

type ExplorerProps = {
  folders: FolderItem[];
  files: FileItem[];
  selectedFileId: number | null;
  selectedFolderId: number | null;
  viewMode: "list" | "grid";
  onSelectFile: (file: FileItem) => void;
  onSelectFolder: (folder: FolderItem) => void;
  onDialogChange: (dialog: DialogName) => void;
  onUploadTargetChange: (folder: FolderItem | null) => void;
};

function DocumentExplorer({ folders, files, selectedFileId, selectedFolderId, viewMode, onSelectFile, onSelectFolder, onDialogChange, onUploadTargetChange }: ExplorerProps) {
  if (folders.length === 0 && files.length === 0) {
    return (
      <div className="flex min-h-[360px] items-center justify-center p-6">
        <div className="max-w-md rounded-lg border border-dashed p-6 text-center">
          <FolderOpen className="mx-auto h-8 w-8 text-amber-500" />
          <div className="mt-3 text-sm font-semibold text-slate-900">This folder is empty.</div>
          <div className="mt-1 text-sm text-muted-foreground">Create a folder or upload a document into this workspace.</div>
          <div className="mt-4 flex justify-center gap-2">
            <button type="button" onClick={() => onDialogChange("upload")} className="rounded-md bg-slate-900 px-3 py-2 text-sm text-white">Upload document</button>
            <button type="button" onClick={() => onDialogChange("folder")} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Create folder</button>
          </div>
        </div>
      </div>
    );
  }

  if (viewMode === "grid") {
    return (
      <div className="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        {folders.map((folder) => (
          <button key={`folder-${folder.id}`} type="button" onClick={() => onSelectFolder(folder)} onDoubleClick={() => router.get("/organization/document-library", { folder: folder.id }, { preserveState: true, preserveScroll: true })} className={`rounded-lg border p-3 text-left hover:bg-slate-50 ${selectedFolderId === folder.id ? "border-slate-900 bg-slate-50" : ""}`}>
            <Folder className="h-7 w-7 text-amber-500" />
            <div className="mt-3 truncate text-sm font-semibold text-slate-900">{folder.name}</div>
            <div className="mt-1 text-xs text-muted-foreground">{folder.file_count ?? 0} files</div>
          </button>
        ))}
        {files.map((file) => (
          <button key={`file-${file.id}`} type="button" onClick={() => onSelectFile(file)} className={`rounded-lg border p-3 text-left hover:bg-slate-50 ${selectedFileId === file.id ? "border-slate-900 bg-slate-50" : ""}`}>
            {fileIcon(file, "h-7 w-7")}
            <div className="mt-3 truncate text-sm font-semibold text-slate-900">{file.title}</div>
            <div className="mt-1 truncate text-xs text-muted-foreground">{formatLabel(file.preview.kind)} • {formatBytes(file.size_bytes)}</div>
            <div className={`mt-3 inline-flex rounded-full border px-2 py-1 text-[11px] ${statusTone(file.status)}`}>{formatLabel(file.status)}</div>
          </button>
        ))}
      </div>
    );
  }

  return (
    <div className="overflow-x-hidden overflow-y-auto">
      <table className="w-full table-fixed text-sm">
        <thead className="sticky top-0 z-10 border-b bg-slate-50 text-xs uppercase tracking-wide text-muted-foreground">
          <tr>
            <th className="w-[36%] px-3 py-2 text-left font-semibold">Name</th>
            <th className="w-[12%] px-3 py-2 text-left font-semibold">Type</th>
            <th className="w-[14%] px-3 py-2 text-left font-semibold">Owner</th>
            <th className="w-[15%] px-3 py-2 text-left font-semibold">Modified</th>
            <th className="w-[14%] px-3 py-2 text-left font-semibold">Status</th>
            <th className="w-[16%] px-3 py-2 text-left font-semibold">Linked To</th>
            <th className="w-[10%] px-3 py-2 text-right font-semibold">Size</th>
            <th className="w-12 px-3 py-2" />
          </tr>
        </thead>
        <tbody className="divide-y">
          {folders.map((folder) => (
            <tr key={`folder-${folder.id}`} className={selectedFolderId === folder.id ? "bg-slate-50" : "hover:bg-slate-50"}>
              <td className="px-3 py-2">
                <button type="button" onClick={() => onSelectFolder(folder)} onDoubleClick={() => router.get("/organization/document-library", { folder: folder.id }, { preserveState: true, preserveScroll: true })} className="flex min-w-0 items-center gap-2 text-left">
                  <Folder className="h-4 w-4 shrink-0 text-amber-500" />
                  <span className="truncate font-medium text-slate-900">{folder.name}</span>
                </button>
              </td>
              <td className="truncate px-3 py-2 text-muted-foreground">Folder</td>
              <td className="truncate px-3 py-2 text-muted-foreground">Repository</td>
              <td className="truncate px-3 py-2 text-muted-foreground">-</td>
              <td className="px-3 py-2"><span className="rounded-full border px-2 py-1 text-[11px] text-slate-600">Open</span></td>
              <td className="truncate px-3 py-2 text-muted-foreground">-</td>
              <td className="truncate px-3 py-2 text-right text-muted-foreground">{folder.file_count ?? 0} files</td>
              <td className="px-3 py-2 text-right">
                <details className="relative inline-block">
                  <summary className="list-none rounded p-1 hover:bg-slate-100" aria-label={`Actions for ${folder.name}`}><MoreHorizontal className="h-4 w-4" /></summary>
                  <div className="absolute right-0 z-20 w-44 rounded-md border bg-white p-1 text-left shadow-lg">
                    <button type="button" onClick={() => router.get("/organization/document-library", { folder: folder.id }, { preserveScroll: true })} className="w-full rounded px-3 py-2 text-left text-sm hover:bg-slate-50">Open</button>
                    <button type="button" onClick={() => { onUploadTargetChange(folder); onDialogChange("upload"); }} className="w-full rounded px-3 py-2 text-left text-sm hover:bg-slate-50">Upload Here</button>
                    {folder.can?.manage ? <button type="button" onClick={() => { onSelectFolder(folder); onDialogChange("folder"); }} className="w-full rounded px-3 py-2 text-left text-sm hover:bg-slate-50">Create Subfolder</button> : null}
                    {folder.can?.delete ? <button type="button" onClick={() => router.delete(`/organization/document-library/folders/${folder.id}`, { preserveScroll: true })} className="w-full rounded px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50">Delete</button> : null}
                  </div>
                </details>
              </td>
            </tr>
          ))}
          {files.map((file) => (
            <tr key={`file-${file.id}`} className={selectedFileId === file.id ? "bg-slate-50" : "hover:bg-slate-50"}>
              <td className="px-3 py-2">
                <button type="button" onClick={() => onSelectFile(file)} className="flex min-w-0 items-center gap-2 text-left">
                  {fileIcon(file)}
                  <span className="truncate font-medium text-slate-900">{file.title}</span>
                </button>
              </td>
              <td className="truncate px-3 py-2 text-muted-foreground">{formatLabel(file.preview.kind)}</td>
              <td className="truncate px-3 py-2 text-muted-foreground">{file.uploaded_by_name ?? "-"}</td>
              <td className="truncate px-3 py-2 text-muted-foreground">{file.updated_at ?? "-"}</td>
              <td className="px-3 py-2">
                <span className={`rounded-full border px-2 py-1 text-[11px] ${statusTone(file.status)}`}>{formatLabel(file.status)}</span>
                {file.checked_out_by_name ? <span className="ml-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] text-amber-700">Checked Out</span> : null}
              </td>
              <td className="px-3 py-2 text-muted-foreground">
                <span className="block truncate">{file.links.length ? file.links.map((link) => link.linkable_name).join(", ") : "-"}</span>
              </td>
              <td className="truncate px-3 py-2 text-right text-muted-foreground">{formatBytes(file.size_bytes)}</td>
              <td className="px-3 py-2 text-right">
                <details className="relative inline-block">
                  <summary className="list-none rounded p-1 hover:bg-slate-100" aria-label={`Actions for ${file.title}`}><MoreHorizontal className="h-4 w-4" /></summary>
                  <div className="absolute right-0 z-20 w-48 rounded-md border bg-white p-1 text-left shadow-lg">
                    <button type="button" onClick={() => onSelectFile(file)} className="w-full rounded px-3 py-2 text-left text-sm hover:bg-slate-50">View Details</button>
                    {file.preview.inline_url ? <a href={file.preview.inline_url} className="block rounded px-3 py-2 text-sm hover:bg-slate-50">Open Preview</a> : null}
                    {file.can?.download ? <a href={file.download_url} className="block rounded px-3 py-2 text-sm hover:bg-slate-50">Download</a> : null}
                    {file.can?.checkout && !file.checked_out_by_name ? <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/checkout`, {}, { preserveScroll: true })} className="w-full rounded px-3 py-2 text-left text-sm hover:bg-slate-50">Check Out</button> : null}
                    {file.can?.checkout && file.checked_out_by_name ? <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/force-release`, {}, { preserveScroll: true })} className="w-full rounded px-3 py-2 text-left text-sm hover:bg-slate-50">Force Release</button> : null}
                    {file.can?.manage ? <button type="button" onClick={() => router.delete(`/organization/document-library/files/${file.id}`, { preserveScroll: true })} className="w-full rounded px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50">Delete</button> : null}
                  </div>
                </details>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function Inspector({ file, folder, activeTab, setActiveTab, linkOptions, relationshipTypes, users, vaultDocumentTypes, filteredSlots, selectedLinkType, setSelectedLinkType, selectedVaultType, setSelectedVaultType, canPublishToVault }: {
  file: FileItem | null;
  folder: FolderItem | null;
  activeTab: InspectorTab;
  setActiveTab: (tab: InspectorTab) => void;
  linkOptions: LinkOptionGroup[];
  relationshipTypes: Array<{ value: string; label: string }>;
  users: Array<{ id: number; name: string }>;
  vaultDocumentTypes: Array<{ value: string; label: string }>;
  filteredSlots: Array<{ value: string; label: string; document_type: string }>;
  selectedLinkType: string;
  setSelectedLinkType: (value: string) => void;
  selectedVaultType: string;
  setSelectedVaultType: (value: string) => void;
  canPublishToVault: boolean;
}) {
  const activeLinkGroup = useMemo(
    () => linkOptions.find((group) => group.linkable_type === selectedLinkType) ?? linkOptions[0] ?? null,
    [linkOptions, selectedLinkType],
  );
  const tabs: InspectorTab[] = ["details", "versions", "links", "approvals", "activity", "vault"];

  if (!file) {
    return (
      <aside className="min-h-0 border-l bg-white xl:w-96">
        <div className="border-b px-4 py-3">
          <div className="text-sm font-semibold text-slate-900">Details</div>
          <div className="text-xs text-muted-foreground">Selection inspector</div>
        </div>
        <div className="p-4">
          {folder ? (
            <div className="space-y-3">
              <Folder className="h-8 w-8 text-amber-500" />
              <div>
                <div className="text-base font-semibold text-slate-900">{folder.name}</div>
                <div className="text-sm text-muted-foreground">Folder • {folder.file_count ?? 0} files</div>
              </div>
              <button type="button" onClick={() => router.get("/organization/document-library", { folder: folder.id }, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Open folder</button>
            </div>
          ) : (
            <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">Select a file or folder to view details.</div>
          )}
        </div>
      </aside>
    );
  }

  return (
    <aside className="min-h-0 border-l bg-white xl:w-96">
      <div className="border-b px-4 py-3">
        <div className="flex items-start gap-3">
          {fileIcon(file, "h-6 w-6")}
          <div className="min-w-0">
            <div className="truncate text-sm font-semibold text-slate-900">{file.title}</div>
            <div className="truncate text-xs text-muted-foreground">{file.original_name}</div>
          </div>
        </div>
        <div className="mt-3 flex flex-wrap gap-1">
          {tabs.map((tab) => (
            <button key={tab} type="button" onClick={() => setActiveTab(tab)} className={`rounded-md px-2 py-1 text-xs ${activeTab === tab ? "bg-slate-900 text-white" : "border text-slate-600 hover:bg-slate-50"}`}>
              {formatLabel(tab)}
            </button>
          ))}
        </div>
      </div>
      <div className="max-h-[calc(100vh-280px)] overflow-auto p-4">
        {activeTab === "details" ? <DetailsTab file={file} /> : null}
        {activeTab === "versions" ? <VersionsTab file={file} /> : null}
        {activeTab === "links" ? <LinksTab file={file} linkOptions={linkOptions} activeLinkGroup={activeLinkGroup} selectedLinkType={selectedLinkType} setSelectedLinkType={setSelectedLinkType} relationshipTypes={relationshipTypes} /> : null}
        {activeTab === "approvals" ? <ApprovalsTab file={file} /> : null}
        {activeTab === "activity" ? <ActivityTab file={file} /> : null}
        {activeTab === "vault" ? <VaultTab file={file} users={users} vaultDocumentTypes={vaultDocumentTypes} filteredSlots={filteredSlots} selectedVaultType={selectedVaultType} setSelectedVaultType={setSelectedVaultType} canPublishToVault={canPublishToVault} /> : null}
      </div>
    </aside>
  );
}

function DetailsTab({ file }: { file: FileItem }) {
  return (
    <div className="space-y-4">
      {file.preview.kind === "pdf" || file.preview.kind === "image" || file.preview.kind === "text" ? (
        <iframe title={file.title} src={file.preview.inline_url ?? file.preview_url} className="h-60 w-full rounded-md border bg-white" />
      ) : (
        <div className="rounded-md border bg-slate-50 p-3 text-sm text-muted-foreground">
          <div className="font-medium text-slate-700">{file.preview.thumbnail_label} preview summary</div>
          <div className="mt-2 whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{file.preview.excerpt ?? "Inline rendering is not available for this format."}</div>
        </div>
      )}
      <dl className="grid gap-2 text-sm">
        {[
          ["Title", file.title],
          ["Filename", file.original_name],
          ["Type", formatLabel(file.preview.kind)],
          ["Size", formatBytes(file.size_bytes)],
          ["Owner", file.uploaded_by_name ?? "-"],
          ["Created", file.created_at ?? "-"],
          ["Modified", file.updated_at ?? "-"],
          ["Status", formatLabel(file.status)],
          ["Current version", `v${file.version}`],
          ["Check-out state", file.checked_out_by_name ? `Checked out by ${file.checked_out_by_name}` : "Available"],
          ["Description", file.description ?? "-"],
        ].map(([label, value]) => (
          <div key={label} className="grid grid-cols-[120px_minmax(0,1fr)] gap-3 border-b pb-2">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="min-w-0 break-words text-slate-900 [overflow-wrap:anywhere]">{value}</dd>
          </div>
        ))}
      </dl>
      <div className="flex flex-wrap gap-2">
        {file.can?.download ? <a href={file.download_url} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50"><Download className="h-4 w-4" /> Download</a> : null}
        {file.preview.inline_url ? <a href={file.preview.inline_url} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50"><Eye className="h-4 w-4" /> Preview</a> : null}
        {file.can?.checkout && !file.checked_out_by_name ? <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/checkout`, {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50"><Lock className="h-4 w-4" /> Check Out</button> : null}
        {file.can?.checkout && file.checked_out_by_name ? <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/force-release`, {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50"><Lock className="h-4 w-4" /> Force Release</button> : null}
      </div>
    </div>
  );
}

function VersionsTab({ file }: { file: FileItem }) {
  const [progress, setProgress] = useState<number | null>(null);

  return (
    <div className="space-y-4">
      {file.can?.version ? (
        <form
          className="space-y-3 rounded-md border p-3"
          onSubmit={(event) => {
            event.preventDefault();
            const form = event.currentTarget;
            setProgress(0);
            router.post(`/organization/document-library/files/${file.id}/versions`, new FormData(form), {
              forceFormData: true,
              preserveScroll: true,
              onProgress: (upload) => setProgress(upload?.percentage ?? 0),
              onFinish: () => setProgress(null),
              onSuccess: () => form.reset(),
            });
          }}
        >
          <div className="text-sm font-semibold">Upload New Version</div>
          <input type="file" name="file" className="w-full rounded-md border px-3 py-2 text-sm" />
          <input name="notes" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Version notes" />
          <ProgressBar label="Uploading new version" value={progress} />
          <button type="submit" className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Upload Version</button>
        </form>
      ) : null}
      <div className="space-y-2">
        {file.versions.length === 0 ? <div className="text-sm text-muted-foreground">No version history is available.</div> : file.versions.map((version) => (
          <div key={version.id} className="rounded-md border p-3">
            <div className="flex items-start justify-between gap-2">
              <div className="min-w-0">
                <div className="truncate text-sm font-medium">v{version.version_number} • {version.original_name}</div>
                <div className="mt-1 text-xs text-muted-foreground">{version.uploaded_by_name ?? "-"} • {version.created_at ?? "-"}</div>
                {version.notes ? <div className="mt-2 text-xs text-slate-700">{version.notes}</div> : null}
              </div>
              {file.can?.version ? (
                <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/versions/${version.id}/restore`, {}, { preserveScroll: true })} className="inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs hover:bg-slate-50">
                  <RotateCcw className="h-3.5 w-3.5" /> Restore
                </button>
              ) : null}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function LinksTab({ file, linkOptions, activeLinkGroup, selectedLinkType, setSelectedLinkType, relationshipTypes }: {
  file: FileItem;
  linkOptions: LinkOptionGroup[];
  activeLinkGroup: LinkOptionGroup | null;
  selectedLinkType: string;
  setSelectedLinkType: (value: string) => void;
  relationshipTypes: Array<{ value: string; label: string }>;
}) {
  return (
    <div className="space-y-4">
      <div className="space-y-2">
        {file.links.length === 0 ? <div className="text-sm text-muted-foreground">This document is not linked to an ERP record yet.</div> : file.links.map((link) => (
          <div key={link.id} className="flex items-center justify-between gap-3 rounded-md border p-3">
            <div>
              <div className="text-sm font-medium text-slate-900">{link.linkable_name}</div>
              <div className="text-xs text-muted-foreground">{formatLabel(link.linkable_type)} • {formatLabel(link.relationship_type)}</div>
            </div>
            {file.can?.manage ? <button type="button" onClick={() => router.delete(`/organization/document-library/files/${file.id}/links/${link.id}`, { preserveScroll: true })} className="rounded-md border px-2 py-1 text-xs hover:bg-slate-50">Remove</button> : null}
          </div>
        ))}
      </div>
      {file.can?.manage ? (
        <form className="space-y-3 rounded-md border p-3" onSubmit={(event) => {
          event.preventDefault();
          router.post(`/organization/document-library/files/${file.id}/links`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
        }}>
          <div className="flex items-center gap-2 text-sm font-semibold"><Link2 className="h-4 w-4" /> Link ERP Record</div>
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
  );
}

function ApprovalsTab({ file }: { file: FileItem }) {
  return (
    <div className="space-y-4">
      <div className="rounded-md border p-3">
        <div className="flex items-center gap-2 text-sm font-semibold"><ShieldCheck className="h-4 w-4" /> Governance Status</div>
        <div className={`mt-3 inline-flex rounded-full border px-2 py-1 text-xs ${statusTone(file.status)}`}>{formatLabel(file.status)}</div>
        {file.latest_approval ? (
          <div className="mt-3 text-sm text-slate-700">
            {file.latest_approval.approver_name ?? "Pending approver"} • {file.latest_approval.approved_at ?? file.latest_approval.created_at ?? "-"}
          </div>
        ) : null}
      </div>
      <div className="flex flex-wrap gap-2">
        {file.can?.manage ? <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/submit-review`, {}, { preserveScroll: true })} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Submit For Review</button> : null}
        {file.can?.approve ? (
          <>
            <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/approve`, {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-emerald-50"><CheckCircle2 className="h-4 w-4" /> Approve</button>
            <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/reject`, {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-rose-50"><XCircle className="h-4 w-4" /> Reject</button>
            <button type="button" onClick={() => router.post(`/organization/document-library/files/${file.id}/archive`, {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-slate-50"><Archive className="h-4 w-4" /> Archive</button>
          </>
        ) : null}
      </div>
      <div className="space-y-2">
        {file.approval_history.length === 0 ? <div className="text-sm text-muted-foreground">No approval history yet.</div> : file.approval_history.map((approval, index) => (
          <div key={approval.id ?? index} className="rounded-md border p-3 text-sm">
            <div className="font-medium">{formatLabel(approval.status)}</div>
            <div className="text-xs text-muted-foreground">{approval.approver_name ?? "Pending approver"} • {approval.approved_at ?? approval.created_at ?? "-"}</div>
            {approval.comments ? <div className="mt-2 text-xs">{approval.comments}</div> : null}
          </div>
        ))}
      </div>
    </div>
  );
}

function ActivityTab({ file }: { file: FileItem }) {
  return (
    <div className="space-y-2">
      {file.activity.length === 0 ? <div className="text-sm text-muted-foreground">No activity has been recorded yet.</div> : file.activity.map((item) => (
        <div key={item.id} className="flex gap-3 rounded-md border p-3">
          <History className="mt-0.5 h-4 w-4 shrink-0 text-slate-500" />
          <div className="min-w-0">
            <div className="text-sm font-medium text-slate-900">{formatLabel(item.action)}</div>
            <div className="text-xs text-muted-foreground">{item.user_name ?? "System"} • {item.created_at ?? "-"}</div>
            {item.entity_context ? <div className="mt-1 text-xs text-slate-700">{item.entity_context}</div> : null}
          </div>
        </div>
      ))}
    </div>
  );
}

function VaultTab({ file, users, vaultDocumentTypes, filteredSlots, selectedVaultType, setSelectedVaultType, canPublishToVault }: {
  file: FileItem;
  users: Array<{ id: number; name: string }>;
  vaultDocumentTypes: Array<{ value: string; label: string }>;
  filteredSlots: Array<{ value: string; label: string; document_type: string }>;
  selectedVaultType: string;
  setSelectedVaultType: (value: string) => void;
  canPublishToVault: boolean;
}) {
  if (!canPublishToVault) {
    return <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">You do not have permission to publish documents to the Official Organization Vault.</div>;
  }

  return (
    <form className="space-y-3" onSubmit={(event) => {
      event.preventDefault();
      router.post(`/organization/document-library/files/${file.id}/publish-to-vault`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
    }}>
      <div className="text-sm font-semibold">Publish To Official Vault</div>
      <input name="title" defaultValue={file.title} className="w-full rounded-md border px-3 py-2 text-sm" />
      <select name="document_type" value={selectedVaultType} onChange={(event) => setSelectedVaultType(event.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
        {vaultDocumentTypes.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}
      </select>
      <select name="slot_key" className="w-full rounded-md border px-3 py-2 text-sm">
        <option value="">No replacement slot</option>
        {filteredSlots.map((slot) => <option key={slot.value} value={slot.value}>{slot.label}</option>)}
      </select>
      <select name="audience_scope" className="w-full rounded-md border px-3 py-2 text-sm">
        <option value="all_staff">All staff</option>
        <option value="selected_users">Selected users</option>
      </select>
      <label className="flex items-center gap-2 text-xs text-muted-foreground">
        <input type="checkbox" name="is_active" value="1" defaultChecked />
        Active immediately
      </label>
      <textarea name="description" defaultValue={file.description ?? ""} rows={3} className="w-full rounded-md border px-3 py-2 text-sm" placeholder="How staff should use this document." />
      <div className="max-h-32 overflow-auto rounded-md border p-2">
        {users.map((item) => (
          <label key={item.id} className="flex items-center gap-2 py-1 text-xs">
            <input type="checkbox" name="selected_user_ids[]" value={item.id} />
            <span>{item.name}</span>
          </label>
        ))}
      </div>
      <button type="submit" className="rounded-md bg-slate-900 px-3 py-2 text-sm text-white hover:bg-slate-800">Publish</button>
    </form>
  );
}

function WorkspaceDialogs({ dialog, close, currentFolder, uploadTarget, ownerOptions, activeOwnerGroup, selectedOwnerType, setSelectedOwnerType, templates, libraryUploadProgress, setLibraryUploadProgress }: {
  dialog: DialogName;
  close: () => void;
  currentFolder: FolderItem | null;
  uploadTarget: FolderItem | null;
  ownerOptions: OwnerOptionGroup[];
  activeOwnerGroup: OwnerOptionGroup | null;
  selectedOwnerType: string;
  setSelectedOwnerType: (value: string) => void;
  templates: TemplateItem[];
  libraryUploadProgress: number | null;
  setLibraryUploadProgress: (value: number | null) => void;
}) {
  const page = usePage<{ errors?: FormErrors }>();
  const errors = page.props.errors ?? {};
  const [workspaceName, setWorkspaceName] = useState("");
  const [selectedOwnerId, setSelectedOwnerId] = useState(activeOwnerGroup?.items[0]?.id ? String(activeOwnerGroup.items[0].id) : "");
  const targetFolder = uploadTarget ?? currentFolder;
  const activeOwnerItems = activeOwnerGroup?.items ?? [];
  const selectedOwnerStillAvailable = activeOwnerItems.some((item) => String(item.id) === selectedOwnerId);
  const effectiveOwnerId = selectedOwnerStillAvailable ? selectedOwnerId : (activeOwnerItems[0]?.id ? String(activeOwnerItems[0].id) : "");
  const canCreateWorkspace = workspaceName.trim().length > 0 && effectiveOwnerId.length > 0;

  if (dialog === "workspace") {
    return (
      <Modal title="Create Workspace / Repository" onClose={close}>
        <form className="space-y-3 p-4" onSubmit={(event) => {
          event.preventDefault();
          if (!canCreateWorkspace) return;

          router.post("/organization/document-library/root-folders", {
            name: workspaceName,
            owner_type: selectedOwnerType,
            owner_id: effectiveOwnerId,
          }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
              setWorkspaceName("");
              close();
            },
          });
        }}>
          <div>
            <input name="name" value={workspaceName} onChange={(event) => setWorkspaceName(event.target.value)} className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Workspace name" autoFocus />
            <FieldError message={errors.name} />
          </div>
          <div>
          <select name="owner_type" value={selectedOwnerType} onChange={(event) => {
            const ownerType = event.target.value;
            const nextGroup = ownerOptions.find((group) => group.owner_type === ownerType);
            setSelectedOwnerType(ownerType);
            setSelectedOwnerId(nextGroup?.items[0]?.id ? String(nextGroup.items[0].id) : "");
          }} className="w-full rounded-md border px-3 py-2 text-sm">
            {ownerOptions.map((group) => <option key={group.owner_type} value={group.owner_type}>{group.label}</option>)}
          </select>
            <FieldError message={errors.owner_type} />
          </div>
          <div>
          <select name="owner_id" value={effectiveOwnerId} onChange={(event) => setSelectedOwnerId(event.target.value)} disabled={activeOwnerItems.length === 0} className="w-full rounded-md border px-3 py-2 text-sm disabled:bg-slate-100 disabled:text-muted-foreground">
            {activeOwnerItems.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
          </select>
            <FieldError message={errors.owner_id} />
            {activeOwnerItems.length === 0 ? <div className="mt-1 text-xs text-muted-foreground">No records are available for this workspace type.</div> : null}
          </div>
          <button type="submit" disabled={!canCreateWorkspace} className="rounded-md bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-50">Create Workspace</button>
        </form>
      </Modal>
    );
  }

  if (dialog === "folder") {
    return (
      <Modal title="Create Folder" onClose={close}>
        <form className="space-y-3 p-4" onSubmit={(event) => {
          event.preventDefault();
          if (!currentFolder) return;
          router.post("/organization/document-library/folders", new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: close });
        }}>
          <input type="hidden" name="parent_id" value={currentFolder?.id ?? ""} />
          <div className="text-sm text-muted-foreground">Parent: {currentFolder?.name ?? "No folder selected"}</div>
          <input name="name" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Folder name" autoFocus />
          <FieldError message={errors.parent_id ?? errors.name} />
          <button type="submit" disabled={!currentFolder?.can?.manage} className="rounded-md bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-50">Create Folder</button>
        </form>
      </Modal>
    );
  }

  if (dialog === "upload") {
    return (
      <Modal title="Upload Document" onClose={close}>
        <form className="space-y-3 p-4" onSubmit={(event) => {
          event.preventDefault();
          if (!targetFolder) return;
          const form = event.currentTarget;
          setLibraryUploadProgress(0);
          router.post("/organization/document-library/files", new FormData(form), {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress) => setLibraryUploadProgress(progress?.percentage ?? 0),
            onFinish: () => setLibraryUploadProgress(null),
            onSuccess: close,
          });
        }}>
          <input type="hidden" name="folder_id" value={targetFolder?.id ?? ""} />
          <div className="text-sm text-muted-foreground">Destination: {targetFolder?.name ?? "No folder selected"}</div>
          <input name="title" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Title" />
          <textarea name="description" rows={3} className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Description" />
          <input type="file" name="file" className="w-full rounded-md border px-3 py-2 text-sm" />
          <ProgressBar label="Uploading document" value={libraryUploadProgress} />
          <button type="submit" disabled={!targetFolder} className="rounded-md bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-50">Upload</button>
        </form>
      </Modal>
    );
  }

  if (dialog === "template") {
    return (
      <Modal title="Repository Template" onClose={close}>
        <div className="space-y-4 p-4">
          {currentFolder ? (
            <form className="flex gap-3" onSubmit={(event) => {
              event.preventDefault();
              router.post(`/organization/document-library/folders/${currentFolder.id}/apply-template`, new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: close });
            }}>
              <select name="template_id" className="min-w-0 flex-1 rounded-md border px-3 py-2 text-sm">
                {templates.map((template) => <option key={template.id} value={template.id}>{template.name}</option>)}
              </select>
              <button type="submit" disabled={!currentFolder.can?.manage} className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50">Apply</button>
            </form>
          ) : null}
          <form className="space-y-3 border-t pt-4" onSubmit={(event) => {
            event.preventDefault();
            router.post("/organization/document-library/templates", new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true, onSuccess: close });
          }}>
            <div className="text-sm font-semibold">Create Template</div>
            <input name="name" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Template name" />
            <input name="description" className="w-full rounded-md border px-3 py-2 text-sm" placeholder="Description" />
            {["Folder 1", "Folder 2", "Folder 3", "Folder 4"].map((placeholder, index) => (
              <input key={placeholder} name={`items[${index}]`} className="w-full rounded-md border px-3 py-2 text-sm" placeholder={placeholder} />
            ))}
            <button type="submit" className="rounded-md border px-3 py-2 text-sm hover:bg-slate-50">Save Template</button>
          </form>
        </div>
      </Modal>
    );
  }

  return null;
}

export default function DocumentLibraryIndex({
  tree,
  breadcrumbs: currentBreadcrumbs,
  selectedFolder,
  folders,
  files,
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
  const [dialog, setDialog] = useState<DialogName>(null);
  const [selectedFileId, setSelectedFileId] = useState<number | null>(contentFiles[0]?.id ?? null);
  const [selectedFolderItem, setSelectedFolderItem] = useState<FolderItem | null>(null);
  const [uploadTarget, setUploadTarget] = useState<FolderItem | null>(null);
  const [selectedLinkType, setSelectedLinkType] = useState(linkOptions[0]?.linkable_type ?? "");
  const [selectedOwnerType, setSelectedOwnerType] = useState(ownerOptions[0]?.owner_type ?? "");
  const [searchTerm, setSearchTerm] = useState(search.term);
  const [searchStatus, setSearchStatus] = useState(search.status);
  const [typeFilter, setTypeFilter] = useState("");
  const [sortKey, setSortKey] = useState<SortKey>("name");
  const [selectedVaultType, setSelectedVaultType] = useState(vaultDocumentTypes[0]?.value ?? "");
  const [inspectorTab, setInspectorTab] = useState<InspectorTab>("details");
  const [libraryUploadProgress, setLibraryUploadProgress] = useState<number | null>(null);

  const allVisibleFiles = search.term || search.status ? searchResults : contentFiles;
  const selectedFile = useMemo(
    () => contentFiles.find((file) => file.id === selectedFileId) ?? searchResults.find((file) => file.id === selectedFileId) ?? null,
    [contentFiles, searchResults, selectedFileId],
  );
  const activeOwnerGroup = useMemo(
    () => ownerOptions.find((group) => group.owner_type === selectedOwnerType) ?? ownerOptions[0] ?? null,
    [ownerOptions, selectedOwnerType],
  );
  const filteredSlots = useMemo(
    () => vaultSlotOptions.filter((slot) => slot.document_type === selectedVaultType),
    [vaultSlotOptions, selectedVaultType],
  );
  const filteredFolders = useMemo(() => {
    const term = searchTerm.trim().toLowerCase();
    return contentFolders.filter((folder) => {
      const matchesSearch = !term || folder.name.toLowerCase().includes(term);
      const matchesType = !typeFilter || typeFilter === "folder";
      return matchesSearch && matchesType;
    }).sort((a, b) => a.name.localeCompare(b.name));
  }, [contentFolders, searchTerm, typeFilter]);
  const filteredFiles = useMemo(() => {
    const term = searchTerm.trim().toLowerCase();
    return allVisibleFiles.filter((file) => {
      const linkText = file.links.map((link) => `${link.linkable_type} ${link.linkable_name}`).join(" ").toLowerCase();
      const matchesSearch = !term
        || file.title.toLowerCase().includes(term)
        || file.original_name.toLowerCase().includes(term)
        || (file.description?.toLowerCase().includes(term) ?? false)
        || linkText.includes(term);
      const matchesStatus = !searchStatus || file.status === searchStatus;
      const matchesType = !typeFilter || typeFilter !== "folder" && file.preview.kind === typeFilter;
      return matchesSearch && matchesStatus && matchesType;
    }).sort((a, b) => {
      if (sortKey === "modified") return (b.updated_at ?? "").localeCompare(a.updated_at ?? "");
      if (sortKey === "created") return (b.created_at ?? "").localeCompare(a.created_at ?? "");
      if (sortKey === "type") return a.preview.kind.localeCompare(b.preview.kind) || a.title.localeCompare(b.title);
      if (sortKey === "size") return (b.size_bytes ?? 0) - (a.size_bytes ?? 0);
      return a.title.localeCompare(b.title);
    });
  }, [allVisibleFiles, searchTerm, searchStatus, sortKey, typeFilter]);

  const openDialog = (value: DialogName) => {
    if (value !== "upload") setUploadTarget(null);
    setDialog(value);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Enterprise Document Workspace" />

      <div className="flex min-h-[calc(100vh-96px)] flex-col bg-slate-100">
        <header className="border-b bg-white px-4 py-3">
          <WorkspaceBreadcrumbs crumbs={currentBreadcrumbs} />
          <div className="mt-3 flex flex-wrap items-start justify-between gap-3">
            <div>
              <h1 className="text-xl font-semibold text-slate-950">Enterprise Document Workspace</h1>
              <p className="mt-1 text-sm text-muted-foreground">Unified repositories for organization, programs, projects, events and governed publishing.</p>
            </div>
            <div className="flex items-center gap-2">
              <Link href="/organization/documents" className="rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Official Vault</Link>
              <DomainNav items={organizationNavItems} />
            </div>
          </div>
        </header>

        <CommandBar
          currentFolder={currentFolder}
          searchTerm={searchTerm}
          searchStatus={searchStatus}
          typeFilter={typeFilter}
          sortKey={sortKey}
          viewMode={viewMode}
          statusOptions={statusOptions}
          onSearchTermChange={setSearchTerm}
          onSearchStatusChange={setSearchStatus}
          onTypeFilterChange={setTypeFilter}
          onSortKeyChange={setSortKey}
          onViewModeChange={setViewMode}
          onDialogChange={openDialog}
        />

        <main className="grid min-h-0 flex-1 bg-white lg:grid-cols-[288px_minmax(0,1fr)] xl:grid-cols-[288px_minmax(0,1fr)_384px]">
          <RepositoryTree tree={tree} selectedFolderId={currentFolder?.id ?? null} />
          <section className="min-h-0 bg-white">
            <div className="flex items-center justify-between border-b px-4 py-2">
              <div>
                <div className="text-sm font-semibold text-slate-900">{currentFolder?.name ?? "Library Root"}</div>
                <div className="text-xs text-muted-foreground">{filteredFolders.length} folders • {filteredFiles.length} documents</div>
              </div>
              <div className="text-xs text-muted-foreground">{selectedFile ? `Selected: ${selectedFile.title}` : selectedFolderItem ? `Selected: ${selectedFolderItem.name}` : "No selection"}</div>
            </div>
            <div className="max-h-[calc(100vh-258px)] overflow-auto">
              <DocumentExplorer
                folders={filteredFolders}
                files={filteredFiles}
                selectedFileId={selectedFile?.id ?? null}
                selectedFolderId={selectedFolderItem?.id ?? null}
                viewMode={viewMode}
                onSelectFile={(file) => {
                  setSelectedFileId(file.id);
                  setSelectedFolderItem(null);
                  setInspectorTab("details");
                }}
                onSelectFolder={(folder) => {
                  setSelectedFolderItem(folder);
                  setSelectedFileId(null);
                }}
                onDialogChange={openDialog}
                onUploadTargetChange={setUploadTarget}
              />
            </div>
          </section>
          <Inspector
            file={selectedFile}
            folder={selectedFolderItem}
            activeTab={inspectorTab}
            setActiveTab={setInspectorTab}
            linkOptions={linkOptions}
            relationshipTypes={relationshipTypes}
            users={users}
            vaultDocumentTypes={vaultDocumentTypes}
            filteredSlots={filteredSlots}
            selectedLinkType={selectedLinkType}
            setSelectedLinkType={setSelectedLinkType}
            selectedVaultType={selectedVaultType}
            setSelectedVaultType={setSelectedVaultType}
            canPublishToVault={canPublishToVault}
          />
        </main>

        <footer className="border-t bg-white px-4 py-2 text-xs text-muted-foreground">
          {filteredFolders.length + filteredFiles.length} items • {currentFolder ? `Current folder: ${currentFolder.name}` : "Repository root"} • Official vault publishing remains governed by existing permissions.
        </footer>

        <WorkspaceDialogs
          dialog={dialog}
          close={() => setDialog(null)}
          currentFolder={currentFolder}
          uploadTarget={uploadTarget}
          ownerOptions={ownerOptions}
          activeOwnerGroup={activeOwnerGroup}
          selectedOwnerType={selectedOwnerType}
          setSelectedOwnerType={setSelectedOwnerType}
          templates={templates}
          libraryUploadProgress={libraryUploadProgress}
          setLibraryUploadProgress={setLibraryUploadProgress}
        />
      </div>
    </AppLayout>
  );
}
