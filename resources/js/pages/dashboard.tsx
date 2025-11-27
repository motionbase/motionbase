import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type Topic } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowUpRight,
    BookOpen,
    FolderOpen,
    Layers,
    Plus,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardProps {
    stats?: {
        topics: number;
        categories: number;
        sections: number;
        chapters: number;
    };
    recentTopics?: Topic[];
}

export default function Dashboard({ stats, recentTopics = [] }: DashboardProps) {
    const displayStats = stats ?? { topics: 0, categories: 0, sections: 0, chapters: 0 };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Hero Section */}
                <div className="border-b border-zinc-100 bg-white px-6 py-12 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        <div className="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-4">
                                <div className="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-3 py-1 text-xs font-medium text-white">
                                    <Sparkles className="h-3 w-3" />
                                    Workspace
                                </div>
                                <h1 className="text-4xl font-bold tracking-tight text-zinc-900 lg:text-5xl">
                                    Willkommen zurück
                                </h1>
                                <p className="max-w-lg text-lg text-zinc-500">
                                    Verwalte deine Lerninhalte, erstelle neue Kurse und behalte den Überblick über deine Plattform.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    variant="outline"
                                    className="h-11 gap-2 border-zinc-200 px-5 text-zinc-700 hover:bg-zinc-50"
                                    asChild
                                >
                                    <Link href="/topics">
                                        <BookOpen className="h-4 w-4" />
                                        Alle Themen
                                    </Link>
                                </Button>
                                <Button
                                    className="h-11 gap-2 bg-zinc-900 px-5 text-white hover:bg-zinc-800"
                                    asChild
                                >
                                    <Link href="/topics/create">
                                        <Plus className="h-4 w-4" />
                                        Neues Thema
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="px-6 py-10 lg:px-10">
                    <div className="mx-auto max-w-6xl space-y-10">
                        {/* Stats Grid */}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <StatCard
                                label="Themen"
                                value={displayStats.topics}
                                icon={BookOpen}
                                trend="+2 diese Woche"
                                href="/topics"
                            />
                            <StatCard
                                label="Kapitel"
                                value={displayStats.chapters}
                                icon={FolderOpen}
                                trend="Strukturiert"
                            />
                            <StatCard
                                label="Abschnitte"
                                value={displayStats.sections}
                                icon={Layers}
                                trend="Inhalte"
                            />
                            <StatCard
                                label="Kategorien"
                                value={displayStats.categories}
                                icon={TrendingUp}
                                href="/categories"
                            />
                        </div>

                        {/* Main Content Grid */}
                        <div className="grid gap-6 lg:grid-cols-3">
                            {/* Recent Topics */}
                            <div className="lg:col-span-2">
                                <div className="rounded-2xl border border-zinc-100 bg-white shadow-sm">
                                    <div className="flex items-center justify-between border-b border-zinc-100 px-6 py-4">
                                        <div>
                                            <h2 className="text-lg font-semibold text-zinc-900">
                                                Zuletzt bearbeitet
                                            </h2>
                                            <p className="text-sm text-zinc-500">
                                                Setze dort fort, wo du aufgehört hast
                                            </p>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="gap-1 text-zinc-500 hover:text-zinc-900"
                                            asChild
                                        >
                                            <Link href="/topics">
                                                Alle anzeigen
                                                <ArrowRight className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                    <div className="divide-y divide-zinc-50">
                                        {recentTopics.length === 0 ? (
                                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                                <div className="rounded-full bg-zinc-100 p-4">
                                                    <BookOpen className="h-8 w-8 text-zinc-400" />
                                                </div>
                                                <h3 className="mt-4 text-base font-medium text-zinc-900">
                                                    Noch keine Themen
                                                </h3>
                                                <p className="mt-1 text-sm text-zinc-500">
                                                    Erstelle dein erstes Thema, um loszulegen.
                                                </p>
                                                <Button
                                                    className="mt-6 bg-zinc-900 text-white hover:bg-zinc-800"
                                                    asChild
                                                >
                                                    <Link href="/topics/create">
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        Thema erstellen
                                                    </Link>
                                                </Button>
                                            </div>
                                        ) : (
                                            recentTopics.slice(0, 5).map((topic) => (
                                                <Link
                                                    key={topic.id}
                                                    href={`/topics/${topic.id}/edit`}
                                                    className="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-50"
                                                >
                                                    <div className="flex items-center gap-4">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100">
                                                            <BookOpen className="h-5 w-5 text-zinc-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-zinc-900">
                                                                {topic.title}
                                                            </p>
                                                            <p className="text-sm text-zinc-500">
                                                                {topic.chapters_count ?? 0} Kapitel · {topic.sections_count ?? 0} Abschnitte
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <ArrowUpRight className="h-5 w-5 text-zinc-400" />
                                                </Link>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="space-y-6">
                                <div className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                                    <h2 className="text-lg font-semibold text-zinc-900">
                                        Schnellzugriff
                                    </h2>
                                    <p className="mt-1 text-sm text-zinc-500">
                                        Wichtige Bereiche deiner Plattform
                                    </p>
                                    <div className="mt-6 space-y-2">
                                        <QuickAction
                                            icon={Plus}
                                            label="Neues Thema erstellen"
                                            href="/topics/create"
                                        />
                                        <QuickAction
                                            icon={BookOpen}
                                            label="Themen verwalten"
                                            href="/topics"
                                        />
                                        <QuickAction
                                            icon={Layers}
                                            label="Kategorien bearbeiten"
                                            href="/categories"
                                        />
                                    </div>
                                </div>

                                {/* Help Card */}
                                <div className="rounded-2xl border border-zinc-100 bg-gradient-to-br from-zinc-900 to-zinc-800 p-6 text-white shadow-sm">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                                        <Users className="h-5 w-5" />
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold">
                                        Hilfe & Support
                                    </h3>
                                    <p className="mt-2 text-sm text-zinc-300">
                                        Brauchst du Unterstützung? Schau in unsere Dokumentation oder kontaktiere uns.
                                    </p>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="mt-4 border-white/20 bg-transparent text-white hover:bg-white/10"
                                        asChild
                                    >
                                        <a
                                            href="https://github.com/motionbase/motionbase"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Dokumentation
                                            <ArrowUpRight className="ml-2 h-4 w-4" />
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({
    label,
    value,
    icon: Icon,
    trend,
    href,
}: {
    label: string;
    value: number;
    icon: React.ComponentType<{ className?: string }>;
    trend?: string;
    href?: string;
}) {
    const content = (
        <div className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm transition-all hover:border-zinc-200 hover:shadow-md">
            <div className="flex items-start justify-between">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100">
                    <Icon className="h-5 w-5 text-zinc-600" />
                </div>
                {href && <ArrowUpRight className="h-5 w-5 text-zinc-300" />}
            </div>
            <div className="mt-4">
                <p className="text-3xl font-bold text-zinc-900">{value}</p>
                <p className="text-sm font-medium text-zinc-500">{label}</p>
            </div>
            {trend && (
                <p className="mt-2 text-xs text-zinc-400">{trend}</p>
            )}
        </div>
    );

    if (href) {
        return <Link href={href}>{content}</Link>;
    }

    return content;
}

function QuickAction({
    icon: Icon,
    label,
    href,
}: {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50"
        >
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100">
                <Icon className="h-4 w-4 text-zinc-600" />
            </div>
            {label}
        </Link>
    );
}
