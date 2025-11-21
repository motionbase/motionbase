import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { type Category, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, BookOpen, Clock, Search } from 'lucide-react';

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
            headline="Wissen entdecken"
            description="Durchstöbere unsere Sammlung an Kursen und Themen. Finde genau das, was du suchst."
        >
            <Head title="Themen entdecken" />
            
            {/* Filter Bar */}
            <div className="mb-12 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-zinc-100 bg-white p-2 shadow-sm">
                <div className="flex flex-wrap gap-2 px-2">
                    <button
                        onClick={() => handleFilterChange('')}
                        className={`rounded-xl px-4 py-2 text-sm font-medium transition-all ${
                            !filters.category 
                                ? 'bg-zinc-900 text-white shadow-lg shadow-zinc-900/20' 
                                : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900'
                        }`}
                    >
                        Alle
                    </button>
                    {categories.map((category) => (
                        <button
                            key={category.id}
                            onClick={() => handleFilterChange(String(category.id))}
                            className={`rounded-xl px-4 py-2 text-sm font-medium transition-all ${
                                String(filters.category) === String(category.id)
                                    ? 'bg-zinc-900 text-white shadow-lg shadow-zinc-900/20'
                                    : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900'
                            }`}
                        >
                            {category.name}
                        </button>
                    ))}
                </div>
                <div className="hidden sm:flex items-center px-4 text-zinc-300">
                    <Search className="w-4 h-4" />
                </div>
            </div>

            {topics.length === 0 ? (
                <Card className="border border-zinc-100 bg-white text-zinc-900">
                    <CardContent className="py-24 text-center">
                        <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-zinc-50">
                            <BookOpen className="h-8 w-8 text-zinc-300" />
                        </div>
                        <h3 className="text-xl font-semibold mb-2">Keine Themen gefunden</h3>
                        <p className="text-zinc-500 max-w-sm mx-auto">
                            Aktuell sind keine Themen in dieser Kategorie verfügbar. 
                            Schau später nochmal vorbei oder wähle eine andere Kategorie.
                        </p>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {topics.map((topic) => (
                        <Link
                            key={topic.id}
                            href={`/themen/${topic.id}`}
                            className="group relative flex flex-col overflow-hidden rounded-3xl border border-zinc-100 bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-200/50"
                        >
                            <div className="flex flex-1 flex-col p-8">
                                <div className="mb-6 flex items-start justify-between gap-4">
                                    {topic.category ? (
                                        <Badge variant="outline" className="border-zinc-200 bg-zinc-50 text-xs font-medium text-zinc-600 hover:bg-zinc-100">
                                            {topic.category.name}
                                        </Badge>
                                    ) : <div />}
                                    <div className="flex items-center gap-1.5 text-xs font-medium text-zinc-400">
                                        <Clock className="w-3.5 h-3.5" />
                                        <span>{formatDate(topic.updated_at)}</span>
                                    </div>
                                </div>
                                
                                <h3 className="mb-3 text-xl font-bold leading-tight text-zinc-900 group-hover:text-[#ff0055] transition-colors">
                                    {topic.title}
                                </h3>
                                
                                {topic.excerpt && (
                                    <p className="mb-6 text-sm leading-relaxed text-zinc-500 line-clamp-3">
                                        {topic.excerpt}
                                    </p>
                                )}

                                <div className="mt-auto pt-6 border-t border-zinc-100 flex items-center justify-between text-sm font-medium text-zinc-600">
                                    <div className="flex items-center gap-2">
                                        {topic.author && (
                                            <span className="text-xs text-zinc-400">
                                                von {topic.author.name}
                                            </span>
                                        )}
                                    </div>
                                    <span className="flex items-center gap-1 text-[#ff0055] opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                        Ansehen <ArrowRight className="w-4 h-4" />
                                    </span>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </PublicLayout>
    );
}

function formatDate(value?: string) {
    if (!value) return '';
    return new Intl.DateTimeFormat('de-DE', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}
