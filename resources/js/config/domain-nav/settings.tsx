import { type DomainNavItem } from '@/components/domain-nav';
import {
    CalendarDays,
    Clock3,
    KeyRound,
    Lock,
    Palette,
    UserCircle,
} from 'lucide-react';

export const settingsNavItems: DomainNavItem[] = [
    {
        label: 'Profile',
        href: '/settings/profile',
        icon: <UserCircle className="h-4 w-4" />,
        requiredPermissions: [
            'domain.settings.view',
            'domain.settings.manage',
            'project-activities.view',
            'project-activities.manage',
        ],
    },
    {
        label: 'Password',
        href: '/settings/password',
        icon: <KeyRound className="h-4 w-4" />,
        requiredPermissions: [
            'domain.settings.view',
            'domain.settings.manage',
            'project-activities.view',
            'project-activities.manage',
        ],
    },
    {
        label: 'Two-Factor Auth',
        href: '/settings/two-factor',
        icon: <Lock className="h-4 w-4" />,
        requiredPermissions: [
            'domain.settings.view',
            'domain.settings.manage',
            'project-activities.view',
            'project-activities.manage',
        ],
    },
    {
        label: 'Appearance',
        href: '/settings/appearance',
        icon: <Palette className="h-4 w-4" />,
        requiredPermissions: [
            'domain.settings.view',
            'domain.settings.manage',
            'project-activities.view',
            'project-activities.manage',
        ],
    },
    {
        label: 'Leave',
        href: '/settings/leave',
        icon: <CalendarDays className="h-4 w-4" />,
        requiredPermissions: ['domain.leave.view', 'domain.leave.manage'],
    },
    {
        label: 'Attendance',
        href: '/settings/attendance',
        icon: <Clock3 className="h-4 w-4" />,
    },
];
