import { Link } from '@inertiajs/react';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/hooks/use-active-url';
import { toUrl } from '@/lib/utils';
import { type NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { currentUrl, urlIsActive } = useActiveUrl();

    const isActiveItem = (item: NavItem) => {
        const href = toUrl(item.href);

        return urlIsActive(item.href)
            || currentUrl.startsWith(`${href}/`)
            || (item.items ?? []).some((child) => {
                const childHref = toUrl(child.href);

                return urlIsActive(child.href) || currentUrl.startsWith(`${childHref}/`);
            });
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="px-3 text-[10px] font-medium uppercase tracking-wider text-muted-foreground dark:text-white/42">Platform</SidebarGroupLabel>
            <SidebarMenu className="gap-1.5">
                {items.map((item) => {
                    const isActive = isActiveItem(item);
                    const childItems = item.items ?? [];

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive}
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

                            {isActive && childItems.length > 0 ? (
                                <SidebarMenuSub className="mt-1">
                                    {childItems.map((child) => {
                                        const childHref = toUrl(child.href);
                                        const childActive = urlIsActive(child.href) || currentUrl.startsWith(`${childHref}/`);

                                        return (
                                            <SidebarMenuSubItem key={child.title}>
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={childActive}
                                                    className="text-sidebar-foreground/72 data-[active=true]:bg-orange-500/10 data-[active=true]:font-semibold data-[active=true]:text-orange-600 dark:text-white/64 dark:data-[active=true]:text-orange-300"
                                                >
                                                    <Link href={child.href}>
                                                        {child.icon && <child.icon />}
                                                        <span>{child.title}</span>
                                                        {child.badgeCount && child.badgeCount > 0 ? (
                                                            <span className="ml-auto rounded-full bg-orange-500 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                                                {child.badgeCount}
                                                            </span>
                                                        ) : null}
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        );
                                    })}
                                </SidebarMenuSub>
                            ) : null}
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
