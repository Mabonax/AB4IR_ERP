import { type User } from "@/types";

export const hasAnyRole = (user: User | null | undefined, roles: string[] = []): boolean => {
  if (!roles.length) return true;
  const userRoles = new Set((user?.roles ?? []) as string[]);
  return roles.some((role) => userRoles.has(role));
};

export const hasAnyPermission = (user: User | null | undefined, permissions: string[] = []): boolean => {
  if (!permissions.length) return true;
  const userPermissions = new Set((user?.permissions ?? []) as string[]);
  const userRoles = (user?.roles ?? []) as string[];
  const isSuperAdmin = userRoles.includes("super-admin") || userRoles.includes("super admin");
  if (isSuperAdmin) return true;
  return permissions.some((permission) => userPermissions.has(permission));
};
