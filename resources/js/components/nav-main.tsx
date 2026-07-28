import { Link } from '@inertiajs/react';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/hooks/use-active-url';
import { type NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { urlIsActive } = useActiveUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="px-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-[#9CA3AF]">
                Platform
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={urlIsActive(item.href)}
                            tooltip={{ children: item.title }}
                            className="h-11 rounded-none px-3 text-[13px] font-medium text-[#374151] transition hover:bg-[#F7F7F7] hover:text-[#111111] data-[active=true]:bg-[#111111] data-[active=true]:text-white"
                        >
                            <Link href={item.href}>
                                {item.icon && (
                                    <item.icon className="text-current transition" />
                                )}
                                <span>{item.title}</span>
                                {item.badgeCount && item.badgeCount > 0 ? (
                                    <span className="ml-auto rounded-full bg-[#FDECEE] px-2 py-0.5 text-[10px] font-semibold text-[#C8102E]">
                                        {item.badgeCount}
                                    </span>
                                ) : null}
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
