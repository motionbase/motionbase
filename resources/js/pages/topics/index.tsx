import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Layers, NotebookPen, Plus, Trash2 } from 'lucide-react';
import { type MouseEvent, useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Themen',
        href: '/topics',
    },
];

export default function TopicsIndex({
    topics,
    categories,
}: {
    topics: Topic[];
    categories: Pick<Category, 'id' | 'name'>[];
}) {
    const topicCountByCategory = useMemo(() => {
        return topics.reduce<Record<number, number>>((acc, topic) => {
            if (topic.category?.id) {
                acc[topic.category.id] = (acc[topic.category.id] ?? 0) + 1;
            }
            return acc;
        }, {});
    }, [topics]);

    const handleDelete = (topicId: number) => {
        if (!confirm('Dieses Thema wirklich löschen?')) {
            return;
        }
        router.delete(`/topics/${topicId}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Themen" />

            <div className="space-y-10 px-6 py-10">
                <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div className="space-y-3">
                        <p className="text-xs font-semibold uppercase tracking-[0.35em] text-[#ff0055]">Inhalte</p>
                        <h1 className="text-4xl font-semibold tracking-tight text-zinc-900">Themen</h1>
                        <p className="text-base text-zinc-500">
                            Verwalte deine Lerninhalte, Abschnitte und Kategorien in einem klaren Workspace.
                        </p>
                    </div>
                    <Button className="bg-[#ff0055] px-6 text-white shadow-[0_10px_25px_-12px_rgba(255,0,85,0.7)] hover:bg-[#ff0055]/90" asChild>
                        <Link href="/topics/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Neues Thema
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="space-y-4">
                        {topics.length === 0 ? (
                            <Card className="border-dashed border-zinc-200 bg-white text-center shadow-none">
                                <CardContent className="py-12">
                                    <NotebookPen className="mx-auto h-12 w-12 text-zinc-300" />
                                    <h3 className="mt-6 text-xl font-semibold text-zinc-900">Keine Themen gefunden</h3>
                                    <p className="mb-6 text-sm text-zinc-500">
                                        Lege dein erstes Thema an, um loszulegen.
                                    </p>
                                    <Button className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90" asChild>
                                        <Link href="/topics/create">Thema erstellen</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid gap-4">
                                {topics.map((topic) => (
                                    <Card key={topic.id} className="border-zinc-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                        <CardContent className="p-6">
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <h3 className="text-lg font-semibold text-zinc-900">{topic.title}</h3>
                                                        {topic.category && (
                                                            <Badge variant="secondary" className="bg-zinc-100 text-zinc-600">
                                                                {topic.category.name}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <p className="text-sm text-zinc-500 line-clamp-2">
                                                        {topic.excerpt ?? 'Keine Vorschau verfügbar.'}
                                                    </p>
                                                    <div className="flex flex-wrap items-center gap-3 text-xs text-zinc-400">
                                                        <span>
                                                            {topic.chapters_count ?? 0}{' '}
                                                            {topic.chapters_count === 1 ? 'Kapitel' : 'Kapitel'}
                                                        </span>
                                                        <span className="hidden sm:inline">•</span>
                                                        <span>
                                                            {topic.sections_count ?? 0}{' '}
                                                            {topic.sections_count === 1 ? 'Abschnitt' : 'Abschnitte'}
                                                        </span>
                                                        <span className="hidden sm:inline">•</span>
                                                        <span>Aktualisiert {formatDate(topic.updated_at)}</span>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2 self-end sm:self-start">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-zinc-700 hover:bg-zinc-50 hover:text-zinc-900"
                                                        asChild
                                                    >
                                                        <Link href={`/topics/${topic.id}/edit`}>Bearbeiten</Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-zinc-400 hover:bg-zinc-50 hover:text-red-500"
                                                        onClick={(event: MouseEvent<HTMLButtonElement>) => {
                                                            event.preventDefault();
                                                            handleDelete(topic.id);
                                                        }}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                        <span className="sr-only">Löschen</span>
                                                    </Button>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </div>

                    <Card className="h-fit border-zinc-100 bg-white shadow-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold text-zinc-900">
                                <Layers className="h-5 w-5 text-[#ff0055]" />
                                Kategorien
                            </CardTitle>
                            <CardDescription>Ein schneller Überblick deiner Bereiche.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {categories.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-500">
                                    Noch keine Kategorien vorhanden.
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {categories.map((category) => (
                                        <div
                                            key={category.id}
                                             className="flex items-center justify-between rounded-xl border border-transparent px-3 py-3 transition hover:border-zinc-200"
                                        >
                                            <span className="text-sm font-medium text-zinc-700">{category.name}</span>
                                            <Badge variant="outline" className="border-zinc-200 text-xs text-zinc-500">
                                                {topicCountByCategory[category.id] ?? 0}
                                            </Badge>
                                        </div>
                                    ))}
                                    <div className="pt-3">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="w-full justify-center text-zinc-500 hover:text-zinc-900"
                                            asChild
                                        >
                                            <Link href="/categories">Alle Kategorien verwalten</Link>
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function formatDate(value?: string) {
    if (!value) {
        return 'unbekannt';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
