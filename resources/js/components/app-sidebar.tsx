import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Briefcase, BriefcaseBusiness, Building2, ClipboardCheck, LayoutGrid, Package, ShieldCheck, UserCircle } from 'lucide-react';

import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { hasAnyPermission, hasAnyRole } from '@/lib/access';
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';

import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Beneficiaries',
        href: '/beneficiaries',
        icon: UserCircle,
        requiredPermissions: ['domain.beneficiaries.view', 'domain.beneficiaries.manage'],
    },
    {
        title: 'Stakeholders',
        href: '/stakeholders',
        icon: UserCircle,
        requiredPermissions: ['domain.stakeholders.view', 'domain.stakeholders.manage'],
    },
    {
        title: 'Facilitators',
        href: '/facilitators',
        icon: UserCircle,
        requiredPermissions: ['domain.facilitators.view', 'domain.facilitators.manage'],
    },
    {
        title: 'Human Resources',
        href: '/human-resources',
        icon: Building2,
        requiredPermissions: ['domain.human-resources.view', 'domain.human-resources.manage'],
    },
    {
        title: 'Assets',
        href: '/assets',
        icon: Package,
        requiredPermissions: ['domain.assets.view', 'domain.assets.manage'],
    },
    {
        title: 'Programs',
        href: '/programs',
        icon: BookOpen,
        requiredPermissions: ['domain.programs.view', 'domain.programs.manage'],
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: Briefcase,
        requiredPermissions: ['domain.projects.view', 'domain.projects.manage'],
    },
    {
        title: 'Business Development',
        href: '/business-development',
        icon: BriefcaseBusiness,
        requiredPermissions: ['domain.business-development.view', 'domain.business-development.manage'],
    },
    {
        title: 'Facilitator Activities',
        href: '/project-locations/dashboard',
        icon: ClipboardCheck,
        requiredPermissions: ['project-activities.view'],
    },
    {
        title: 'Access Control',
        href: '/access-control/roles',
        icon: ShieldCheck,
        requiredRoles: ['super-admin', 'super admin', 'admin'],
        requiredPermissions: ['access-control.view'],
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const user = auth?.user;
    const visibleMainNavItems = mainNavItems.filter(
        (item) =>
            hasAnyRole(user, item.requiredRoles ?? []) &&
            hasAnyPermission(user, item.requiredPermissions ?? []),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleMainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
