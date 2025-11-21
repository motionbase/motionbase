import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { type Section, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import type { OutputBlockData } from '@editorjs/editorjs';
import { createElement, useMemo } from 'react';
import { cn } from '@/lib/utils';
import { ArrowLeft, Hash, List } from 'lucide-react';

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
        <PublicLayout stickyFooter>
            <Head title={`${topic.title} – ${activeSection?.title ?? 'Thema'}`} />

            <div className="relative -mx-6 lg:-mx-12 border-x border-zinc-100 bg-white lg:min-h-[calc(100vh-140px)] lg:overflow-hidden">
                <div
                    className={cn(
                        'grid',
                        'lg:grid-cols-[320px_minmax(0,1fr)_320px]'
                    )}
                >
                    <aside className="border-b border-zinc-100 px-4 py-6 lg:border-b-0 lg:border-r lg:px-6 lg:py-10 flex flex-col gap-6 lg:sticky lg:top-20 lg:max-h-[calc(100vh-140px)] lg:overflow-y-auto lg:scrollbar-thin lg:scrollbar-thumb-zinc-200 lg:bg-white">
                        <div className="space-y-1.5 border-b border-zinc-100 pb-6 -mx-4 lg:-mx-6 px-4 lg:px-6">
                            <p className="text-base font-semibold text-zinc-900 leading-snug mb-0">
                                {topic.title}
                            </p>
                            <p className="text-sm text-zinc-400 leading-tight">
                                {topic.author?.name ? `von ${topic.author.name}` : 'Autor unbekannt'}
                            </p>
                        </div>
                        <div className="flex items-center justify-between">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-zinc-400 flex items-center gap-2">
                                <List className="w-3 h-3" /> Kursinhalt
                            </h3>
                        </div>
                        <div className="space-y-1">
                            {topic.sections.map((section, index) => {
                                const isActive = section.id === activeSection?.id;
                                return (
                                    <button
                                        key={section.id}
                                        type="button"
                                        onClick={() => navigateToSection(section.id)}
                                        className={cn(
                                            'group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-all',
                                            isActive
                                                ? 'bg-zinc-900 text-white'
                                                : 'text-zinc-500 hover:bg-white hover:text-zinc-900'
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold transition-colors',
                                                isActive
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-white text-zinc-400 group-hover:text-zinc-700'
                                            )}
                                        >
                                            {index + 1}
                                        </span>
                                        <span className="truncate text-left">{section.title}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </aside>

                    <section className="px-4 py-8 sm:px-8 lg:px-12 lg:py-12 lg:max-h-[calc(100vh-140px)] lg:overflow-y-auto lg:scrollbar-thin lg:scrollbar-thumb-zinc-200">
                        <header className="pb-6 space-y-3">
                            <div className="flex items-center gap-2 text-xs font-medium text-[#ff0055] uppercase tracking-wider">
                                <span className="h-px w-8 bg-[#ff0055]/50"></span>
                                {topic.title}
                            </div>
                            <h1 className="text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl leading-tight">
                                {activeSection?.title ?? topic.title}
                            </h1>
                        </header>

                        <div className="prose max-w-none prose-headings:font-bold prose-headings:tracking-tight prose-headings:text-zinc-900 prose-p:text-zinc-600 prose-p:leading-relaxed prose-li:text-zinc-600 prose-strong:text-zinc-900">
                            {renderBlocks(activeSection?.content?.blocks ?? [])}
                        </div>

                    </section>

                    <aside className="hidden lg:flex border-t border-zinc-100 px-6 py-10 flex-col gap-4 lg:border-t-0 lg:border-l lg:sticky lg:top-20 lg:max-h-[calc(100vh-140px)] lg:overflow-y-auto lg:scrollbar-thin lg:scrollbar-thumb-zinc-200 lg:bg-white">
                        <div className="flex items-center justify-between">
                            <h4 className="text-xs font-bold uppercase tracking-widest text-zinc-400 flex items-center gap-2">
                                <Hash className="w-3 h-3" /> Auf dieser Seite
                            </h4>
                        </div>
                        <ul className="space-y-2.5 text-sm">
                            {tocItems.length > 0 ? (
                                tocItems.map((block, index) => {
                                    const level = block.data?.level ?? 2;
                                    return (
                                        <li key={block.id ?? index}>
                                            <a
                                                href="#"
                                                onClick={(e) => e.preventDefault()}
                                                className={cn(
                                                    'block transition-colors hover:text-[#ff0055]',
                                                    level === 2 ? 'text-zinc-800 font-medium' : 'text-zinc-500 pl-3 text-xs'
                                                )}
                                                dangerouslySetInnerHTML={{ __html: block.data?.text ?? '' }}
                                            />
                                        </li>
                                    );
                                })
                            ) : (
                                <li className="text-sm text-zinc-400">Keine Überschriften vorhanden.</li>
                            )}
                        </ul>
                    </aside>
                </div>
            </div>
        </PublicLayout>
    );
}

function renderBlocks(blocks: OutputBlockData[]) {
    if (!blocks.length) {
        return (
            <div className="flex flex-col items-center justify-center py-12 text-zinc-400 border border-dashed border-zinc-200 rounded-xl bg-zinc-50">
                <p>Dieser Abschnitt ist noch leer.</p>
            </div>
        );
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
                    className: 'scroll-mt-20 mt-12 first:mt-0 mb-4',
                    dangerouslySetInnerHTML: { __html: block.data.text ?? '' },
                });
            }

            case 'list': {
                const Tag = block.data.style === 'ordered' ? 'ol' : 'ul';
                return createElement(
                    Tag,
                    {
                        key,
                        className: 'my-6 space-y-2 pl-6 marker:text-[#ff0055]',
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
                        className="mb-6 last:mb-0"
                    />
                );
        }
    });
}

