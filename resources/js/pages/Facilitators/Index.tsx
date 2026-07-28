import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";

import { ConfirmDeleteModal } from "@/components/confirm-delete-modal";
import { CustomTable } from "@/components/custom-table";
import { FacilitatorTableConfig } from "@/config/tables/facilitator-table";
import AppLayout from "@/layouts/app-layout";
import facilitators from "@/routes/facilitators";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Facilitators", href: facilitators.index() },
];

export default function FacilitatorIndex({
  facilitators: facilitatorPagination,
  canManageFacilitators,
}: {
  facilitators: { data: any[] };
  canManageFacilitators: boolean;
}) {
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [facilitatorToDelete, setFacilitatorToDelete] = useState<any | null>(null);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Facilitators" />

      <div className="p-4 space-y-4">
        <div className="flex justify-between gap-3">
          <h1 className="text-xl font-semibold">Facilitators</h1>

          {canManageFacilitators ? (
            <Link href={facilitators.create().url} className="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">
              Add Facilitator
            </Link>
          ) : null}
        </div>

        <CustomTable
          columns={FacilitatorTableConfig.columns}
          data={facilitatorPagination.data}
          actions={[
            {
              icon: "Eye" as const,
              onClick: (row) => {
                router.visit(facilitators.show(row.id).url);
              },
            },
            ...(canManageFacilitators
              ? [
                  {
                    icon: "PencilIcon" as const,
                    onClick: (row: any) => {
                      router.visit(facilitators.edit(row.id).url);
                    },
                  },
                  {
                    icon: "Trash2" as const,
                    variant: "danger" as const,
                    onClick: (row: any) => {
                      setFacilitatorToDelete(row);
                      setDeleteOpen(true);
                    },
                  },
                ]
              : []),
          ]}
        />

        {facilitatorToDelete && (
          <ConfirmDeleteModal
            open={deleteOpen}
            onOpenChange={setDeleteOpen}
            title="Delete Facilitator"
            submitRoute={facilitators.destroy}
            routeParams={facilitatorToDelete.id}
          />
        )}
      </div>
    </AppLayout>
  );
}
