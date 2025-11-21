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

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="space-y-8 p-6">
                {/* Welcome & Quick Actions */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-white">Willkommen zurück</h1>
                        <p className="text-muted-foreground">
                            Hier hast du den Überblick über deine Kurse und Inhalte.
                        </p>
                    </div>
                    <Button className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90" asChild>
                        <Link href="/topics/create">
                            <Plus className="mr-2 h-4 w-4" /> Neues Thema
                        </Link>
                    </Button>
                </div>

                {/* Stats Overview */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="border-white/10 bg-white/5 text-white">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktive Themen</CardTitle>
                            <BookOpen className="h-4 w-4 text-[#ff0055]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">12</div>
                            <p className="text-xs text-white/50">+2 seit letzter Woche</p>
                        </CardContent>
                    </Card>
                    <Card className="border-white/10 bg-white/5 text-white">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Kategorien</CardTitle>
                            <Layers className="h-4 w-4 text-[#ff0055]" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">4</div>
                            <p className="text-xs text-white/50">Alle Bereiche abgedeckt</p>
                        </CardContent>
                    </Card>
                    <Card className="border-white/10 bg-white/5 text-white">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Lernende</CardTitle>
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
                            <div className="text-2xl font-bold">573</div>
                            <p className="text-xs text-white/50">+201 diesen Monat</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Activity / Quick Access */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                    <Card className="col-span-4 border-white/10 bg-white/5 text-white">
                        <CardHeader>
                            <CardTitle>Zuletzt bearbeitet</CardTitle>
                            <CardDescription className="text-white/50">
                                Arbeite direkt an deinen Inhalten weiter.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {/* Placeholder items - would typically map through data */}
                                {[1, 2, 3].map((i) => (
                                    <div
                                        key={i}
                                        className="flex items-center justify-between rounded-lg border border-white/5 p-4 hover:bg-white/5"
                                    >
                                        <div className="space-y-1">
                                            <p className="text-sm font-medium leading-none text-white">
                                                Einführung in React
                                            </p>
                                            <p className="text-xs text-white/50">
                                                Zuletzt bearbeitet vor {i} Stunden
                                            </p>
                                        </div>
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/topics/${i}/edit`}>
                                                Bearbeiten <ArrowRight className="ml-2 h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="col-span-3 border-white/10 bg-white/5 text-white">
                        <CardHeader>
                            <CardTitle>Schnellzugriff</CardTitle>
                            <CardDescription className="text-white/50">
                                Wichtige Bereiche verwalten.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <Button
                                variant="outline"
                                className="justify-start border-white/10 bg-transparent text-white hover:bg-white/5 hover:text-white"
                                asChild
                            >
                                <Link href="/topics">
                                    <BookOpen className="mr-2 h-4 w-4" />
                                    Themen verwalten
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                className="justify-start border-white/10 bg-transparent text-white hover:bg-white/5 hover:text-white"
                                asChild
                            >
                                <Link href="/categories">
                                    <Layers className="mr-2 h-4 w-4" />
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
