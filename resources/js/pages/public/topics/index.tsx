import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { type Category, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface PublicTopicsIndexProps {
    topics: Topic[];
    categories: Pick<Category, 'id' | 'name'>[];
    filters: {
        category?: string | number | null;
    };
}

export default function PublicTopicsIndex({ topics, categories, filters }: PublicTopicsIndexProps) {
    const handleFilterChange = (categoryId: string) => {
        router.visit(categoryId ? `/themen?category=${categoryId}` : '/themen', {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <PublicLayout
            headline="Themen entdecken"
            description="Ein modernes, dunkles Showcase deiner Inhalte. Nutze es als Vorlage für dein eigenes Branding."
        >
            <Head title="Themen entdecken" />
            <div className="mb-10 flex flex-wrap items-center gap-3">
                <Button
                    variant={filters.category ? 'ghost' : 'secondary'}
                    className={filters.category ? 'border border-white/20 text-white' : 'bg-[#ff0055] text-white'}
                    onClick={() => handleFilterChange('')}
                >
                    Alle Kategorien
                </Button>
                {categories.map((category) => (
                    <Button
                        key={category.id}
                        variant={String(filters.category) === String(category.id) ? 'secondary' : 'ghost'}
                        className={
                            String(filters.category) === String(category.id)
                                ? 'bg-[#ff0055] text-white'
                                : 'border border-white/20 text-white'
                        }
                        onClick={() => handleFilterChange(String(category.id))}
                    >
                        {category.name}
                    </Button>
                ))}
            </div>
            {topics.length === 0 ? (
                <Card className="border border-white/10 bg-white/5 text-white">
                    <CardContent className="py-12 text-center text-white/60">
                        Noch keine Themen vorhanden. Lege im Dashboard neue Inhalte an und sie erscheinen hier automatisch.
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2">
                    {topics.map((topic) => (
                        <article
                            key={topic.id}
                            className="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_20px_45px_rgba(0,0,0,0.35)] transition hover:-translate-y-1 hover:border-[#ff0055]/60"
                        >
                            <div className="absolute inset-0 bg-gradient-to-br from-white/0 via-white/0 to-white/5 opacity-0 transition group-hover:opacity-100" />
                            <div className="relative flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.3em] text-white/50">
                                {topic.category && (
                                    <Badge variant="outline" className="border-white/30 text-white/80">
                                        {topic.category.name}
                                    </Badge>
                                )}
                                <span>{formatDate(topic.updated_at)}</span>
                            </div>
                            <h2 className="relative mt-6 text-2xl font-semibold tracking-tight text-white">
                                {topic.title}
                            </h2>
                            {topic.excerpt && (
                                <p className="relative mt-3 text-sm leading-relaxed text-white/70">{topic.excerpt}</p>
                            )}

                            <div className="relative mt-6 flex flex-wrap items-center justify-between gap-3 text-sm text-white/70">
                                <div>{topic.author ? `von ${topic.author.name}` : 'Autor unbekannt'}</div>
                                <Button
                                    className="rounded-full bg-[#ff0055] px-5 text-sm font-medium text-white hover:bg-[#ff0055]/90"
                                    asChild
                                >
                                    <Link href={`/themen/${topic.id}`}>Ansehen</Link>
                                </Button>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </PublicLayout>
    );
}

function formatDate(value?: string) {
    if (!value) {
        return 'Aktualisierung unbekannt';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

