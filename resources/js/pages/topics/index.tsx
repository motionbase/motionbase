import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { NotebookPen, Plus, Trash2 } from 'lucide-react';
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
            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border bg-card px-6 py-5">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            Du siehst hier ausschließlich Themen, die du erstellt hast.
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">Deine Themen</h1>
                    </div>
                    <Button asChild>
                        <Link href="/topics/create">
                            <Plus className="mr-2 size-4" />
                            Neues Thema
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="space-y-4">
                        <CardHeader>
                            <CardTitle>Übersicht</CardTitle>
                            <CardDescription>Bearbeite oder lösche Themen über das Kontextmenü.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {topics.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground">
                                    Noch keine Themen vorhanden. Lege dein erstes Thema an – der Editor wartet auf dich!
                                </div>
                            ) : (
                                <ul className="space-y-3">
                                    {topics.map((topic) => (
                                        <li
                                            key={topic.id}
                                            className="rounded-xl border px-4 py-3 transition hover:border-primary/50"
                                        >
                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="space-y-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <NotebookPen className="size-4 text-muted-foreground" />
                                                        <p className="font-medium">{topic.title}</p>
                                                        {topic.category && (
                                                            <Badge variant="outline">{topic.category.name}</Badge>
                                                        )}
                                                    </div>
                                                    <p className="text-sm text-muted-foreground">
                                                        {topic.excerpt ?? 'Dieses Thema enthält derzeit keine Inhalte.'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Aktualisiert {formatDate(topic.updated_at)}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/topics/${topic.id}/edit`}>Bearbeiten</Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={(event: MouseEvent<HTMLButtonElement>) => {
                                                            event.preventDefault();
                                                            handleDelete(topic.id);
                                                        }}
                                                        aria-label="Thema löschen"
                                                    >
                                                        <Trash2 className="size-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Kategorien</CardTitle>
                            <CardDescription>So verteilen sich deine Themen.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {categories.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Noch keine Kategorien. Lege welche unter <Link href="/categories">Kategorien</Link> an.
                                </p>
                            ) : (
                                <ul className="space-y-2">
                                    {categories.map((category) => (
                                        <li
                                            key={category.id}
                                            className="flex items-center justify-between rounded-lg border px-3 py-2"
                                        >
                                            <span>{category.name}</span>
                                            <Badge variant="secondary">{topicCountByCategory[category.id] ?? 0}</Badge>
                                        </li>
                                    ))}
                                </ul>
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

