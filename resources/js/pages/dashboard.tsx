import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, Layers, Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const recentTopics = [
    { title: 'Animation Basics', time: 'vor 2 Stunden', id: 1 },
    { title: 'Product Design Sprint', time: 'vor 8 Stunden', id: 2 },
    { title: 'Marketing Analytics', time: 'gestern', id: 3 },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="space-y-10 px-6 py-10">
                <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div className="space-y-3">
                        <p className="text-xs font-semibold uppercase tracking-[0.35em] text-[#ff0055]">Übersicht</p>
                        <h1 className="text-4xl font-semibold tracking-tight text-zinc-900">Willkommen zurück</h1>
                        <p className="text-base text-zinc-500">
                            Verwalte deine Themen, Kategorien und Abschnitte in einem klaren Workspace.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" className="border-zinc-200 text-zinc-700 hover:bg-zinc-50" asChild>
                            <Link href="/topics">Alle Themen</Link>
                        </Button>
                        <Button className="bg-[#ff0055] px-6 text-white shadow-[0_10px_25px_-12px_rgba(255,0,85,0.7)] hover:bg-[#ff0055]/90" asChild>
                            <Link href="/topics/create">
                                <Plus className="mr-2 h-4 w-4" /> Neues Thema
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="border-zinc-100 bg-white shadow-sm">
                        <CardHeader className="flex flex-row items-start justify-between space-y-0">
                            <CardTitle className="text-sm font-medium text-zinc-500">Aktive Themen</CardTitle>
                            <BookOpen className="h-4 w-4 text-[#ff0055]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold text-zinc-900">12</div>
                            <p className="text-xs text-zinc-500">+2 seit letzter Woche</p>
                        </CardContent>
                    </Card>

                    <Card className="border-zinc-100 bg-white shadow-sm">
                        <CardHeader className="flex flex-row items-start justify-between space-y-0">
                            <CardTitle className="text-sm font-medium text-zinc-500">Kategorien</CardTitle>
                            <Layers className="h-4 w-4 text-[#ff0055]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold text-zinc-900">4</div>
                            <p className="text-xs text-zinc-500">Alle Bereiche abgedeckt</p>
                        </CardContent>
                    </Card>

                    <Card className="border-zinc-100 bg-white shadow-sm">
                        <CardHeader className="flex flex-row items-start justify-between space-y-0">
                            <CardTitle className="text-sm font-medium text-zinc-500">Lernende</CardTitle>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                className="h-4 w-4 text-[#ff0055]"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold text-zinc-900">573</div>
                            <p className="text-xs text-zinc-500">+201 diesen Monat</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="border-zinc-100 bg-white shadow-sm lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-zinc-900">Zuletzt bearbeitet</CardTitle>
                            <CardDescription>Starte direkt dort, wo du aufgehört hast.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentTopics.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex flex-col gap-3 rounded-2xl border border-zinc-100 px-4 py-4 transition hover:border-zinc-200 hover:bg-zinc-50/50 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="text-base font-medium text-zinc-900">{item.title}</p>
                                        <p className="text-sm text-zinc-500">{item.time}</p>
                                    </div>
                                    <Button variant="ghost" size="sm" className="justify-start text-zinc-700 hover:text-[#ff0055]" asChild>
                                        <Link href={`/topics/${item.id}/edit`}>
                                            Weiter <ArrowRight className="ml-2 h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="border-zinc-100 bg-white shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-zinc-900">Schnellzugriff</CardTitle>
                            <CardDescription>Wichtige Bereiche deiner Plattform.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <Button
                                variant="outline"
                                className="justify-start border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50"
                                asChild
                            >
                                <Link href="/topics">
                                    <BookOpen className="mr-2 h-4 w-4 text-[#ff0055]" />
                                    Themen verwalten
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                className="justify-start border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50"
                                asChild
                            >
                                <Link href="/categories">
                                    <Layers className="mr-2 h-4 w-4 text-[#ff0055]" />
                                    Kategorien bearbeiten
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
