import { Button } from '@/components/ui/button';
import { cn, isSameUrl, resolveUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { KeyRound, Palette, Settings, Shield, User } from 'lucide-react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: edit(),
        icon: User,
    },
    {
        title: 'Passwort',
        href: editPassword(),
        icon: KeyRound,
    },
    {
        title: 'Zwei-Faktor-Auth',
        href: show(),
        icon: Shield,
    },
    {
        title: 'Erscheinungsbild',
        href: editAppearance(),
        icon: Palette,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;

    return (
        <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
            {/* Header */}
            <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                <div className="mx-auto max-w-5xl">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-900">
                            <Settings className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-zinc-900">
                                Einstellungen
                            </h1>
                            <p className="text-zinc-500">
                                Verwalte dein Profil und Kontoeinstellungen
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="px-6 py-10 lg:px-10">
                <div className="mx-auto max-w-5xl">
                    <div className="grid gap-8 lg:grid-cols-[240px_1fr]">
                        {/* Sidebar Navigation */}
                        <aside>
                            <nav className="space-y-1">
                                {sidebarNavItems.map((item, index) => {
                                    const isActive = isSameUrl(currentPath, item.href);
                                    return (
                                        <Button
                                            key={`${resolveUrl(item.href)}-${index}`}
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                            className={cn(
                                                'w-full justify-start gap-3 h-10 px-3 font-medium',
                                                isActive
                                                    ? 'bg-zinc-900 text-white hover:bg-zinc-800 hover:text-white'
                                                    : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
                                            )}
                                        >
                                            <Link href={item.href}>
                                                {item.icon && (
                                                    <item.icon className="h-4 w-4" />
                                                )}
                                                {item.title}
                                            </Link>
                                        </Button>
                                    );
                                })}
                            </nav>
                        </aside>

                        {/* Main Content */}
                        <div className="rounded-2xl border border-zinc-100 bg-white p-8 shadow-sm">
                            <div className="max-w-xl space-y-8">
                                {children}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
