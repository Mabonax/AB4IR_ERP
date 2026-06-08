import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import {
  CalendarCheck2,
  CheckCircle,
  ClipboardCheck,
  Eye,
  Pencil,
  Send,
  Trash2,
  Undo2,
  XCircle,
} from "lucide-react";
import type { LucideIcon } from "lucide-react";
import type { ReactNode } from "react";

type RowData = Record<string, any>;

interface TableColumn<T extends RowData = RowData> {
  key: string;
  label: string;
  className?: string;
  isAction?: boolean;
  render?: (row: T) => ReactNode;
}

interface TableAction {
  icon: keyof typeof tableActionIcons | LucideIcon;
  label?: string;
  onClick?: (row: any) => void;
  route?: string;
  href?: string | ((row: any) => string);
  method?: "get" | "post" | "put" | "delete";
  variant?: "danger" | "primary";
  visible?: (row: any) => boolean;
}

interface CustomTableProps<T extends RowData = RowData> {
  columns: TableColumn<T>[];
  actions?: TableAction[];
  data?: T[];
}

const tableActionIcons = {
  CalendarCheck2,
  CheckCircle,
  ClipboardCheck,
  Eye,
  Pencil,
  PencilIcon: Pencil,
  Send,
  Trash2,
  Undo2,
  XCircle,
} satisfies Record<string, LucideIcon>;

export const CustomTable = <T extends RowData>({
  columns,
  actions = [],
  data = [],
}: CustomTableProps<T>) => {
  const RenderActions = ({ row }: { row: T }) => (
    <div className="flex flex-wrap gap-2">
      {actions.map((action: TableAction, index: number) => {
        if (action.visible && !action.visible(row)) {
          return null;
        }

        const Icon: LucideIcon | undefined =
          typeof action.icon === "string"
            ? tableActionIcons[action.icon as keyof typeof tableActionIcons]
            : action.icon;
        const label = action.label ?? (typeof action.icon === "string" ? action.icon : "Action");
        const baseClassName = `inline-flex items-center justify-center rounded-md border p-2 transition
          ${
            action.variant === "danger"
              ? "border-red-600 text-red-600 hover:bg-red-600 hover:text-white"
              : "border-orange-500 text-orange-600 hover:bg-orange-500 hover:text-white"
          }`;

        /* ===============================
         * CASE 1: onClick (modal / custom logic)
         =============================== */
        if (action.onClick) {
          return (
            <button
              key={index}
              type="button"
              onClick={() => action.onClick?.(row)}
              className={baseClassName}
              title={label}
              aria-label={label}
            >
              {Icon ? <Icon size={14} className="opacity-90" /> : <span className="font-semibold">?</span>}
            </button>
          );
        }

        /* ===============================
         * CASE 2: route (navigation / delete)
         =============================== */
        if (action.route) {
          return (
            <Link
              key={index}
              as="button"
              href={route(action.route, row.id)}
              method={action.method ?? "get"}
              className={baseClassName}
              title={label}
              aria-label={label}
            >
              {Icon ? <Icon size={14} className="opacity-90" /> : <span className="font-semibold">?</span>}
            </Link>
          );
        }

        if (action.href) {
          const href = typeof action.href === "function" ? action.href(row) : action.href;

          return (
            <Link
              key={index}
              as="button"
              href={href}
              method={action.method ?? "get"}
              className={baseClassName}
              title={label}
              aria-label={label}
            >
              {Icon ? <Icon size={14} className="opacity-90" /> : <span className="font-semibold">?</span>}
            </Link>
          );
        }

        return null;
      })}
    </div>
  );

  return (
    <div
      className="
        overflow-hidden rounded-lg border shadow-sm
        bg-card
        border-gray-200 dark:border-border
      "
    >
      <table className="min-w-full divide-y divide-gray-200 dark:divide-border">
        {/* ===================== HEADER (BLUE – DO NOT DARKEN) ===================== */}
        <thead className="bg-gradient-to-r from-red-600 to-orange-500 text-white">
          <tr>
            {columns.map((col: any) => (
              <th
                key={col.key}
                className={`
                  ${col.className}
                  text-sm font-semibold
                `}
              >
                {col.label}
              </th>
            ))}
          </tr>
        </thead>

        {/* ===================== BODY ===================== */}
        <tbody
          className="
            divide-y
            divide-gray-100 dark:divide-border
            bg-card
          "
        >
          {data.length === 0 ? (
            <tr>
              <td
                colSpan={columns.length}
                className="
                  px-4 py-4 text-center
                  text-gray-500 dark:text-muted-foreground
                "
              >
                No data available
              </td>
            </tr>
          ) : (
            data.map((row) => (
                <tr
                  key={row.id}
                  className="
                  hover:bg-gray-50 dark:hover:bg-accent
                  transition-colors
                "
              >
                {columns.map((col: any) => (
                  <td
                    key={col.key}
                    className={`
                      ${col.className}
                      text-gray-900 dark:text-foreground
                    `}
                  >
                    {col.isAction ? (
                      <RenderActions row={row} />
                    ) : col.render ? (
                      col.render(row)
                    ) : (
                      row[col.key] ?? "-"
                    )}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
};
