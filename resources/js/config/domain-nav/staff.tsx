import {
    Briefcase,
    Building2,
    CalendarDays,
    Clock3,
} from 'lucide-react';

import { type DomainNavItem } from '@/components/domain-nav';

export const staffNavItems: DomainNavItem[] = [
    {
        label: 'HR Dashboard',
        href: '/human-resources',
        icon: <Briefcase className="h-4 w-4" />,
        requiredPermissions: [
            'domain.human-resources.view',
            'domain.human-resources.manage',
        ],
    },
    {
        label: 'Staff List',
        href: '/staff',
        requiredPermissions: ['domain.staff.view', 'domain.staff.manage'],
    },
    {
        label: 'Departments',
        href: '/staff-departments',
        icon: <Building2 className="h-4 w-4" />,
        requiredPermissions: ['domain.staff.view', 'domain.staff.manage'],
    },
    {
        label: 'Leave Requests',
        href: '/leave-requests',
        icon: <CalendarDays className="h-4 w-4" />,
        requiredPermissions: [
            'domain.staff.view',
            'domain.staff.manage',
            'domain.leave.view',
            'domain.leave.manage',
        ],
    },
    {
        label: 'Attendance',
        href: '/human-resources/attendance',
        icon: <Clock3 className="h-4 w-4" />,
        requiredPermissions: [
            'domain.human-resources.view',
            'domain.human-resources.manage',
            'domain.staff.view',
            'domain.staff.manage',
        ],
    },
];
