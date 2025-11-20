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

            <div className="grid gap-4 lg:grid-cols-[280px_1fr_280px]">
                <Card className="border border-white/10 bg-black text-white">
                    <CardHeader className="border-b border-white/10">
                        <CardTitle className="text-sm uppercase tracking-[0.2em] text-white/50">
                            Kursstruktur
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6 p-5">
                        <div className="space-y-3">
                            <Label className="text-xs uppercase tracking-[0.3em] text-white/50">
                                Titel
                            </Label>
                            <Input
                                value={topicForm.data.title}
                                onChange={(event) => topicForm.setData('title', event.target.value)}
                                onBlur={handleTopicSave}
                                className="border-white/10 bg-transparent text-white focus-visible:ring-[#ff0055]"
                            />
                            <InputError message={topicForm.errors.title} />
                        </div>

                        <div className="space-y-3">
                            <Label className="text-xs uppercase tracking-[0.3em] text-white/50">
                                Kategorie
                            </Label>
                            <Select
                                value={topicForm.data.category_id}
                                onValueChange={(value) => {
                                    topicForm.setData('category_id', value);
                                    topicForm.put(`/topics/${topic.id}`, { preserveScroll: true });
                                }}
                            >
                                <SelectTrigger className="border-white/10 bg-transparent text-white focus:ring-[#ff0055]">
                                    <SelectValue placeholder="Kategorie wählen" />
                                </SelectTrigger>
                                <SelectContent className="bg-black text-white">
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-center justify-between text-xs uppercase tracking-[0.3em] text-white/50">
                            <span>Abschnitte</span>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-7 w-7 border border-white/10 text-white hover:bg-white/10"
                                onClick={handleCreateSection}
                            >
                                <Plus className="h-4 w-4" />
                            </Button>
                        </div>

                        <div className="space-y-2">
                            {topic.sections.map((section, index) => {
                                const isActive = section.id === activeSection?.id;
                                return (
                                    <div
                                        key={section.id}
                                        className={cn(
                                            'flex items-center justify-between rounded-xl border border-white/5 px-3 py-2 text-sm transition',
                                            isActive
                                                ? 'bg-[#ff0055]/20 text-white'
                                                : 'text-white/70 hover:bg-white/5',
                                        )}
                                    >
                                        <button
                                            type="button"
                                            className="flex flex-1 items-center gap-2 text-left"
                                            onClick={() => handleNavigateToSection(section.id)}
                                        >
                                            <span className="flex h-6 w-6 items-center justify-center rounded-full border border-white/10 text-xs">
                                                {index + 1}
                                            </span>
                                            <span className="truncate">{section.title}</span>
                                        </button>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-6 w-6 text-white/60 hover:bg-white/10 hover:text-white"
                                                >
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="bg-black text-white">
                                                <DropdownMenuItem
                                                    className="text-red-400 focus:text-red-400"
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
                        </div>
                    </CardContent>
                </Card>

                <Card className="border border-white/10 bg-black text-white">
                    {activeSection ? (
                        <>
                            <div className="flex items-center justify-between border-b border-white/10 px-6 py-4">
                                <Input
                                    value={sectionForm.data.title}
                                    onChange={(event) => sectionForm.setData('title', event.target.value)}
                                    placeholder="Abschnittstitel"
                                    className="border-none bg-transparent text-2xl font-semibold text-white focus-visible:ring-0"
                                />
                                <Button
                                    onClick={handleSectionSave}
                                    disabled={sectionForm.processing}
                                    className="rounded-full bg-[#ff0055] px-6 text-white hover:bg-[#ff0055]/90"
                                >
                                    {sectionForm.processing ? 'Speichern…' : 'Speichern'}
                                </Button>
                            </div>
                            <CardContent className="h-[calc(100vh-18rem)] overflow-y-auto p-6">
                                <RichTextEditor
                                    key={activeSection.id}
                                    initialValue={(activeSection.content ?? null) as OutputData}
                                    onChange={(value) => sectionForm.setData('content', value as EditorContent)}
                                    className="min-h-[600px] border border-white/10 bg-neutral-950"
                                    placeholder="Starte mit deinem Inhalt…"
                                />
                            </CardContent>
                        </>
                    ) : (
                        <div className="flex min-h-[400px] items-center justify-center text-white/50">
                            Kein Abschnitt ausgewählt.
                        </div>
                    )}
                </Card>

                <Card className="hidden border border-white/10 bg-black text-white lg:flex lg:flex-col">
                    <CardHeader className="border-b border-white/10">
                        <CardTitle className="text-sm uppercase tracking-[0.3em] text-white/50">
                            Inhaltsverzeichnis
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex-1 overflow-y-auto p-5">
                        {tocItems.length === 0 && (
                            <p className="text-sm text-white/50">
                                Füge Überschriften im Editor hinzu, um automatisch ein Inhaltsverzeichnis zu erstellen.
                            </p>
                        )}
                        <ul className="space-y-3 text-sm">
                            {tocItems.map((item) => (
                                <li
                                    key={item.id}
                                    className={cn(
                                        'border-l-2 border-transparent pl-3 text-white/70',
                                        item.level === 3 && 'ml-3 text-white/60',
                                        item.level >= 4 && 'ml-6 text-white/50',
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
