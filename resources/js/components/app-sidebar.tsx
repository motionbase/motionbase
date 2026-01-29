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
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Github, Image, LayoutGrid, Layers, NotebookPen, Users } from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    {
        title: 'GitHub',
        href: 'https://github.com/motionbase/motionbase',
        icon: Github,
    },
    {
        title: 'Dokumentation',
        href: 'https://github.com/motionbase/motionbase#readme',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: { user: { is_admin?: boolean } } }>().props;
    const isAdmin = auth.user?.is_admin ?? false;

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
            href: '/admin/topics',
            icon: NotebookPen,
        },
        {
            title: 'Kategorien',
            href: '/admin/categories',
            icon: Layers,
        },
        {
            title: 'Medien',
            href: '/admin/media',
            icon: Image,
        },
    ];

    const adminNavItems: NavItem[] = [
        {
            title: 'Benutzer',
            href: '/admin/users',
            icon: Users,
        },
    ];

    const navGroups: NavGroup[] = [
        { label: 'Übersicht', items: mainNavItems },
        { label: 'Inhalte', items: contentNavItems },
        ...(isAdmin ? [{ label: 'Administration', items: adminNavItems }] : []),
    ];

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="border-r border-zinc-100 bg-white"
        >
            <SidebarHeader className="border-b border-zinc-100 px-4 py-4">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="text-lg font-semibold tracking-tight text-zinc-900 hover:bg-transparent"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2 py-4">
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter className="gap-4 border-t border-zinc-100 px-4 py-4">
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
