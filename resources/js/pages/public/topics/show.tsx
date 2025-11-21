import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { type Section, type Topic } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import type { OutputBlockData } from '@editorjs/editorjs';
import { createElement, type ReactNode, useEffect, useMemo, useRef } from 'react';
import { cn } from '@/lib/utils';
import { Hash, List } from 'lucide-react';
import Prism from 'prismjs';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-typescript';
import 'prismjs/components/prism-css';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-markup-templating';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-bash';
import 'prismjs/components/prism-json';

interface PublicTopicShowProps {
    topic: Topic & {
        sections: Array<Pick<Section, 'id' | 'title' | 'sort_order'>>;
        activeSection: Section | null;
    };
}

type EditorListItem = string | { content?: string; items?: EditorListItem[] | null };
type ListStyle = 'ordered' | 'unordered';

export default function PublicTopicShow({ topic }: PublicTopicShowProps) {
    const activeSection = topic.activeSection;
    const contentRef = useRef<HTMLElement | null>(null);
    useEffect(() => {
        if (contentRef.current) {
            Prism.highlightAllUnder(contentRef.current);
        }
    }, [activeSection?.id]);
    const scrollToHeading = (headingId: string) => {
        const heading = document.getElementById(headingId);
        if (!heading) {
            return;
        }

        const container = contentRef.current;
        if (container && container.contains(heading)) {
            const offset = Math.max(heading.offsetTop - 24, 0);
            container.scrollTo({
                top: offset,
                behavior: 'smooth',
            });
            return;
        }

        const headerOffset = 90;
        const elementPosition = heading.getBoundingClientRect().top + window.scrollY;
        window.scrollTo({
            top: elementPosition - headerOffset,
            behavior: 'smooth',
        });
    };


    const tocItems = useMemo(() => {
        const blocks = activeSection?.content?.blocks ?? [];
        return blocks
            .map((block, index) => ({
                block,
                index,
            }))
            .filter(({ block }) => block.type === 'header')
            .map(({ block, index }) => ({
                id: getHeadingId(block, index),
                block,
            }));
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

                    <section
                        ref={contentRef}
                        className="px-4 py-8 sm:px-8 lg:px-12 lg:py-12 lg:max-h-[calc(100vh-140px)] lg:overflow-y-auto lg:scrollbar-thin lg:scrollbar-thumb-zinc-200"
                    >
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
                                        tocItems.map(({ block, id }) => {
                                            const level = block.data?.level ?? 2;
                                    return (
                                                <li key={id}>
                                            <a
                                                        href={`#${id}`}
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            scrollToHeading(id);
                                                        }}
                                                className={cn(
                                                    'block transition-colors hover:text-[#ff0055]',
                                                    level === 2
                                                        ? 'text-zinc-800 font-medium'
                                                        : level === 3
                                                            ? 'text-zinc-600 pl-3 text-xs'
                                                            : 'text-zinc-500 pl-6 text-xs'
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

type RenderTheme = {
    textClass?: string;
    headingClass?: Partial<Record<2 | 3 | 4, string>>;
};

function renderBlocks(blocks: OutputBlockData[], theme?: RenderTheme) {
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
                const headingClassMap: Record<typeof level, string> = {
                    2: 'text-xl sm:text-3xl font-semibold tracking-tight text-zinc-900 leading-tight',
                    3: 'text-lg sm:text-xl font-semibold text-zinc-900 leading-tight',
                    4: 'text-base sm:text-lg font-semibold text-zinc-900',
                };
                const headingId = getHeadingId(block, index);

                return createElement(headingTagMap[level] ?? 'h2', {
                    key,
                    id: headingId,
                    className: cn(
                        'scroll-mt-20 mt-12 first:mt-0 mb-4',
                        headingClassMap[level] ?? '',
                        theme?.headingClass?.[level] ?? '',
                    ),
                    dangerouslySetInnerHTML: { __html: block.data.text ?? '' },
                });
            }

            case 'list': {
                const style = block.data.style === 'ordered' ? 'ordered' : 'unordered';
                return (
                    <ul
                        key={key}
                        className="my-6 list-none space-y-2"
                    >
                        {renderListItems(block.data.items as EditorListItem[] | undefined, key, style, theme)}
                    </ul>
                );
            }

            case 'alert': {
                const type = (block.data?.type ?? 'info') as 'info' | 'warning' | 'danger' | 'neutral';
                const innerBlocks = (block.data?.contentBlocks?.blocks ?? []) as OutputBlockData[];
                const hasStructuredContent = innerBlocks.length > 0;
                const fallbackMessage = block.data?.content ?? '';
                const alertClassMap: Record<typeof type, string> = {
                    info: 'border-sky-200 bg-sky-50 text-sky-900',
                    warning: 'border-amber-200 bg-amber-50 text-amber-900',
                    danger: 'border-rose-200 bg-rose-50 text-rose-950',
                    neutral: 'border-zinc-200 bg-white text-zinc-900',
                };

                return (
                    <div
                        key={key}
                        className={cn(
                            'mb-6 rounded-2xl border px-4 py-4 text-sm leading-relaxed lg:px-5 lg:py-5',
                            alertClassMap[type],
                        )}
                    >
                        {hasStructuredContent ? (
                            <div className="space-y-4">
                                {renderBlocks(innerBlocks, alertThemes[type])}
                            </div>
                        ) : (
                            <div
                                className={alertThemes[type]?.textClass}
                                dangerouslySetInnerHTML={{ __html: fallbackMessage.replace(/\n/g, '<br />') }}
                            />
                        )}
                    </div>
                );
            }

            case 'paragraph':
            case 'code':
            default:
                if (block.type === 'code') {
                    const language = (block.data?.language as string) ?? 'javascript';
                    const languageClass = `language-${language}`;

                    return (
                        <div className="mb-6 overflow-hidden rounded-2xl bg-zinc-950 shadow-inner shadow-black/30">
                            <pre
                                key={key}
                                className={cn(
                                    'language-pre block w-full overflow-auto px-5 py-4 text-sm lg:px-6 lg:py-5',
                                    languageClass,
                                )}
                            >
                                <code className={languageClass}>{block.data.code}</code>
                            </pre>
                        </div>
                    );
                }

                return (
                    <p
                        key={key}
                        dangerouslySetInnerHTML={{ __html: block.data.text ?? '' }}
                        className={cn('mb-6 last:mb-0', theme?.textClass ?? 'text-zinc-600')}
                    />
                );
        }
    });
}

function renderListItems(
    items: EditorListItem[] = [],
    keyPrefix = '',
    style: ListStyle = 'unordered',
    theme?: RenderTheme,
): ReactNode[] {
    return items.map((item, index) => {
        const itemKey = `${keyPrefix}-${index}`;
        const content =
            typeof item === 'string'
                ? item
                : typeof item?.content === 'string'
                  ? item.content
                  : '';
        const childItems =
            typeof item === 'object' && item !== null && Array.isArray(item.items)
                ? (item.items as EditorListItem[])
                : [];

        const bullet = style === 'ordered' ? `${index + 1}.` : '–';

        return (
            <li key={itemKey} className="list-none space-y-1.5">
                <div className={cn('flex gap-3', theme?.textClass ?? 'text-zinc-900')}>
                    <span className="font-normal">{bullet}</span>
                    <div className="flex-1" dangerouslySetInnerHTML={{ __html: content }} />
                </div>
                {childItems.length > 0 && (
                    <ul className="ml-6 list-none space-y-1.5">
                        {renderListItems(childItems, `${itemKey}-child`, style, theme)}
                    </ul>
                )}
            </li>
        );
    });
}

const alertThemes: Record<
    'info' | 'warning' | 'danger' | 'neutral',
    RenderTheme
> = {
    info: {
        textClass: 'text-sky-900',
        headingClass: {
            2: '!text-sky-900',
            3: '!text-sky-900',
            4: '!text-sky-900',
        },
    },
    warning: {
        textClass: 'text-amber-900',
        headingClass: {
            2: '!text-amber-900',
            3: '!text-amber-900',
            4: '!text-amber-900',
        },
    },
    danger: {
        textClass: 'text-rose-950',
        headingClass: {
            2: '!text-rose-950',
            3: '!text-rose-950',
            4: '!text-rose-950',
        },
    },
    neutral: {
        textClass: 'text-zinc-900',
        headingClass: {
            2: '!text-zinc-900',
            3: '!text-zinc-900',
            4: '!text-zinc-900',
        },
    },
};

function getHeadingId(block: OutputBlockData, fallbackIndex: number): string {
    if (block.id) {
        return String(block.id);
    }

    const rawText =
        typeof block.data?.text === 'string'
            ? block.data.text.replace(/<[^>]*>/g, '').trim().toLowerCase()
            : '';

    if (rawText) {
        const slug = rawText
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        if (slug) {
            return `${slug}-${fallbackIndex}`;
        }
    }

    return `heading-${fallbackIndex}`;
}

