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
            <div className="mb-12 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 p-2 backdrop-blur-md">
                <div className="flex flex-wrap gap-2 px-2">
                    <button
                        onClick={() => handleFilterChange('')}
                        className={`rounded-xl px-4 py-2 text-sm font-medium transition-all ${
                            !filters.category 
                                ? 'bg-white text-black shadow-lg' 
                                : 'text-white/60 hover:bg-white/5 hover:text-white'
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
                                    ? 'bg-white text-black shadow-lg'
                                    : 'text-white/60 hover:bg-white/5 hover:text-white'
                            }`}
                        >
                            {category.name}
                        </button>
                    ))}
                </div>
                <div className="hidden sm:flex items-center px-4 text-white/40">
                    <Search className="w-4 h-4" />
                </div>
            </div>

            {topics.length === 0 ? (
                <Card className="border border-white/10 bg-white/5 text-white">
                    <CardContent className="py-24 text-center">
                        <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-white/5">
                            <BookOpen className="h-8 w-8 text-white/20" />
                        </div>
                        <h3 className="text-xl font-semibold mb-2">Keine Themen gefunden</h3>
                        <p className="text-white/60 max-w-sm mx-auto">
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
                            className="group relative flex flex-col overflow-hidden rounded-3xl border border-white/10 bg-white/5 transition-all duration-300 hover:-translate-y-1 hover:border-white/20 hover:bg-white/10"
                        >
                            <div className="flex flex-1 flex-col p-8">
                                <div className="mb-6 flex items-start justify-between gap-4">
                                    {topic.category ? (
                                        <Badge variant="outline" className="border-white/10 bg-white/5 text-xs font-medium text-white/80 hover:bg-white/10">
                                            {topic.category.name}
                                        </Badge>
                                    ) : <div />}
                                    <div className="flex items-center gap-1.5 text-xs font-medium text-white/40">
                                        <Clock className="w-3.5 h-3.5" />
                                        <span>{formatDate(topic.updated_at)}</span>
                                    </div>
                                </div>
                                
                                <h3 className="mb-3 text-xl font-bold leading-tight text-white group-hover:text-[#ff0055] transition-colors">
                                    {topic.title}
                                </h3>
                                
                                {topic.excerpt && (
                                    <p className="mb-6 text-sm leading-relaxed text-white/60 line-clamp-3">
                                        {topic.excerpt}
                                    </p>
                                )}

                                <div className="mt-auto pt-6 border-t border-white/5 flex items-center justify-between text-sm font-medium text-white/80">
                                    <div className="flex items-center gap-2">
                                        {topic.author && (
                                            <span className="text-xs text-white/40">
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
