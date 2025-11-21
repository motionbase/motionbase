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
            <div className="space-y-8 p-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-white">Themen</h1>
                        <p className="text-muted-foreground">
                            Verwalte deine Lerninhalte und erstelle neue Kurse.
                        </p>
                    </div>
                    <Button className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90" asChild>
                        <Link href="/topics/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Neues Thema
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-8 lg:grid-cols-[1fr_300px]">
                    {/* Topics List */}
                    <div className="space-y-4">
                        {topics.length === 0 ? (
                            <Card className="border-dashed border-white/10 bg-transparent text-center">
                                <CardContent className="py-12">
                                    <NotebookPen className="mx-auto h-12 w-12 text-white/20" />
                                    <h3 className="mt-4 text-lg font-semibold text-white">Keine Themen gefunden</h3>
                                    <p className="mb-6 text-sm text-muted-foreground">
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
                                    <Card
                                        key={topic.id}
                                        className="border-white/10 bg-white/5 text-white transition-colors hover:border-white/20"
                                    >
                                        <CardContent className="p-6">
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="space-y-2">
                                                    <div className="flex items-center gap-2">
                                                        <h3 className="font-semibold text-lg">{topic.title}</h3>
                                                        {topic.category && (
                                                            <Badge variant="outline" className="border-white/20 text-white/60">
                                                                {topic.category.name}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <p className="text-sm text-white/60 line-clamp-2">
                                                        {topic.excerpt ?? 'Keine Vorschau verfügbar.'}
                                                    </p>
                                                    <div className="flex items-center gap-4 text-xs text-white/40">
                                                        <span>
                                                            {topic.sections_count ?? 0} {topic.sections_count === 1 ? 'Abschnitt' : 'Abschnitte'}
                                                        </span>
                                                        <span>•</span>
                                                        <span>Aktualisiert {formatDate(topic.updated_at)}</span>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2 self-end sm:self-start">
                                                    <Button variant="ghost" size="sm" className="text-white/60 hover:bg-white/10 hover:text-white" asChild>
                                                        <Link href={`/topics/${topic.id}/edit`}>Bearbeiten</Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-white/40 hover:bg-white/10 hover:text-red-400"
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

                    {/* Sidebar: Categories */}
                    <div className="space-y-6">
                        <Card className="border-white/10 bg-white/5 text-white">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Layers className="h-5 w-5 text-[#ff0055]" />
                                    Kategorien
                                </CardTitle>
                                <CardDescription className="text-white/50">
                                    Überblick deiner Inhalte.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {categories.length === 0 ? (
                                    <div className="text-center text-sm text-white/40">
                                        <p className="mb-4">Keine Kategorien vorhanden.</p>
                                        <Button variant="link" className="text-[#ff0055]" asChild>
                                            <Link href="/categories">Verwalten</Link>
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="space-y-1">
                                        {categories.map((category) => (
                                            <div
                                                key={category.id}
                                                className="flex items-center justify-between rounded-md px-2 py-2 text-sm text-white/70 hover:bg-white/5"
                                            >
                                                <span>{category.name}</span>
                                                <Badge variant="secondary" className="bg-white/10 text-white hover:bg-white/20">
                                                    {topicCountByCategory[category.id] ?? 0}
                                                </Badge>
                                            </div>
                                        ))}
                                        <div className="pt-4">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="w-full text-white/40 hover:text-white"
                                                asChild
                                            >
                                                <Link href="/categories">Alle verwalten</Link>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
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
