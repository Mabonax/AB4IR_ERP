import { Head, router, usePage } from "@inertiajs/react";
import { File, FileSpreadsheet, FileText, Folder, FolderOpen, Presentation } from "lucide-react";
import { useMemo, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { organizationNavItems } from "@/config/domain-nav/organization";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type TreeNode = {
  id: number;
  name: string;
  folder_type: string;
  owner_type: string | null;
  owner_id: number | null;
  children: TreeNode[];
};

type FolderItem = {
  id: number;
  name: string;
  parent_id: number | null;
  folder_type: string;
  can?: {
    manage: boolean;
    delete: boolean;
  };
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
  uploaded_by_name: string | null;
  created_at: string | null;
  download_url: string;
  can?: {
    download: boolean;
    manage: boolean;
  };
};

type OwnerOptionGroup = {
  label: string;
  owner_type: string;
  items: Array<{ id: number; name: string }>;
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

function fileExtension(fileName: string): string {
  return fileName.split(".").pop()?.toLowerCase() ?? "";
}

function FileIcon({ file }: { file: Pick<FileItem, "mime_type" | "original_name"> }) {
  const extension = fileExtension(file.original_name);

  if (["xls", "xlsx"].includes(extension) || file.mime_type?.includes("spreadsheet")) {
    return <FileSpreadsheet className="h-5 w-5 text-emerald-600" />;
  }

  if (["ppt", "pptx"].includes(extension) || file.mime_type?.includes("presentation")) {
    return <Presentation className="h-5 w-5 text-red-600" />;
  }

  if (["pdf", "doc", "docx"].includes(extension) || file.mime_type?.includes("pdf") || file.mime_type?.includes("word")) {
    return <FileText className="h-5 w-5 text-blue-600" />;
  }

  return <File className="h-5 w-5 text-slate-500" />;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Working Library", href: "/organization/document-library" },
];

function TreeBranch({
  node,
  selectedFolderId,
}: {
  node: TreeNode;
  selectedFolderId: number | null;
}) {
  const isSelected = selectedFolderId === node.id;
  const TreeIcon = isSelected ? FolderOpen : Folder;

  return (
    <div className="space-y-1">
      <button
        type="button"
        onClick={() => router.get("/organization/document-library", { folder: node.id }, { preserveScroll: true, preserveState: true })}
        className={`flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition ${
          isSelected ? "bg-slate-900 text-white" : "text-slate-700 hover:bg-slate-100"
        }`}
      >
        <TreeIcon className={`h-4 w-4 shrink-0 ${isSelected ? "text-amber-300" : "text-amber-500"}`} />
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

export default function DocumentLibraryIndex({
  tree,
  selectedFolder,
  folders,
  files,
  moveTargets,
  departments,
  users,
  vaultDocumentTypes,
  vaultSlotOptions,
  canPublishToVault,
  ownerOptions,
}: {
  tree: TreeNode[];
  selectedFolder: ResourceProp<FolderItem> | null;
  folders: { data?: FolderItem[] } | FolderItem[];
  files: { data?: FileItem[] } | FileItem[];
  moveTargets: { data?: FolderItem[] } | FolderItem[];
  departments: Array<{ id: number; name: string }>;
  users: Array<{ id: number; name: string }>;
  vaultDocumentTypes: Array<{ value: string; label: string }>;
  vaultSlotOptions: Array<{ value: string; label: string; document_type: string }>;
  canPublishToVault: boolean;
  ownerOptions: OwnerOptionGroup[];
}) {
  const page = usePage<{ props: { errors?: Record<string, string> } }>();
  const errors = page.props.errors ?? {};
  const uploadErrors = ["folder_id", "file", "title", "description"]
    .map((key) => errors[key])
    .filter(Boolean);
  const currentFolder = unwrapResource(selectedFolder);
  const contentFolders = unwrapCollection(folders);
  const contentFiles = unwrapCollection(files);
  const availableTargets = unwrapCollection(moveTargets);
  const selectedFolderCanManage = currentFolder?.can?.manage ?? false;
  const selectedFolderIsLibraryGroup = currentFolder?.folder_type === "library_group";
  const canUploadIntoSelectedFolder = Boolean(currentFolder) && !selectedFolderIsLibraryGroup;
  const [selectedFolderItemId, setSelectedFolderItemId] = useState<number | null>(null);
  const [selectedFileId, setSelectedFileId] = useState<number | null>(null);
  const [vaultDocumentType, setVaultDocumentType] = useState(vaultDocumentTypes[0]?.value ?? "email_signature");
  const [selectedOwnerType, setSelectedOwnerType] = useState(ownerOptions[0]?.owner_type ?? "");
  const [selectedOwnerId, setSelectedOwnerId] = useState<string>(ownerOptions[0]?.items[0] ? String(ownerOptions[0].items[0].id) : "");

  const selectedOwnerGroup = useMemo(
    () => ownerOptions.find((group) => group.owner_type === selectedOwnerType) ?? ownerOptions[0] ?? null,
    [ownerOptions, selectedOwnerType],
  );

  const activeFolderItem = useMemo(
    () => contentFolders.find((folder) => folder.id === selectedFolderItemId) ?? null,
    [contentFolders, selectedFolderItemId],
  );
  const activeFile = useMemo(
    () => contentFiles.find((file) => file.id === selectedFileId) ?? null,
    [contentFiles, selectedFileId],
  );
  const filteredSlots = useMemo(
    () => vaultSlotOptions.filter((slot) => slot.document_type === vaultDocumentType),
    [vaultSlotOptions, vaultDocumentType],
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Working Document Library" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">Working Document Library</h1>
            <p className="text-sm text-muted-foreground">
              Operational folders and files are managed here before anything approved is published to the official vault.
            </p>
          </div>
          <DomainNav items={organizationNavItems} />
        </div>

        <div className="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">
          <section className="rounded-2xl border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between gap-2">
              <div>
                <div className="text-sm font-semibold">Folder Tree</div>
                <div className="text-xs text-muted-foreground">Explorer-style workspace hierarchy</div>
              </div>
              <button
                type="button"
                onClick={() => router.get("/organization/document-library", {}, { preserveScroll: true })}
                className="rounded-md border px-3 py-2 text-xs text-slate-700 hover:bg-slate-50"
              >
                Root
              </button>
            </div>
            <div className="space-y-1">
              {tree.length === 0 ? (
                <div className="text-sm text-muted-foreground">No accessible folders yet.</div>
              ) : tree.map((node) => (
                <TreeBranch key={node.id} node={node} selectedFolderId={currentFolder?.id ?? null} />
              ))}
            </div>
          </section>

          <section className="rounded-2xl border bg-card p-4 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3 border-b pb-4">
              <div>
                <h2 className="text-lg font-semibold">{currentFolder?.name ?? "Library Root"}</h2>
                <p className="text-sm text-muted-foreground">
                  {currentFolder ? "Manage working folders and files here, then publish approved outputs to the official vault when needed." : "Create or open working workspaces for organization, program, project, HR, stakeholder, and beneficiary records."}
                </p>
              </div>
            </div>

            {currentFolder ? (
              <div className="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div className="space-y-4">
                  <div className="grid gap-3 md:grid-cols-2">
                    <form
                      className="rounded-xl border p-3"
                      onSubmit={(event) => {
                        event.preventDefault();
                        const form = event.currentTarget;
                        router.post("/organization/document-library/folders", new FormData(form), {
                          forceFormData: true,
                          preserveScroll: true,
                          onSuccess: () => form.reset(),
                        });
                      }}
                    >
                      <div className="text-sm font-semibold">Create Subfolder</div>
                      {selectedFolderCanManage ? (
                        <>
                          <input type="hidden" name="parent_id" value={currentFolder.id} />
                          <input name="name" placeholder="Folder name" className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm" />
                          {errors.name ? <div className="mt-2 text-xs text-red-600">{errors.name}</div> : null}
                          <button type="submit" className="mt-3 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                            Create Folder
                          </button>
                        </>
                      ) : (
                        <div className="mt-3 text-xs text-muted-foreground">
                          You can view this workspace, but only users with workspace management rights can create subfolders here.
                        </div>
                      )}
                    </form>

                    <form
                      className="rounded-xl border p-3"
                      onSubmit={(event) => {
                        event.preventDefault();
                        if (!canUploadIntoSelectedFolder) {
                          return;
                        }
                        const form = event.currentTarget;
                        router.post("/organization/document-library/files", new FormData(form), {
                          forceFormData: true,
                          preserveScroll: true,
                          onSuccess: () => form.reset(),
                        });
                      }}
                    >
                      <div className="text-sm font-semibold">Upload Working File</div>
                      {canUploadIntoSelectedFolder ? (
                        <>
                          <input type="hidden" name="folder_id" value={currentFolder.id} />
                          <input name="title" placeholder="File title (optional)" className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm" />
                          <textarea name="description" rows={2} placeholder="Description" className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm" />
                          <input
                            name="file"
                            type="file"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                            className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm"
                          />
                          {uploadErrors.length > 0 ? (
                            <div className="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                              {uploadErrors.map((message) => (
                                <div key={message}>{message}</div>
                              ))}
                            </div>
                          ) : null}
                          <button type="submit" className="mt-3 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                            Upload File
                          </button>
                        </>
                      ) : (
                        <div className="mt-3 text-xs text-muted-foreground">
                          Uploads are only allowed inside an actual workspace or subfolder. Open a program, project, stakeholder, beneficiary, HR, or organization workspace first.
                        </div>
                      )}
                    </form>
                  </div>

                  <div className="rounded-xl border">
                    <div className="border-b px-4 py-3 text-sm font-semibold">Workspace Contents</div>
                    <div className="divide-y">
                      {contentFolders.length === 0 && contentFiles.length === 0 ? (
                        <div className="px-4 py-6 text-sm text-muted-foreground">This folder is empty.</div>
                      ) : null}

                      {contentFolders.map((folder) => (
                        <div
                          key={`folder-${folder.id}`}
                          onClick={() => {
                            setSelectedFolderItemId(folder.id);
                            setSelectedFileId(null);
                          }}
                          className={`flex cursor-pointer items-center justify-between px-4 py-3 text-left hover:bg-slate-50 ${
                            activeFolderItem?.id === folder.id ? "bg-slate-50" : ""
                          }`}
                        >
                          <div className="flex min-w-0 items-center gap-3">
                            <Folder className="h-6 w-6 shrink-0 text-amber-500" />
                            <div className="min-w-0">
                              <div className="truncate font-medium text-slate-900">{folder.name}</div>
                              <div className="text-xs text-muted-foreground">Folder</div>
                            </div>
                          </div>
                          <button
                            type="button"
                            onClick={(event) => {
                              event.stopPropagation();
                              router.get("/organization/document-library", { folder: folder.id }, { preserveScroll: true, preserveState: true });
                            }}
                            className="rounded-md border px-3 py-2 text-xs text-slate-700 hover:bg-white"
                          >
                            Open
                          </button>
                        </div>
                      ))}

                      {contentFiles.map((file) => (
                        <button
                          key={`file-${file.id}`}
                          type="button"
                          onClick={() => {
                            setSelectedFileId(file.id);
                            setSelectedFolderItemId(null);
                          }}
                          className={`block w-full px-4 py-3 text-left hover:bg-slate-50 ${
                            activeFile?.id === file.id ? "bg-slate-50" : ""
                          }`}
                        >
                          <div className="flex min-w-0 items-center gap-3">
                            <FileIcon file={file} />
                            <div className="min-w-0">
                              <div className="truncate font-medium text-slate-900">{file.title}</div>
                              <div className="truncate text-xs text-muted-foreground">
                                {file.original_name} | v{file.version} | {file.uploaded_by_name ?? "-"}
                              </div>
                            </div>
                          </div>
                        </button>
                      ))}
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  {activeFolderItem ? (
                    <div className="rounded-xl border p-4">
                      <div className="text-sm font-semibold">Subfolder Actions</div>
                      <div className="mt-1 text-xs text-muted-foreground">{activeFolderItem.name}</div>

                      {activeFolderItem.can?.manage ? (
                        <>
                          <form
                            className="mt-4 space-y-3"
                            onSubmit={(event) => {
                              event.preventDefault();
                              router.post(`/organization/document-library/folders/${activeFolderItem.id}/rename`, new FormData(event.currentTarget), {
                                forceFormData: true,
                                preserveScroll: true,
                              });
                            }}
                          >
                            <input name="name" defaultValue={activeFolderItem.name} className="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                            <button type="submit" className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                              Rename
                            </button>
                          </form>

                          <form
                            className="mt-4 space-y-3"
                            onSubmit={(event) => {
                              event.preventDefault();
                              router.post(`/organization/document-library/folders/${activeFolderItem.id}/move`, new FormData(event.currentTarget), {
                                forceFormData: true,
                                preserveScroll: true,
                              });
                            }}
                          >
                            <select name="parent_id" defaultValue={currentFolder.id} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                              {availableTargets.map((target) => (
                                <option key={target.id} value={target.id}>{target.name}</option>
                              ))}
                            </select>
                            <button type="submit" className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                              Move
                            </button>
                          </form>
                        </>
                      ) : null}

                      {activeFolderItem.can?.delete ? (
                        <button
                          type="button"
                          onClick={() => router.delete(`/organization/document-library/folders/${activeFolderItem.id}`, {
                            preserveScroll: true,
                          })}
                          className="mt-4 rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 hover:bg-rose-50"
                        >
                          Delete
                        </button>
                      ) : null}
                    </div>
                  ) : null}

                  {activeFile ? (
                    <div className="rounded-xl border p-4">
                      <div className="text-sm font-semibold">Working File Actions</div>
                      <div className="mt-1 text-xs text-muted-foreground">{activeFile.original_name}</div>

                      {activeFile.can?.download ? (
                        <a href={activeFile.download_url} className="mt-4 inline-block rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                          Download
                        </a>
                      ) : null}

                      {activeFile.can?.manage ? (
                        <>
                          <form
                            className="mt-4 space-y-3"
                            onSubmit={(event) => {
                              event.preventDefault();
                              router.post(`/organization/document-library/files/${activeFile.id}/rename`, new FormData(event.currentTarget), {
                                forceFormData: true,
                                preserveScroll: true,
                              });
                            }}
                          >
                            <input name="title" defaultValue={activeFile.title} className="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                            <textarea name="description" defaultValue={activeFile.description ?? ""} rows={3} className="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                            <button type="submit" className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                              Rename
                            </button>
                          </form>

                          <form
                            className="mt-4 space-y-3"
                            onSubmit={(event) => {
                              event.preventDefault();
                              router.post(`/organization/document-library/files/${activeFile.id}/move`, new FormData(event.currentTarget), {
                                forceFormData: true,
                                preserveScroll: true,
                              });
                            }}
                          >
                            <select name="folder_id" defaultValue={activeFile.folder_id} className="w-full rounded-md border bg-background px-3 py-2 text-sm">
                              {availableTargets.map((target) => (
                                <option key={target.id} value={target.id}>{target.name}</option>
                              ))}
                            </select>
                            <button type="submit" className="rounded-md border px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                              Move
                            </button>
                          </form>

                          <button
                            type="button"
                            onClick={() => router.delete(`/organization/document-library/files/${activeFile.id}`, { preserveScroll: true })}
                            className="mt-4 rounded-md border border-rose-300 px-3 py-2 text-sm text-rose-700 hover:bg-rose-50"
                          >
                            Delete
                          </button>
                        </>
                      ) : null}

                      {canPublishToVault ? (
                        <form
                          className="mt-6 space-y-3 rounded-xl border bg-slate-50 p-3"
                          onSubmit={(event) => {
                            event.preventDefault();
                            router.post(`/organization/document-library/files/${activeFile.id}/publish-to-vault`, new FormData(event.currentTarget), {
                              forceFormData: true,
                              preserveScroll: true,
                            });
                          }}
                        >
                          <div className="text-sm font-semibold">Publish Approved File To Official Vault</div>
                          <input name="title" defaultValue={activeFile.title} className="w-full rounded-md border bg-white px-3 py-2 text-sm" />
                          <select
                            name="document_type"
                            value={vaultDocumentType}
                            onChange={(event) => setVaultDocumentType(event.target.value)}
                            className="w-full rounded-md border bg-white px-3 py-2 text-sm"
                          >
                            {vaultDocumentTypes.map((documentType) => (
                              <option key={documentType.value} value={documentType.value}>{documentType.label}</option>
                            ))}
                          </select>
                          <select name="slot_key" defaultValue="" className="w-full rounded-md border bg-white px-3 py-2 text-sm">
                            <option value="">No replacement slot</option>
                            {filteredSlots.map((slot) => (
                              <option key={slot.value} value={slot.value}>{slot.label}</option>
                            ))}
                          </select>
                          <select name="audience_scope" defaultValue="all_staff" className="w-full rounded-md border bg-white px-3 py-2 text-sm">
                            <option value="all_staff">All staff</option>
                            <option value="department">Department</option>
                            <option value="selected_users">Selected users</option>
                          </select>
                          <select name="department_id" defaultValue="" className="w-full rounded-md border bg-white px-3 py-2 text-sm">
                            <option value="">No department target</option>
                            {departments.map((department) => (
                              <option key={department.id} value={department.id}>{department.name}</option>
                            ))}
                          </select>
                          <textarea name="description" defaultValue={activeFile.description ?? ""} rows={3} className="w-full rounded-md border bg-white px-3 py-2 text-sm" />
                          <label className="flex items-center gap-2 text-xs text-muted-foreground">
                            <input type="checkbox" name="replace_existing" value="1" />
                            Replace current document in slot
                          </label>
                          <label className="flex items-center gap-2 text-xs text-muted-foreground">
                            <input type="checkbox" name="is_active" value="1" defaultChecked />
                            Active immediately
                          </label>
                          <div className="grid gap-2">
                            <input name="effective_from" type="date" className="rounded-md border bg-white px-3 py-2 text-sm" />
                            <input name="effective_until" type="date" className="rounded-md border bg-white px-3 py-2 text-sm" />
                          </div>
                          <div className="grid max-h-40 gap-2 overflow-auto rounded-md border bg-white p-2">
                            {users.map((user) => (
                              <label key={user.id} className="flex items-center gap-2 text-xs text-muted-foreground">
                                <input type="checkbox" name="selected_user_ids[]" value={user.id} />
                                <span>{user.name}</span>
                              </label>
                            ))}
                          </div>
                          <button type="submit" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                            Publish To Vault
                          </button>
                        </form>
                      ) : null}
                    </div>
                  ) : null}
                </div>
              </div>
            ) : (
              <div className="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div className="rounded-xl border border-dashed p-8 text-sm text-muted-foreground">
                  Select a workspace from the tree to view and manage its contents.
                </div>
                <form
                  className="rounded-xl border p-4"
                  onSubmit={(event) => {
                    event.preventDefault();
                    router.post("/organization/document-library/root-folders", new FormData(event.currentTarget), {
                      forceFormData: true,
                      preserveScroll: true,
                    });
                  }}
                >
                  <div className="text-sm font-semibold">Create Working Workspace</div>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Create a separate working workspace for an owner. Approved outputs can be published to the official vault later.
                  </p>
                  <input name="name" placeholder="Workspace name" className="mt-4 w-full rounded-md border bg-background px-3 py-2 text-sm" />
                  <select
                    name="owner_type"
                    value={selectedOwnerType}
                    onChange={(event) => {
                      const nextType = event.target.value;
                      setSelectedOwnerType(nextType);
                      const nextGroup = ownerOptions.find((group) => group.owner_type === nextType);
                      setSelectedOwnerId(nextGroup?.items[0] ? String(nextGroup.items[0].id) : "");
                    }}
                    className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm"
                  >
                    {ownerOptions.map((group) => (
                      <option key={group.owner_type} value={group.owner_type}>{group.label}</option>
                    ))}
                  </select>
                  <select
                    name="owner_id"
                    value={selectedOwnerId}
                    onChange={(event) => setSelectedOwnerId(event.target.value)}
                    className="mt-3 w-full rounded-md border bg-background px-3 py-2 text-sm"
                  >
                    <option value="">Choose owner</option>
                    {selectedOwnerGroup?.items.map((item) => (
                      <option key={item.id} value={item.id}>{item.name}</option>
                    ))}
                  </select>
                  <button type="submit" className="mt-4 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
                    Create Workspace
                  </button>
                </form>
              </div>
            )}
          </section>
        </div>
      </div>
    </AppLayout>
  );
}
