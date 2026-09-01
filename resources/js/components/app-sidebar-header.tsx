import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { BookOpen, Briefcase, Building2, LayoutGrid, UserCircle } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';

const headerIcons: Record<string, ComponentType<{ className?: string }>> = {
    Dashboard: LayoutGrid,
    Beneficiaries: UserCircle,
    Stakeholders: UserCircle,
    Organization: Building2,
    Programs: BookOpen,
    Projects: Briefcase,
};

export function AppSidebarHeader({
    breadcrumbs = [],
    actions,
}: {
    breadcrumbs?: BreadcrumbItemType[];
    actions?: ReactNode;
}) {
    const current = breadcrumbs[breadcrumbs.length - 1] ?? breadcrumbs[0];
    const HeaderIcon = current ? (headerIcons[current.title] ?? LayoutGrid) : LayoutGrid;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6">
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger className="-ml-1" />
                <div className="flex min-w-0 items-center gap-3">
                    <span className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-slate-900">
                        <HeaderIcon className="h-4 w-4" />
                    </span>
                    <div className="min-w-0 text-sm font-medium text-slate-900">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            </div>
            {actions ? <div className="flex shrink-0 items-center gap-2">{actions}</div> : null}
        </header>
    );
}
