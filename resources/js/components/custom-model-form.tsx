import { useForm } from "@inertiajs/react";
import { Check, Info, SquarePlus } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { useEffect, useState, type ReactNode } from "react";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

/* =========================================================
| TYPES
========================================================= */

type RouteFn = (args?: any) => {
  url: string;
  method: "post" | "put" | "patch";
};

type FieldConfig = {
  id: string;
  name: string;
  label: string;
  type: string;
  multiple?: boolean;
  optionsSource?: string;
  optionLabel?: string;
  optionValue?: string;
  options?: Array<{ label: string; value: string | number }>;
  placeholder?: string;
  autoFocus?: boolean;
  required?: boolean;
  rows?: number;
  min?: number;
  maxLength?: number;
};

type CustomModalFormProps = {
  addButton?: {
    label: string;
    icon?: LucideIcon;
    variant?: string | null;
    className?: string;
  };
  title: string;
  description?: string;
  fields: FieldConfig[];
  mode?: "create" | "edit" | "view";
  initialData?: Record<string, any>;
  submitRoute: RouteFn;
  routeParams?: any;
  options?: Record<string, any[]>;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  hideTrigger?: boolean;
  children?: ReactNode;
  keepOpenOnSuccess?: boolean;
  preserveOnSuccessFields?: string[];
};

const buildNestedPayload = (flat: Record<string, any>) => {
  const result: Record<string, any> = {};

  Object.entries(flat).forEach(([key, value]) => {
    if (!key.includes(".")) {
      result[key] = value;
      return;
    }

    const parts = key.split(".");
    let current = result as Record<string, any>;

    for (let i = 0; i < parts.length - 1; i += 1) {
      const part = parts[i];

      if (current[part] === undefined || typeof current[part] !== "object") {
        current[part] = {};
      }

      current = current[part];
    }

    current[parts[parts.length - 1]] = value;
  });

  return result;
};

/* =========================================================
| COMPONENT
========================================================= */

export const CustomModelForm = ({
  addButton,
  title,
  description,
  fields,
  mode = "create",
  initialData = {},
  submitRoute,
  routeParams,
  options = {},
  open,
  onOpenChange,
  hideTrigger = false,
  children,
  keepOpenOnSuccess = false,
  preserveOnSuccessFields = [],
}: CustomModalFormProps) => {
  const getDynamicOptions = (source?: string) => {
    if (!source) return [];

    const value = options?.[source];
    if (Array.isArray(value)) return value;

    if (value && typeof value === "object" && Array.isArray((value as any).data)) {
      return (value as any).data;
    }

    return [];
  };

  /* ------------------------------
   | Dialog control
  ------------------------------ */
  const [internalOpen, setInternalOpen] = useState(false);
  const dialogOpen = open ?? internalOpen;
  const setDialogOpen = onOpenChange ?? setInternalOpen;

  const isView = mode === "view";

  /* ------------------------------
   | Form state
  ------------------------------ */
  const form = useForm(
    fields.reduce((acc, field) => {
      acc[field.name] = initialData[field.name] ?? (field.multiple ? [] : "");
      return acc;
    }, {} as Record<string, any>)
  );

  const { data, setData, processing, errors, reset } = form;

  /* ------------------------------
   | Hydration logic (FIXED)
  ------------------------------ */
  useEffect(() => {
    if (dialogOpen) {
      fields.forEach((field) => {
        const value = initialData[field.name];

        if (field.type === "select") {
          if (field.multiple) {
            setData(
              field.name,
              Array.isArray(value) ? value.map((item) => String(item)) : []
            );
          } else {
            setData(field.name, value !== null && value !== undefined ? String(value) : "");
          }
        } else {
          setData(field.name, value ?? "");
        }
      });
    }
  }, [dialogOpen, fields, initialData, setData]);


  /* ------------------------------
   | SUBMIT
  ------------------------------ */
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (isView) return;

    const payload = { ...data };

    // Normalize date fields
    fields.forEach((field) => {
      if (field.type === "date" && payload[field.name]) {
        payload[field.name] = payload[field.name].slice(0, 10);
      }
    });

    const nestedPayload = buildNestedPayload(payload);

    const routeDef = submitRoute(routeParams);

    form.transform(() => nestedPayload);
    form.submit(routeDef.method, routeDef.url, {
      preserveScroll: true,
      onSuccess: () => {
        if (preserveOnSuccessFields.length > 0) {
          const preserved: Record<string, any> = {};
          preserveOnSuccessFields.forEach((field) => {
            preserved[field] = data[field];
          });
          reset();
          preserveOnSuccessFields.forEach((field) => {
            setData(field, preserved[field] ?? "");
          });
        } else {
          reset();
        }

        if (!keepOpenOnSuccess) {
          setDialogOpen(false);
        }
      },
    });
  };

  const Icon = addButton?.icon;
  const HeaderIcon = Icon ?? SquarePlus;
  const primaryActionLabel = mode === "edit" ? "Save Changes" : title.toLowerCase().includes("program") ? "Save Program" : "Save";

  const characterLimit = (field: FieldConfig) => {
    if (field.maxLength) return field.maxLength;
    if (field.name.toLowerCase().includes("title")) return 100;
    if (field.type === "textarea" || field.name.toLowerCase().includes("description")) return 500;

    return null;
  };

  /* ------------------------------
   | RENDER
  ------------------------------ */
  return (
    <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
      {!hideTrigger && addButton && (
        <DialogTrigger asChild>
          <Button
            variant={addButton.variant as React.ComponentProps<typeof Button>["variant"]}
            className={addButton.className}
          >
            {Icon && <Icon className="h-4 w-4 mr-2" />}
            {addButton.label}
          </Button>
        </DialogTrigger>
      )}

      <DialogContent className="max-h-[90vh] overflow-hidden p-0 sm:max-w-[760px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader className="px-7 pb-3 pt-7">
            <div className="flex items-start gap-4 pr-10">
              <span className="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-red-100 to-orange-100 text-red-600 ring-8 ring-orange-50">
                <HeaderIcon className="h-7 w-7" />
              </span>
              <div className="pt-1">
                <DialogTitle className="text-2xl font-semibold tracking-normal">
                  {title}
                </DialogTitle>

                {description && (
                  <DialogDescription className="mt-1">
                    {description}
                  </DialogDescription>
                )}
              </div>
            </div>
          </DialogHeader>


          <div className="max-h-[calc(90vh-12rem)] overflow-y-auto px-7 pb-6 pt-3">
            <div className="grid grid-cols-1 gap-x-7 gap-y-5 sm:grid-cols-2">
              {fields.map((field) => {
                const limit = characterLimit(field);
                const currentLength = String(data[field.name] ?? "").length;

                return (
                <div key={field.id} className={field.type === "textarea" ? "grid gap-2" : "grid gap-2"}>
                  <Label htmlFor={field.id} className="text-sm font-semibold text-slate-950">
                    {field.label}
                    {field.required ? <span className="text-red-600"> *</span> : null}
                  </Label>

                {/* TEXT INPUTS */}
                {["text", "email", "number", "tel", "date"].includes(
                  field.type
                ) && (
                  <Input
                    id={field.id}
                    type={field.type}
                    value={data[field.name]}
                    maxLength={limit ?? undefined}
                    placeholder={field.placeholder}
                    disabled={isView}
                    onChange={(e) =>
                      setData(field.name, e.target.value)
                    }
                    className="h-11 rounded-lg border-slate-200 bg-white px-4 text-sm shadow-sm focus-visible:ring-orange-300"
                  />
                )}

                {/* TEXTAREA */}
                {field.type === "textarea" && (
                  <textarea
                    id={field.id}
                    rows={field.rows ?? 4}
                    value={data[field.name]}
                    maxLength={limit ?? undefined}
                    placeholder={field.placeholder}
                    disabled={isView}
                    onChange={(e) =>
                      setData(field.name, e.target.value)
                    }
                    className="min-h-28 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-orange-300 focus:ring-2 focus:ring-orange-200"
                  />
                )}

                {/* SELECT (PROVINCES FIX) */}
                {field.type === "select" && (
                  <select
                    id={field.id}
                    value={data[field.name]}
                    multiple={field.multiple}
                    disabled={isView}
                    onChange={(e) =>
                      setData(
                        field.name,
                        field.multiple
                          ? Array.from(e.target.selectedOptions, (option) => option.value)
                          : e.target.value
                      )
                    }
                    className="min-h-11 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-orange-300 focus:ring-2 focus:ring-orange-200"
                  >
                    {!field.multiple && <option value="">Select option</option>}

                    {/* 🔹 STATIC OPTIONS (gender, enums, etc.) */}
                    {field.options?.map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}

                    {/* 🔹 DYNAMIC OPTIONS (provinces, roles, etc.) */}
                    {!field.options && field.optionsSource &&
                      getDynamicOptions(field.optionsSource).map((opt: any) => (
                        <option
                          key={opt[field.optionValue ?? "id"]}
                          value={opt[field.optionValue ?? "id"]}
                        >
                          {opt[field.optionLabel ?? "name"]}
                        </option>
                      ))}
                  </select>
                )}

                {field.type === "select" && field.multiple && (
                  <p className="text-xs text-slate-500">
                    Hold Ctrl or Cmd to select multiple options.
                  </p>
                )}

                {field.name.toLowerCase().includes("slug") ? (
                  <p className="text-xs leading-relaxed text-slate-500">
                    This will be used in URLs. Use lowercase letters, numbers, and hyphens.
                  </p>
                ) : null}

                {limit ? (
                  <p className="text-right text-xs text-slate-500">{currentLength} / {limit}</p>
                ) : null}

                {errors[field.name] && (
                  <p className="text-xs text-red-600">
                    {errors[field.name]}
                  </p>
                )}
                </div>
              )})}
            </div>

            {children && (
              <div className="mt-6">{children}</div>
            )}

            {fields.some((field) => field.name.toLowerCase().includes("slug")) ? (
              <div className="mt-7 grid gap-5 sm:grid-cols-2">
                <div className="rounded-lg border border-blue-200 bg-blue-50/70 p-4">
                  <div className="flex items-center gap-2 text-sm font-semibold text-blue-700">
                    <Info className="h-4 w-4" />
                    Slug Preview
                  </div>
                  <div className="mt-4 rounded-lg border border-dashed border-blue-300 bg-white/70 px-4 py-3 text-sm text-slate-500">
                    {String(data.slug ?? "").trim() || "Your slug will appear here"}
                  </div>
                </div>

                <div className="rounded-lg border border-emerald-200 bg-emerald-50/70 p-4">
                  <div className="flex items-center gap-2 text-sm font-semibold text-emerald-700">
                    <Check className="h-4 w-4" />
                    Guidelines
                  </div>
                  <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                    <li>Keep the title short and descriptive</li>
                    <li>Slug must be unique</li>
                    <li>You can edit these details later</li>
                  </ul>
                </div>
              </div>
            ) : null}
          </div>

          <div className="border-t bg-white px-7 py-5">
            <DialogFooter>
              <DialogClose asChild>
                <Button type="button" variant="outline" className="rounded-lg border-slate-200 bg-white px-4 text-slate-700 hover:bg-slate-50">
                  Cancel
                </Button>
              </DialogClose>

              {!isView && (
                <Button type="submit" disabled={processing} className="rounded-lg bg-red-600 px-5 font-semibold text-white hover:bg-red-700">
                  {processing ? "Saving..." : primaryActionLabel}
                </Button>
              )}
            </DialogFooter>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};




