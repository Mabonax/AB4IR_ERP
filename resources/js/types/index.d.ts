import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    badgeCount?: number;
    requiredPermissions?: string[];
    requiredRoles?: string[];
}

export interface SharedData {
    name: string;
    auth: Auth;
    organization?: {
        id?: number;
        name: string;
        legal_name?: string | null;
        tagline?: string | null;
        mission?: string | null;
        vision?: string | null;
        objectives?: string | null;
        focus_areas?: string | null;
        about?: string | null;
        service_offering?: string | null;
        website?: string | null;
        email?: string | null;
        phone?: string | null;
        address_line_1?: string | null;
        address_line_2?: string | null;
        city?: string | null;
        province?: string | null;
        country?: string | null;
        postal_code?: string | null;
        primary_logo_url?: string | null;
        light_logo_url?: string | null;
        dark_logo_url?: string | null;
        icon_logo_url?: string | null;
        impact_summary?: {
            total?: number | null;
            digital?: number | null;
            physical?: number | null;
            trainings_conducted?: number | null;
        };
        impact_channels?: Array<{
            label: string;
            value?: number | null;
        }>;
        updated_at?: string | null;
    };
    notifications?: {
        unread_count: number;
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    unread_notifications_count?: number;
    roles?: string[];
    permissions?: string[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
