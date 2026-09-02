import { Link, usePage } from '@inertiajs/react';
import { Bell, BookOpen, Briefcase, BriefcaseBusiness, Building2, CalendarRange, ClipboardCheck, Download, FolderTree, LayoutGrid, LifeBuoy, Megaphone, Package, ReceiptText, ShieldCheck, UserCircle } from 'lucide-react';

import { type DomainNavItem } from '@/components/domain-nav';
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
import { accessControlNavItems } from '@/config/domain-nav/access-control';
import { assetNavItems } from '@/config/domain-nav/assets';
import { businessDevelopmentNavItems } from '@/config/domain-nav/business-development';
import { eventNavItems } from '@/config/domain-nav/events';
import { financeNavItems } from '@/config/domain-nav/finance';
import { humanResourcesNavItems } from '@/config/domain-nav/human-resources';
import { marketingNavItems } from '@/config/domain-nav/marketing';
import { organizationNavItems } from '@/config/domain-nav/organization';
import { programNavItems } from '@/config/domain-nav/programs';
import { projectNavItems } from '@/config/domain-nav/projects';
import { taskManagementNavItems } from '@/config/domain-nav/task-management';
import { hasAnyPermission, hasAnyRole } from '@/lib/access';
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';

import AppLogo from './app-logo';

const toSidebarSubItems = (items: DomainNavItem[]): NavItem[] =>
    items
        .filter((item) => !item.native && !item.href.startsWith('#'))
        .map((item) => ({
            title: item.label,
            href: item.href,
            requiredPermissions: item.requiredPermissions,
            requiredRoles: item.requiredRoles,
        }));

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
        items: toSidebarSubItems(organizationNavItems),
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
        items: toSidebarSubItems(humanResourcesNavItems),
    },
    {
        title: 'Assets',
        href: '/assets',
        icon: Package,
        requiredPermissions: ['domain.assets.view', 'domain.assets.manage'],
        items: toSidebarSubItems(assetNavItems),
    },
    {
        title: 'Programs',
        href: '/programs',
        icon: BookOpen,
        requiredPermissions: ['domain.programs.view', 'domain.programs.manage'],
        items: toSidebarSubItems(programNavItems),
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: Briefcase,
        requiredPermissions: ['domain.projects.view', 'domain.projects.manage'],
        items: toSidebarSubItems(projectNavItems),
    },
    {
        title: 'Business Development',
        href: '/business-development',
        icon: BriefcaseBusiness,
        requiredPermissions: ['domain.business-development.view', 'domain.business-development.manage'],
        items: toSidebarSubItems(businessDevelopmentNavItems),
    },
    {
        title: 'Events',
        href: '/events',
        icon: CalendarRange,
        requiredPermissions: ['domain.events.view', 'domain.events.manage'],
        items: toSidebarSubItems(eventNavItems),
    },
    {
        title: 'Task Management',
        href: '/task-management',
        icon: LifeBuoy,
        requiredPermissions: ['domain.task-management.view', 'domain.task-management.manage'],
        items: toSidebarSubItems(taskManagementNavItems),
    },
    {
        title: 'Marketing Operations',
        href: '/marketing',
        icon: Megaphone,
        requiredPermissions: ['domain.marketing.view', 'domain.marketing.manage'],
        items: toSidebarSubItems(marketingNavItems),
    },
    {
        title: 'Finance',
        href: '/finance/travel-claims',
        icon: ReceiptText,
        requiredPermissions: ['domain.finance.view', 'domain.finance.manage', 'travel-claims.submit'],
        items: toSidebarSubItems(financeNavItems),
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
        title: 'Delivery Locations',
        href: '/project-locations/dashboard',
        icon: ClipboardCheck,
        requiredPermissions: ['project-activities.view'],
    },
    {
        title: 'Access Control',
        href: '/access-control/roles',
        icon: ShieldCheck,
        requiredRoles: ['super-admin', 'super admin'],
        items: toSidebarSubItems(accessControlNavItems),
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

    const canSeeItem = (item: NavItem) =>
        hasAnyRole(user, item.requiredRoles ?? []) &&
        hasAnyPermission(user, item.requiredPermissions ?? []) &&
        (!restrictBeneficiariesForBds || item.href !== '/beneficiaries');

    const itemsWithBadges = mainNavItems.map((item) => ({
        ...item,
        items: item.items?.filter(canSeeItem),
        badgeCount: item.href === '/notifications' ? (notifications?.unread_count ?? 0) : undefined,
    }));

    const visibleMainNavItems = itemsWithBadges.filter(canSeeItem);

    return (
        <Sidebar collapsible="icon" variant="sidebar" className="border-r border-sidebar-border bg-sidebar text-sidebar-foreground dark:border-white/[0.08] dark:bg-[#080d13]">
            <SidebarHeader className="px-3 py-5">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="h-12 text-sidebar-foreground hover:bg-sidebar-accent dark:text-white dark:hover:bg-white/[0.04] dark:data-[active=true]:bg-white/[0.04]">
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-4 px-1">
                <NavMain items={visibleMainNavItems} />
            </SidebarContent>

            <SidebarFooter className="px-3 pb-4">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
