import { type ReactNode } from 'react';

import FlashMessages from '@/components/flash-messages';
import { StaffAttendancePrompt } from '@/components/staff-attendance-prompt';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        {/* universal flash area */}
        <FlashMessages />
        <StaffAttendancePrompt />
        {children}
    </AppLayoutTemplate>
);
