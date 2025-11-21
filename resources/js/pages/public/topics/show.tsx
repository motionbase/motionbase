import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { type Section, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import type { OutputBlockData } from '@editorjs/editorjs';
import { createElement, useMemo } from 'react';
import { cn } from '@/lib/utils';
import { ArrowLeft, ChevronRight, Menu, Hash, List } from 'lucide-react';

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
            <Head title={`${topic.title} – ${activeSection?.title ?? 'Thema'}`} />
            
            {/* Mobile Header / Breadcrumbs */}
            <div className="mb-8 flex flex-wrap items-center gap-2 text-sm text-white/60">
                <Link href="/themen" className="hover:text-white transition-colors">Themen</Link>
                <ChevronRight className="w-4 h-4 text-white/20" />
                <span className="text-white">{topic.title}</span>
            </div>

            <div className="grid gap-8 lg:grid-cols-[280px_1fr_240px] items-start">
                {/* Left Sidebar: Sections */}
                <div className="space-y-6 lg:sticky lg:top-32">
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-1">
                        <div className="px-4 py-3 border-b border-white/5 mb-1">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-white/40 flex items-center gap-2">
                                <List className="w-3 h-3" /> Kursinhalt
                            </h3>
                        </div>
                        <div className="p-1 space-y-0.5">
                            {topic.sections.map((section, index) => {
                                const isActive = section.id === activeSection?.id;
                                return (
                                    <button
                                        key={section.id}
                                        type="button"
                                        onClick={() => navigateToSection(section.id)}
                                        className={cn(
                                            'group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all',
                                            isActive
                                                ? 'bg-white text-black shadow-lg shadow-white/10'
                                                : 'text-white/60 hover:bg-white/5 hover:text-white'
                                        )}
                                    >
                                        <span className={cn(
                                            "flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold transition-colors",
                                            isActive ? "bg-black/10 text-black" : "bg-white/10 text-white/60 group-hover:bg-white/20 group-hover:text-white"
                                        )}>
                                            {index + 1}
                                        </span>
                                        <span className="truncate text-left">{section.title}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                    
                    {topic.category && (
                        <div className="px-2">
                            <Badge variant="outline" className="border-white/10 bg-white/5 text-white/60 hover:bg-white/10">
                                {topic.category.name}
                            </Badge>
                        </div>
                    )}
                </div>

                {/* Main Content */}
                <article className="min-w-0">
                    <div className="rounded-3xl border border-white/10 bg-white/5 p-8 sm:p-12 shadow-2xl shadow-black/50">
                        <header className="mb-10 border-b border-white/5 pb-8">
                            <div className="flex items-center gap-2 text-xs font-medium text-[#ff0055] mb-4 uppercase tracking-wider">
                                <span className="h-px w-8 bg-[#ff0055]/50"></span>
                                Abschnitt
                            </div>
                            <h1 className="text-3xl font-bold tracking-tight text-white sm:text-5xl leading-tight">
                                {activeSection?.title ?? topic.title}
                            </h1>
                        </header>
                        
                        <div className="prose prose-invert max-w-none prose-headings:font-bold prose-headings:tracking-tight prose-p:text-white/70 prose-p:leading-relaxed prose-li:text-white/70 prose-strong:text-white">
                            {renderBlocks(activeSection?.content?.blocks ?? [])}
                        </div>
                    </div>

                    {/* Navigation Footer */}
                    <div className="mt-8 flex justify-between gap-4">
                        <Button variant="ghost" className="text-white/60 hover:text-white" asChild>
                            <Link href="/themen">
                                <ArrowLeft className="mr-2 h-4 w-4" /> Zurück
                            </Link>
                        </Button>
                        
                        {/* Next Section Logic could go here */}
                    </div>
                </article>

                {/* Right Sidebar: TOC */}
                <div className="hidden lg:block lg:sticky lg:top-32">
                    {tocItems.length > 0 && (
                        <div className="space-y-4 pl-4 border-l border-white/5">
                            <h4 className="text-xs font-bold uppercase tracking-widest text-white/40 flex items-center gap-2">
                                <Hash className="w-3 h-3" /> Auf dieser Seite
                            </h4>
                            <ul className="space-y-2.5 text-sm">
                                {tocItems.map((block, index) => {
                                    const level = block.data?.level ?? 2;
                                    return (
                                        <li key={block.id ?? index}>
                                            <a 
                                                href="#" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    // Smooth scroll logic would be here
                                                }}
                                                className={cn(
                                                    "block transition-colors hover:text-[#ff0055]",
                                                    level === 2 ? "text-white/80 font-medium" : "text-white/50 pl-3 text-xs"
                                                )}
                                                dangerouslySetInnerHTML={{ __html: block.data?.text ?? '' }}
                                            />
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}

function renderBlocks(blocks: OutputBlockData[]) {
    if (!blocks.length) {
        return (
            <div className="flex flex-col items-center justify-center py-12 text-white/40 border border-dashed border-white/10 rounded-xl bg-white/[0.02]">
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
                    className: 'scroll-mt-20 mt-12 first:mt-0 mb-4', // Added scroll-margin for anchored links
                    dangerouslySetInnerHTML: { __html: block.data.text ?? '' },
                });
            }

            case 'list': {
                const Tag = block.data.style === 'ordered' ? 'ol' : 'ul';
                return createElement(
                    Tag,
                    {
                        key,
                        className: 'my-6 space-y-2 pl-6 marker:text-[#ff0055]', // Styled markers
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
