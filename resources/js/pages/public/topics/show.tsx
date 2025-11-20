import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { type Section, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import type { OutputBlockData } from '@editorjs/editorjs';
import { createElement, useMemo } from 'react';
import { cn } from '@/lib/utils';

interface PublicTopicShowProps {
    topic: Topic & {
        sections: Array<Pick<Section, 'id' | 'title' | 'sort_order'>>;
        activeSection: Section | null;
    };
}

export default function PublicTopicShow({ topic }: PublicTopicShowProps) {
    const activeSection = topic.activeSection;

    const tocItems = useMemo(() => {
        const blocks = activeSection?.content?.blocks ?? [];
        return blocks.filter((block) => block.type === 'header');
    }, [activeSection]);

    const navigateToSection = (sectionId: number) => {
        router.visit(`/themen/${topic.id}/${sectionId}`, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <PublicLayout>
            <Head title={`${topic.title} – Thema`} />
            <div className="mb-10 space-y-3">
                <div className="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.3em] text-white/60">
                    {topic.category && (
                        <Badge variant="outline" className="border-white/30 text-white/80">
                            {topic.category.name}
                        </Badge>
                    )}
                    <span>{formatDate(topic.updated_at)}</span>
                    {topic.author && <span>• {topic.author.name}</span>}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-[240px_1fr_240px]">
                <div className="space-y-2 rounded-3xl border border-white/10 bg-white/5 p-5">
                    <p className="text-xs uppercase tracking-[0.3em] text-white/50">Abschnitte</p>
                    <ul className="space-y-2">
                        {topic.sections.map((section, index) => {
                            const isActive = section.id === activeSection?.id;
                            return (
                                <li key={section.id}>
                                    <button
                                        type="button"
                                        onClick={() => navigateToSection(section.id)}
                                        className={cn(
                                            'flex w-full items-center gap-2 rounded-2xl border border-white/10 px-3 py-2 text-sm transition',
                                            isActive
                                                ? 'bg-[#ff0055]/30 text-white'
                                                : 'text-white/70 hover:bg-white/10',
                                        )}
                                    >
                                        <span className="flex h-6 w-6 items-center justify-center rounded-full border border-white/10 text-xs">
                                            {index + 1}
                                        </span>
                                        <span className="truncate">{section.title}</span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>

                <article className="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-[0_40px_60px_rgba(0,0,0,0.45)]">
                    <h1 className="mb-6 text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                        {activeSection?.title ?? topic.title}
                    </h1>
                    <div className="space-y-6 text-lg leading-relaxed text-white/80">
                        {renderBlocks(activeSection?.content?.blocks ?? [])}
                    </div>
                </article>

                <div className="hidden rounded-3xl border border-white/10 bg-white/5 p-5 lg:block">
                    <p className="mb-4 text-xs uppercase tracking-[0.3em] text-white/50">Inhalt</p>
                    {tocItems.length === 0 ? (
                        <p className="text-sm text-white/50">
                            Dieses Kapitel enthält noch keine Überschriften.
                        </p>
                    ) : (
                        <ul className="space-y-3 text-sm text-white/80">
                            {tocItems.map((block, index) => {
                                const level = block.data?.level ?? 2;
                                return (
                                    <li
                                        key={block.id ?? index}
                                        className={cn(
                                            'border-l border-white/10 pl-3',
                                            level === 3 && 'ml-3 text-white/70',
                                            level >= 4 && 'ml-6 text-white/60',
                                        )}
                                        dangerouslySetInnerHTML={{ __html: block.data?.text ?? '' }}
                                    />
                                );
                            })}
                        </ul>
                    )}
                </div>
            </div>

            <div className="mt-10 flex flex-wrap gap-3">
                <Button
                    variant="outline"
                    className="border-white/40 text-white hover:bg-white/10 hover:text-white"
                    asChild
                >
                    <Link href="/themen">Zur Übersicht</Link>
                </Button>
                <Button className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90" asChild>
                    <Link href="/register">Eigenes Projekt starten</Link>
                </Button>
            </div>
        </PublicLayout>
    );
}

function renderBlocks(blocks: OutputBlockData[]) {
    if (!blocks.length) {
        return <p className="text-white/60">Dieser Abschnitt ist noch leer.</p>;
    }

    return blocks.map((block, index) => {
        const key = block.id ?? `${block.type}-${index}`;

        switch (block.type) {
            case 'header': {
                const level = Math.min(Math.max(block.data.level ?? 2, 2), 4) as 2 | 3 | 4;
                const headingTagMap: Record<typeof level, 'h2' | 'h3' | 'h4'> = {
                    2: 'h2',
                    3: 'h3',
                    4: 'h4',
                };

                return createElement(headingTagMap[level] ?? 'h2', {
                    key,
                    className: 'mt-10 text-white',
                    dangerouslySetInnerHTML: { __html: block.data.text ?? '' },
                });
            }

            case 'list': {
                const Tag = block.data.style === 'ordered' ? 'ol' : 'ul';
                return createElement(
                    Tag,
                    {
                        key,
                        className: 'my-6 list-outside space-y-2 pl-6 text-white/80',
                    },
                    block.data.items?.map((item: string, itemIndex: number) => (
                        <li key={`${key}-${itemIndex}`} dangerouslySetInnerHTML={{ __html: item }} />
                    )),
                );
            }

            case 'paragraph':
            default:
                return (
                    <p
                        key={key}
                        dangerouslySetInnerHTML={{ __html: block.data.text ?? '' }}
                        className="text-white/80"
                    />
                );
        }
    });
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

