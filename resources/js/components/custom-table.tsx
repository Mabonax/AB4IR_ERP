import { Link } from "@inertiajs/react";
import * as LucideIcons from "lucide-react";

interface TableAction {
  icon: keyof typeof LucideIcons;
  onClick?: (row: any) => void;
  route?: string;
  method?: "get" | "post" | "put" | "delete";
  variant?: "danger" | "primary";
}

export const CustomTable = ({ columns, actions, data = [] }: any) => {
  const RenderActions = ({ row }: { row: any }) => (
    <div className="flex gap-2">
      {actions.map((action: TableAction, index: number) => {
        const Icon = LucideIcons[action.icon];

        /* ===============================
         * CASE 1: onClick (modal / custom logic)
         =============================== */
        if (action.onClick) {
          return (
            <button
              key={index}
              type="button"
              onClick={() => action.onClick?.(row)}
              className={`inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md border transition
                ${
                  action.variant === "danger"
                    ? "border-red-600 text-red-600 hover:bg-red-600 hover:text-white"
                    : "border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white"
                }
              `}
            >
              {Icon && <Icon size={14} className="opacity-90" />}
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
              className={`inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md border transition
                ${
                  action.variant === "danger"
                    ? "border-red-600 text-red-600 hover:bg-red-600 hover:text-white"
                    : "border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white"
                }
              `}
            >
              {Icon && <Icon size={14} className="opacity-90" />}
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
        bg-white dark:bg-gray-900
        border-gray-200 dark:border-gray-700
      "
    >
      <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        {/* ===================== HEADER (BLUE – DO NOT DARKEN) ===================== */}
        <thead className="bg-blue-600 text-white">
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
            divide-gray-100 dark:divide-gray-700
            bg-white dark:bg-gray-900
          "
        >
          {data.length === 0 ? (
            <tr>
              <td
                colSpan={columns.length}
                className="
                  px-4 py-4 text-center
                  text-gray-500 dark:text-gray-400
                "
              >
                No data available
              </td>
            </tr>
          ) : (
            data.map((row: any) => (
              <tr
                key={row.id}
                className="
                  hover:bg-gray-50 dark:hover:bg-gray-800
                  transition-colors
                "
              >
                {columns.map((col: any) => (
                  <td
                    key={col.key}
                    className={`
                      ${col.className}
                      text-gray-900 dark:text-gray-100
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
