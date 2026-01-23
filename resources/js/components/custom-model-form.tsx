import { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";

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
  type: "text" | "email" | "number" | "tel" | "date" | "textarea" | "select";
  optionsSource?: string;
  optionLabel?: string;
  optionValue?: string;
};

type CustomModalFormProps = {
  addButton?: {
    label: string;
    icon?: any;
    variant?: any;
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
}: CustomModalFormProps) => {
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
      acc[field.name] = initialData[field.name] ?? "";
      return acc;
    }, {} as Record<string, any>)
  );

  const { data, setData, processing, errors, reset } = form;

  /* ------------------------------
   | Hydration logic (FIXED)
  ------------------------------ */
useEffect(() => {
  if ((mode === "edit" || mode === "view") && dialogOpen) {
    fields.forEach((field) => {
      const value = initialData[field.name];

      if (field.type === "select") {
        setData(field.name, value !== null && value !== undefined ? String(value) : "");
      } else {
        setData(field.name, value ?? "");
      }
    });
  }

  if (mode === "create" && dialogOpen) {
    reset();
  }
}, [mode, dialogOpen]);


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

    const routeDef = submitRoute(routeParams);

    form.submit(routeDef.method, routeDef.url, {
      data: payload,
      preserveScroll: true,
      onSuccess: () => {
        reset();
        setDialogOpen(false);
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
            variant={addButton.variant}
            className={addButton.className}
          >
            {Icon && <Icon className="h-4 w-4 mr-2" />}
            {addButton.label}
          </Button>
        </DialogTrigger>
      )}

      <DialogContent className="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            {description && (
              <DialogDescription>{description}</DialogDescription>
            )}
          </DialogHeader>

          <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                    className="rounded-md border px-3 py-2 text-sm"
                  />
                )}

                {/* SELECT (PROVINCES FIX) */}
                {field.type === "select" && (
                  <select
                    value={data[field.name]}
                    disabled={isView}
                    onChange={(e) => setData(field.name, e.target.value)}
                    className="rounded-md border px-3 py-2 text-sm"
                  >
                    <option value="">Select option</option>

                    {/* 🔹 STATIC OPTIONS (gender, enums, etc.) */}
                    {field.options?.map((opt: any) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}

                    {/* 🔹 DYNAMIC OPTIONS (provinces, roles, etc.) */}
                    {!field.options && field.optionsSource &&
                      options?.[field.optionsSource]?.map((opt: any) => (
                        <option
                          key={opt[field.optionValue!]}
                          value={opt[field.optionValue!]}
                        >
                          {opt[field.optionLabel!]}
                        </option>
                      ))}
                  </select>
                )}


                {errors[field.name] && (
                  <p className="text-xs text-red-600">
                    {errors[field.name]}
                  </p>
                )}
              </div>
            ))}
          </div>

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
        </form>
      </DialogContent>
    </Dialog>
  );
};
