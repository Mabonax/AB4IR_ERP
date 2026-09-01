import { router } from "@inertiajs/react";
import type { Method } from "@inertiajs/core";
import { Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";

type RouteFn = (args?: any) => { url: string; method: Method };

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  submitRoute: RouteFn;
  routeParams?: any;
};

export function ConfirmDeleteModal({
  open,
  onOpenChange,
  title,
  description,
  submitRoute,
  routeParams,
}: Props) {
  const handleDelete = () => {
    const routeDef = submitRoute(routeParams);

    router.visit(routeDef.url, {
      method: routeDef.method,
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="overflow-hidden p-0 sm:max-w-[520px]">
        <DialogHeader className="px-7 pb-4 pt-7">
          <div className="flex items-start gap-4 pr-10">
            <span className="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-red-100 to-orange-100 text-red-600 ring-8 ring-orange-50">
              <Trash2 className="h-7 w-7" />
            </span>
            <div className="pt-1">
              <DialogTitle className="text-2xl font-semibold tracking-normal">{title}</DialogTitle>
              <DialogDescription className="mt-1">
                {description ?? "This action cannot be undone. Confirm that you want to delete this record."}
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <DialogFooter className="border-t px-7 py-5">
          <Button variant="outline" className="rounded-lg border-slate-200 bg-white px-4 text-slate-700 hover:bg-slate-50" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button variant="destructive" className="rounded-lg px-5 font-semibold" onClick={handleDelete}>
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
