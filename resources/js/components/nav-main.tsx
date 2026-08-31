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
            <SidebarGroupLabel className="px-3 text-[10px] font-medium uppercase tracking-wider text-muted-foreground dark:text-white/42">Platform</SidebarGroupLabel>
            <SidebarMenu className="gap-1.5">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={urlIsActive(item.href)}
                            className="h-10 rounded-xl px-3 text-sidebar-foreground/85 transition hover:bg-orange-500/10 hover:text-sidebar-foreground data-[active=true]:bg-gradient-to-r data-[active=true]:from-red-600 data-[active=true]:to-orange-600 data-[active=true]:font-semibold data-[active=true]:text-white data-[active=true]:shadow-[0_12px_30px_rgba(255,92,0,0.24)] dark:text-white/84 dark:hover:text-white"
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href}>
                                {item.icon && <item.icon className="text-orange-500" />}
                                <span>{item.title}</span>
                                {item.badgeCount && item.badgeCount > 0 ? (
                                    <span className="ml-auto rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-semibold text-white">
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
