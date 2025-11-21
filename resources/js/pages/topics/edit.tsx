import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category, type Section, type Topic } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import type { OutputData } from '@editorjs/editorjs';
import { useEffect, useMemo } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Plus, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';

type EditorContent = {
    time: number;
    blocks: Array<Record<string, unknown>>;
    version: string;
};

interface TopicsEditProps {
    topic: Topic & { category_id: number; sections: Array<Pick<Section, 'id' | 'title' | 'sort_order'>> };
    activeSection: Section | null;
    categories: Pick<Category, 'id' | 'name'>[];
}

export default function TopicsEdit({ topic, activeSection, categories }: TopicsEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Themen', href: '/topics' },
        { title: topic.title, href: `/topics/${topic.id}/edit` },
    ];

    const topicForm = useForm({
        title: topic.title,
        category_id: topic.category_id ? String(topic.category_id) : '',
    });

    const sectionForm = useForm<{
        title: string;
        content: EditorContent | null;
    }>({
        title: activeSection?.title ?? '',
        content: (activeSection?.content ?? null) as EditorContent | null,
    });

    useEffect(() => {
        sectionForm.setData({
            title: activeSection?.title ?? '',
            content: (activeSection?.content ?? null) as EditorContent | null,
        });
        sectionForm.clearErrors();
    }, [activeSection?.id]);

    const handleTopicSave = () => {
        topicForm.put(`/topics/${topic.id}`, { preserveScroll: true });
    };

    const handleSectionSave = () => {
        if (!activeSection) return;
        sectionForm.patch(`/sections/${activeSection.id}`, {
            preserveScroll: true,
        });
    };

    const handleNavigateToSection = (sectionId: number) => {
        router.visit(`/topics/${topic.id}/edit?section=${sectionId}`, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleCreateSection = () => {
        router.post(`/topics/${topic.id}/sections`, {}, { preserveScroll: true });
    };

    const handleDeleteSection = (sectionId: number) => {
        if (!confirm('Diesen Abschnitt wirklich löschen?')) {
            return;
        }
        router.delete(`/sections/${sectionId}`, { preserveScroll: true });
    };

    const tocItems = useMemo(() => {
        const blocks = sectionForm.data.content?.blocks ?? [];
        return blocks
            .filter((block) => block.type === 'header' && block.data?.text)
            .map((block, index) => ({
                id: `${block.id ?? index}`,
                text: block.data.text.replace(/<[^>]+>/g, ''),
                level: block.data.level ?? 2,
            }));
    }, [sectionForm.data.content]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Bearbeiten: ${topic.title}`} />

            <div className="grid gap-6 px-6 py-10 lg:grid-cols-[320px_minmax(0,1fr)_300px]">
                <Card className="border-zinc-100 bg-white shadow-sm">
                    <CardHeader className="border-b border-zinc-100 pb-4">
                        <CardTitle className="text-xs font-semibold uppercase tracking-[0.35em] text-zinc-400">
                            Kursverwaltung
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6 pt-6">
                        <div className="space-y-2">
                            <Label className="text-sm font-medium text-zinc-700">Titel</Label>
                            <Input
                                value={topicForm.data.title}
                                onChange={(event) => topicForm.setData('title', event.target.value)}
                                onBlur={handleTopicSave}
                                aria-invalid={!!topicForm.errors.title}
                            />
                            <InputError message={topicForm.errors.title} />
                        </div>

                        <div className="space-y-2">
                            <Label className="text-sm font-medium text-zinc-700">Kategorie</Label>
                            <Select
                                value={topicForm.data.category_id}
                                onValueChange={(value) => {
                                    topicForm.setData('category_id', value);
                                    topicForm.put(`/topics/${topic.id}`, { preserveScroll: true });
                                }}
                            >
                                <SelectTrigger className="border-zinc-200" aria-label="Kategorie wählen">
                                    <SelectValue placeholder="Kategorie wählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={topicForm.errors.category_id} />
                        </div>

                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-semibold text-zinc-900">Abschnitte</p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="border-zinc-200 text-xs font-semibold uppercase tracking-wider"
                                    onClick={handleCreateSection}
                                >
                                    <Plus className="mr-2 h-3.5 w-3.5" />
                                    Neu
                                </Button>
                            </div>
                            <div className="space-y-2">
                                {topic.sections.map((section, index) => {
                                    const isActive = section.id === activeSection?.id;
                                    return (
                                        <div
                                            key={section.id}
                                            className={cn(
                                                'flex items-center gap-2 rounded-2xl border px-3 py-2',
                                                isActive
                                                    ? 'border-zinc-900 bg-zinc-900 text-white'
                                                    : 'border-zinc-100 bg-white text-zinc-600 hover:border-zinc-200',
                                            )}
                                        >
                                            <button
                                                type="button"
                                                className="flex flex-1 items-center gap-3 text-left"
                                                onClick={() => handleNavigateToSection(section.id)}
                                            >
                                                <span
                                                    className={cn(
                                                        'flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold',
                                                        isActive ? 'bg-white/20 text-white' : 'bg-zinc-100 text-zinc-500',
                                                    )}
                                                >
                                                    {index + 1}
                                                </span>
                                                <span className="truncate">{section.title}</span>
                                            </button>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className={cn(
                                                            'h-6 w-6',
                                                            isActive
                                                                ? 'text-white hover:bg-white/10'
                                                                : 'text-zinc-400 hover:text-zinc-700',
                                                        )}
                                                    >
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent
                                                    align="end"
                                                    className="w-40 border border-zinc-100 bg-white shadow-lg"
                                                >
                                                    <DropdownMenuItem
                                                        className="text-red-600 focus:text-red-600"
                                                        onClick={() => handleDeleteSection(section.id)}
                                                    >
                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                        Löschen
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    );
                                })}
                                {topic.sections.length === 0 && (
                                    <div className="rounded-xl border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500">
                                        Noch keine Abschnitte. Lege deinen ersten Abschnitt an, um loszulegen.
                                    </div>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-zinc-100 bg-white shadow-sm">
                    {activeSection ? (
                        <>
                            <div className="flex flex-col gap-4 border-b border-zinc-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                                <Input
                                    value={sectionForm.data.title}
                                    onChange={(event) => sectionForm.setData('title', event.target.value)}
                                    placeholder="Abschnittstitel"
                                    className="border-none px-0 text-2xl font-semibold text-zinc-900 focus-visible:ring-0"
                                />
                                <Button
                                    onClick={handleSectionSave}
                                    disabled={sectionForm.processing}
                                    className="bg-[#ff0055] px-6 text-white hover:bg-[#ff0055]/90"
                                >
                                    {sectionForm.processing ? 'Speichern…' : 'Speichern'}
                                </Button>
                            </div>
                            <CardContent className="lg:h-[calc(100vh-260px)] lg:overflow-y-auto">
                                <RichTextEditor
                                    key={activeSection.id}
                                    initialValue={(activeSection.content ?? null) as OutputData}
                                    onChange={(value) => sectionForm.setData('content', value as EditorContent)}
                                    className="min-h-[600px] border border-zinc-200 bg-white"
                                    placeholder="Starte mit deinem Inhalt…"
                                />
                            </CardContent>
                        </>
                    ) : (
                        <div className="flex min-h-[400px] items-center justify-center text-zinc-400">
                            Kein Abschnitt ausgewählt.
                        </div>
                    )}
                </Card>

                <Card className="hidden border-zinc-100 bg-white shadow-sm lg:flex lg:flex-col">
                    <CardHeader className="border-b border-zinc-100">
                        <CardTitle className="text-sm font-semibold uppercase tracking-[0.35em] text-zinc-400">
                            Inhaltsverzeichnis
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex-1 overflow-y-auto pt-6">
                        {tocItems.length === 0 && (
                            <p className="text-sm text-zinc-500">
                                Füge Überschriften im Editor hinzu, um automatisch ein Inhaltsverzeichnis zu erstellen.
                            </p>
                        )}
                        <ul className="space-y-3 text-sm">
                            {tocItems.map((item) => (
                                <li
                                    key={item.id}
                                    className={cn(
                                        'border-l-2 border-transparent pl-3 text-zinc-600',
                                        item.level === 3 && 'ml-3 text-zinc-500',
                                        item.level >= 4 && 'ml-6 text-zinc-400',
                                    )}
                                >
                                    {item.text}
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
