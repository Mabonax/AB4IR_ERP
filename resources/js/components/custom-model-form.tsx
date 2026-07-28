import { useForm } from "@inertiajs/react";
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

      <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto p-0">
        <form onSubmit={handleSubmit}>
          <DialogHeader className="bg-gradient-to-r border-red-600 bg-red-600  text-white px-6 py-4 rounded-t-lg">
            <DialogTitle className="text-lg font-semibold">
              {title}
            </DialogTitle>

            {description && (
              <DialogDescription className="text-white/90">
                {description}
              </DialogDescription>
            )}
          </DialogHeader>


          <div className="p-6">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              {fields.map((field) => (
                <div key={field.id} className="grid gap-2">
                  <Label>{field.label}</Label>

                {/* TEXT INPUTS */}
                {["text", "email", "number", "tel", "date"].includes(
                  field.type
                ) && (
                  <Input
                    type={field.type}
                    value={data[field.name]}
                    disabled={isView}
                    onChange={(e) =>
                      setData(field.name, e.target.value)
                    }
                  />
                )}

                {/* TEXTAREA */}
                {field.type === "textarea" && (
                  <textarea
                    rows={3}
                    value={data[field.name]}
                    disabled={isView}
                    onChange={(e) =>
                      setData(field.name, e.target.value)
                    }
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  />
                )}

                {/* SELECT (PROVINCES FIX) */}
                {field.type === "select" && (
                  <select
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
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
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
                  <p className="text-xs text-muted-foreground">
                    Hold Ctrl or Cmd to select multiple options.
                  </p>
                )}


                {errors[field.name] && (
                  <p className="text-xs text-red-600">
                    {errors[field.name]}
                  </p>
                )}
                </div>
              ))}
            </div>

            {children && (
              <div className="mt-6">{children}</div>
            )}

            <DialogFooter className="mt-6">
              <DialogClose asChild>
                <Button type="button" variant="outline">
                  Close
                </Button>
              </DialogClose>

              {!isView && (
                <Button type="submit" disabled={processing}>
                  {processing ? "Saving..." : "Save"}
                </Button>
              )}
            </DialogFooter>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};




