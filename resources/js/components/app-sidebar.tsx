import { NavFooter } from '@/components/nav-footer';
import { NavMain, type NavGroup } from '@/components/nav-main';
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
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, NotebookPen, Tag } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const contentNavItems: NavItem[] = [
    {
        title: 'Themen',
        href: '/topics',
        icon: NotebookPen,
    },
    {
        title: 'Kategorien',
        href: '/categories',
        icon: Tag,
    },
];

const navGroups: NavGroup[] = [
    { label: 'Arbeitsbereich', items: mainNavItems },
    { label: 'Inhalte', items: contentNavItems },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="border-r border-zinc-100 bg-white/95 text-zinc-900 shadow-sm supports-[backdrop-filter]:bg-white/85"
        >
            <SidebarHeader className="border-b border-zinc-100 px-4 py-4">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="text-lg font-semibold tracking-tight text-zinc-900">
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2 py-6">
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter className="gap-6 border-t border-zinc-100 px-4 py-5">
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
