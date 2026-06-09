import { Link, usePage } from '@inertiajs/react';
import { Bell, BookOpen, Briefcase, BriefcaseBusiness, Building2, CalendarRange, ClipboardCheck, Download, FolderTree, LayoutGrid, LifeBuoy, Megaphone, Package, ReceiptText, ShieldCheck, UserCircle } from 'lucide-react';

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
        title: 'Organization',
        href: '/organization',
        icon: Building2,
        requiredPermissions: ['domain.organization.view', 'domain.organization.manage'],
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
        title: 'Events',
        href: '/events',
        icon: CalendarRange,
        requiredPermissions: ['domain.events.view', 'domain.events.manage'],
    },
    {
        title: 'Task Management',
        href: '/task-management',
        icon: LifeBuoy,
        requiredPermissions: ['domain.task-management.view', 'domain.task-management.manage'],
    },
    {
        title: 'Marketing',
        href: '/marketing',
        icon: Megaphone,
        requiredPermissions: ['domain.marketing.view', 'domain.marketing.manage'],
    },
    {
        title: 'Finance',
        href: '/finance/travel-claims',
        icon: ReceiptText,
        requiredPermissions: ['domain.finance.view', 'domain.finance.manage', 'travel-claims.submit'],
    },
    {
        title: 'Notifications',
        href: '/notifications',
        icon: Bell,
    },
    {
        title: 'Document Library',
        href: '/organization/document-library',
        icon: FolderTree,
        requiredPermissions: [
            'domain.organization.view',
            'domain.organization.manage',
            'domain.programs.view',
            'domain.programs.manage',
            'domain.projects.view',
            'domain.projects.manage',
            'domain.beneficiaries.view',
            'domain.beneficiaries.manage',
            'domain.stakeholders.view',
            'domain.stakeholders.manage',
            'domain.human-resources.view',
            'domain.human-resources.manage',
        ],
    },
    {
        title: 'Official Vault',
        href: '/organization/documents',
        icon: Download,
        requiredPermissions: ['domain.organization.view', 'domain.organization.manage'],
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
        requiredRoles: ['super-admin', 'super admin'],
    },
];

export function AppSidebar() {
    const { auth, notifications } = usePage<SharedData>().props;
    const user = auth?.user;
    const isAdminUser = hasAnyRole(user, ['super-admin', 'super admin', 'admin']);
    const isBusinessDevelopmentUser = hasAnyPermission(user, [
        'domain.business-development.view',
        'domain.business-development.manage',
    ]);
    const restrictBeneficiariesForBds = isBusinessDevelopmentUser && !isAdminUser;

    const itemsWithBadges = mainNavItems.map((item) => ({
        ...item,
        badgeCount: item.href === '/notifications' ? (notifications?.unread_count ?? 0) : undefined,
    }));

    const visibleMainNavItems = itemsWithBadges.filter(
        (item) =>
            hasAnyRole(user, item.requiredRoles ?? []) &&
            hasAnyPermission(user, item.requiredPermissions ?? []) &&
            (!restrictBeneficiariesForBds || item.href !== '/beneficiaries'),
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
