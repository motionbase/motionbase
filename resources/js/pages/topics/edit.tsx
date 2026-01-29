import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { type BreadcrumbItem, type Category, type Chapter, type Section, type Topic } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import type { OutputData } from '@editorjs/editorjs';
import { useEffect, useRef, useCallback } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    ArrowLeft,
    Check,
    ChevronDown,
    ChevronRight,
    FileText,
    FolderOpen,
    MoreHorizontal,
    PanelLeftClose,
    PanelLeftOpen,
    Pencil,
    Plus,
    Save,
    Settings,
    Trash2,
    X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import { TopicHistory } from '@/components/revisions/topic-history';
import { CreateItemDialog } from '@/components/create-item-dialog';

type EditorContent = {
    time: number;
    blocks: Array<Record<string, unknown>>;
    version: string;
};

interface TopicsEditProps {
    topic: Topic & {
        category_id: number;
        chapters: Array<Chapter>;
    };
    activeSection: Section | null;
    categories: Pick<Category, 'id' | 'name'>[];
}

export default function TopicsEdit({ topic, activeSection, categories }: TopicsEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Themen', href: '/admin/topics' },
        { title: topic.title, href: `/admin/topics/${topic.id}/edit` },
    ];

    // Sidebar visibility
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [settingsOpen, setSettingsOpen] = useState(false);

    // Dialog for creating chapters/sections
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogType, setDialogType] = useState<'chapter' | 'section'>('chapter');
    const [dialogChapterId, setDialogChapterId] = useState<number | undefined>(undefined);

    // Settings panel tabs
    const [settingsTab, setSettingsTab] = useState<'topic' | 'chapter' | 'section'>('topic');
    const [selectedChapterId, setSelectedChapterId] = useState<number | null>(null);

    // Track expanded chapters
    const [expandedChapters, setExpandedChapters] = useState<Set<number>>(() => {
        const activeChapterId = topic.chapters.find((ch) =>
            ch.sections.some((s) => s.id === activeSection?.id)
        )?.id;
        return new Set(activeChapterId ? [activeChapterId] : topic.chapters.slice(0, 1).map((ch) => ch.id));
    });

    const toggleChapter = (chapterId: number) => {
        setExpandedChapters((prev) => {
            const next = new Set(prev);
            if (next.has(chapterId)) {
                next.delete(chapterId);
            } else {
                next.add(chapterId);
            }
            return next;
        });
    };

    const topicForm = useForm({
        title: topic.title,
        slug: topic.slug,
        category_id: topic.category_id,
    });

    const sectionForm = useForm<{
        title: string;
        content: EditorContent | null;
    }>({
        title: activeSection?.title ?? '',
        content: (activeSection?.content ?? null) as EditorContent | null,
    });

    // Ref to track the current section ID to prevent stale updates
    const activeSectionIdRef = useRef(activeSection?.id);
    const [isSwitchingSection, setIsSwitchingSection] = useState(false);

    useEffect(() => {
        // Mark that we're switching sections
        setIsSwitchingSection(true);

        activeSectionIdRef.current = activeSection?.id;

        sectionForm.reset({
            title: activeSection?.title ?? '',
            content: (activeSection?.content ?? null) as EditorContent | null,
        });
        sectionForm.clearErrors();

        // Auto-select chapter when section changes
        if (activeSection) {
            const chapter = topic.chapters.find((ch) =>
                ch.sections.some((s) => s.id === activeSection.id)
            );
            if (chapter) {
                setSelectedChapterId(chapter.id);
            }
        }

        // Allow saving after a short delay to ensure form is properly reset
        const timer = setTimeout(() => {
            setIsSwitchingSection(false);
        }, 100);

        return () => clearTimeout(timer);
    }, [activeSection?.id]);

    // Warn user about unsaved changes before leaving
    useEffect(() => {
        const hasUnsavedChanges = sectionForm.isDirty || topicForm.isDirty;

        // Browser navigation (close tab, reload, etc.)
        const handleBeforeUnload = (e: BeforeUnloadEvent) => {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        };

        // Inertia navigation (internal links)
        const handleInertiaNavigate = (event: { detail: { visit: { url: { href: string } } } }) => {
            if (hasUnsavedChanges) {
                const currentUrl = window.location.href;
                const targetUrl = event.detail.visit.url.href;

                // Don't warn if staying on the same page (section changes)
                if (targetUrl.includes(`/admin/topics/${topic.id}/edit`)) {
                    return;
                }

                if (!confirm('Du hast ungespeicherte Änderungen. Möchtest du die Seite wirklich verlassen?')) {
                    event.preventDefault();
                }
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
        const removeInertiaListener = router.on('before', handleInertiaNavigate as any);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
            removeInertiaListener();
        };
    }, [sectionForm.isDirty, topicForm.isDirty, topic.id]);

    // Memoized onChange handler that checks section ID
    const handleEditorChange = useCallback((value: EditorContent) => {
        // Only update if we're still on the same section
        if (activeSectionIdRef.current === activeSection?.id) {
            sectionForm.setData('content', value);
        }
    }, [activeSection?.id, sectionForm]);

    const handleTopicSave = () => {
        topicForm.put(`/admin/topics/${topic.id}`, { preserveScroll: true });
    };

    const handleSectionSave = () => {
        if (!activeSection) return;

        // Double-check we're not trying to save stale data
        if (isSwitchingSection) {
            console.warn('Cannot save while switching sections');
            return;
        }

        // Verify we're saving to the correct section
        if (activeSectionIdRef.current !== activeSection.id) {
            console.error('Section ID mismatch - preventing save');
            return;
        }

        sectionForm.patch(`/admin/sections/${activeSection.id}`, {
            preserveScroll: true,
        });
    };

    const handleNavigateToSection = (sectionId: number) => {
        router.visit(`/admin/topics/${topic.id}/edit/${sectionId}`, {
            preserveScroll: true,
        });
    };

    const handleCreateChapter = () => {
        setDialogType('chapter');
        setDialogChapterId(undefined);
        setDialogOpen(true);
    };

    const handleDeleteChapter = (chapterId: number) => {
        if (!confirm('Dieses Kapitel und alle Abschnitte wirklich löschen?')) {
            return;
        }
        router.delete(`/admin/chapters/${chapterId}`, { preserveScroll: true });
    };

    const handleCreateSection = (chapterId: number) => {
        setDialogType('section');
        setDialogChapterId(chapterId);
        setDialogOpen(true);
    };

    const handleDeleteSection = (sectionId: number) => {
        if (!confirm('Diesen Abschnitt wirklich löschen?')) {
            return;
        }
        router.delete(`/admin/sections/${sectionId}`, { preserveScroll: true });
    };

    const totalSections = topic.chapters.reduce((acc, ch) => acc + ch.sections.length, 0);
    const hasUnsavedChanges = sectionForm.isDirty;

    // Chapter editing state
    const [editingChapterId, setEditingChapterId] = useState<number | null>(null);
    const [editingChapterTitle, setEditingChapterTitle] = useState('');

    const handleStartEditChapter = (chapter: Chapter) => {
        setEditingChapterId(chapter.id);
        setEditingChapterTitle(chapter.title);
    };

    const handleSaveChapterTitle = (chapterId: number) => {
        if (editingChapterTitle.trim()) {
            router.patch(`/admin/chapters/${chapterId}`, { title: editingChapterTitle }, { preserveScroll: true });
        }
        setEditingChapterId(null);
        setEditingChapterTitle('');
    };

    const handleCancelEditChapter = () => {
        setEditingChapterId(null);
        setEditingChapterTitle('');
    };

    return (
        <>
            <Head title={`Bearbeiten: ${topic.title}`} />

            {/* Gutenberg-style Full-screen Editor */}
            <div className="flex h-screen flex-col bg-white">
                {/* Top Bar */}
                <header className="flex h-14 shrink-0 items-center justify-between border-b border-zinc-100 bg-white px-4">
                    <div className="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 text-zinc-500 hover:text-zinc-900"
                            onClick={() => router.visit('/admin/topics')}
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Button>

                        <div className="hidden h-6 w-px bg-zinc-200 sm:block" />

                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 text-zinc-500 hover:text-zinc-900"
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                        >
                            {sidebarOpen ? (
                                <PanelLeftClose className="h-5 w-5" />
                            ) : (
                                <PanelLeftOpen className="h-5 w-5" />
                            )}
                        </Button>

                        <div className="hidden items-center gap-2 sm:flex">
                            <span className="text-sm font-medium text-zinc-900 truncate max-w-[200px]">
                                {topic.title}
                            </span>
                            {activeSection && (
                                <>
                                    <ChevronRight className="h-4 w-4 text-zinc-300" />
                                    <span className="text-sm text-zinc-500 truncate max-w-[180px]">
                                        {activeSection.title}
                                    </span>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {hasUnsavedChanges && (
                            <span className="hidden text-xs text-amber-600 sm:block">
                                Ungespeicherte Änderungen
                            </span>
                        )}

                        <TopicHistory topicId={topic.id} />

                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 text-zinc-500 hover:text-zinc-900"
                            onClick={() => setSettingsOpen(!settingsOpen)}
                        >
                            <Settings className="h-5 w-5" />
                        </Button>

                        <Button
                            onClick={handleSectionSave}
                            disabled={sectionForm.processing || !activeSection || isSwitchingSection}
                            className="h-9 bg-zinc-900 px-4 text-sm font-medium text-white hover:bg-zinc-800"
                        >
                            <Save className="mr-2 h-4 w-4" />
                            {sectionForm.processing ? 'Speichern…' : 'Speichern'}
                        </Button>
                    </div>
                </header>

                {/* Main Content Area */}
                <div className="flex flex-1 overflow-hidden">
                    {/* Left Sidebar - Document Structure */}
                    <aside
                        className={cn(
                            'flex h-full w-72 shrink-0 flex-col border-r border-zinc-100 bg-zinc-50/50 transition-all duration-200',
                            sidebarOpen ? 'translate-x-0' : '-translate-x-full absolute lg:relative lg:-ml-72',
                        )}
                    >
                        <div className="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                            <h2 className="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                Kursstruktur
                            </h2>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 gap-1.5 text-xs font-medium text-zinc-600 hover:text-zinc-900"
                                onClick={handleCreateChapter}
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Kapitel
                            </Button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-3">
                            <div className="space-y-2">
                                {topic.chapters.map((chapter, chapterIndex) => {
                                    const isExpanded = expandedChapters.has(chapter.id);
                                    const isEditing = editingChapterId === chapter.id;
                                    return (
                                        <div key={chapter.id} className="rounded-lg bg-white shadow-sm">
                                            <div className="flex items-center gap-1 p-2">
                                                <button
                                                    type="button"
                                                    onClick={() => toggleChapter(chapter.id)}
                                                    className="flex h-6 w-6 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                                                >
                                                    {isExpanded ? (
                                                        <ChevronDown className="h-4 w-4" />
                                                    ) : (
                                                        <ChevronRight className="h-4 w-4" />
                                                    )}
                                                </button>
                                                <FolderOpen className="h-4 w-4 shrink-0 text-amber-500" />
                                                
                                                {isEditing ? (
                                                    <div className="flex flex-1 items-center gap-1">
                                                        <input
                                                            type="text"
                                                            value={editingChapterTitle}
                                                            onChange={(e) => setEditingChapterTitle(e.target.value)}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') {
                                                                    handleSaveChapterTitle(chapter.id);
                                                                } else if (e.key === 'Escape') {
                                                                    handleCancelEditChapter();
                                                                }
                                                            }}
                                                            className="h-6 flex-1 rounded border border-zinc-300 bg-white px-2 text-sm font-medium text-zinc-700 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500"
                                                            autoFocus
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSaveChapterTitle(chapter.id)}
                                                            className="flex h-6 w-6 items-center justify-center rounded text-green-600 hover:bg-green-50"
                                                        >
                                                            <Check className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={handleCancelEditChapter}
                                                            className="flex h-6 w-6 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <>
                                                        <button
                                                            type="button"
                                                            className="flex-1 cursor-pointer truncate text-left text-sm font-medium text-zinc-700 hover:text-zinc-900"
                                                            onClick={() => {
                                                                setSelectedChapterId(chapter.id);
                                                                setSettingsTab('chapter');
                                                                setSettingsOpen(true);
                                                            }}
                                                        >
                                                            {chapter.title}
                                                        </button>
                                                        <span className="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500">
                                                            {chapter.sections.length}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleCreateSection(chapter.id)}
                                                            className="flex h-6 w-6 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                                                            title="Seite hinzufügen"
                                                        >
                                                            <Plus className="h-4 w-4" />
                                                        </button>
                                                    </>
                                                )}
                                            </div>

                                            {isExpanded && (
                                                <div className="space-y-0.5 px-2 pb-2">
                                                    {chapter.sections.map((section) => {
                                                        const isActive = section.id === activeSection?.id;
                                                        return (
                                                            <div
                                                                key={section.id}
                                                                className={cn(
                                                                    'group flex items-center gap-2 rounded-md px-2 py-1.5 transition-colors',
                                                                    isActive
                                                                        ? 'bg-zinc-900 text-white'
                                                                        : 'text-zinc-600 hover:bg-zinc-50',
                                                                )}
                                                            >
                                                                <button
                                                                    type="button"
                                                                    className="flex flex-1 items-center gap-2 text-left"
                                                                    onClick={() => handleNavigateToSection(section.id)}
                                                                >
                                                                    <FileText
                                                                        className={cn(
                                                                            'h-4 w-4 shrink-0',
                                                                            isActive ? 'text-white/70' : 'text-zinc-400',
                                                                        )}
                                                                    />
                                                                    <span className="truncate text-sm">
                                                                        {section.title}
                                                                    </span>
                                                                </button>
                                                            </div>
                                                        );
                                                    })}
                                                    {chapter.sections.length === 0 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleCreateSection(chapter.id)}
                                                            className="flex w-full items-center gap-2 rounded-md border border-dashed border-zinc-200 px-2 py-2 text-xs text-zinc-400 hover:border-zinc-300 hover:text-zinc-500"
                                                        >
                                                            <Plus className="h-3.5 w-3.5" />
                                                            Abschnitt hinzufügen
                                                        </button>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}

                                {topic.chapters.length === 0 && (
                                    <button
                                        type="button"
                                        onClick={handleCreateChapter}
                                        className="flex w-full flex-col items-center gap-2 rounded-xl border-2 border-dashed border-zinc-200 px-4 py-8 text-zinc-400 transition-colors hover:border-zinc-300 hover:text-zinc-500"
                                    >
                                        <FolderOpen className="h-8 w-8" />
                                        <span className="text-sm font-medium">Erstes Kapitel anlegen</span>
                                    </button>
                                )}
                            </div>
                        </div>
                    </aside>

                    {/* Editor Area */}
                    <main className="flex flex-1 flex-col overflow-hidden bg-white">
                        {activeSection ? (
                            <>
                                {/* Section Title */}
                                <div className="shrink-0 border-b border-zinc-100 py-6 pl-16 pr-8 lg:pl-20 lg:pr-12">
                                    <input
                                        type="text"
                                        value={sectionForm.data.title}
                                        onChange={(event) => sectionForm.setData('title', event.target.value)}
                                        placeholder="Titel eingeben…"
                                        className="w-full border-none bg-transparent text-3xl font-bold text-zinc-900 placeholder:text-zinc-300 focus:outline-none focus:ring-0"
                                    />
                                </div>

                                {/* Editor */}
                                <div className="flex-1 overflow-y-auto py-6 pl-16 pr-8 lg:pl-20 lg:pr-12">
                                    <RichTextEditor
                                        key={activeSection.id}
                                        initialValue={(activeSection.content ?? null) as OutputData}
                                        onChange={(value) => handleEditorChange(value as EditorContent)}
                                        className="gutenberg-editor min-h-[60vh] border-0 bg-transparent shadow-none"
                                        placeholder="Beginne zu schreiben oder drücke / für Blöcke…"
                                    />
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-1 flex-col items-center justify-center gap-4 text-zinc-400">
                                <FileText className="h-16 w-16 text-zinc-200" />
                                <div className="text-center">
                                    <p className="text-lg font-medium text-zinc-500">Kein Abschnitt ausgewählt</p>
                                    <p className="mt-1 text-sm">
                                        Wähle einen Abschnitt aus der Seitenleiste oder erstelle einen neuen.
                                    </p>
                                </div>
                            </div>
                        )}
                    </main>

                    {/* Right Sidebar - Settings */}
                    <aside
                        className={cn(
                            'flex h-full w-80 shrink-0 flex-col border-l border-zinc-100 bg-white transition-all duration-200',
                            settingsOpen ? 'translate-x-0' : 'translate-x-full absolute right-0 lg:relative lg:mr-0 lg:hidden',
                        )}
                    >
                        <div className="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                            <h2 className="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                                Einstellungen
                            </h2>
                            <button
                                type="button"
                                onClick={() => setSettingsOpen(false)}
                                className="flex h-6 w-6 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 lg:hidden"
                            >
                                ×
                            </button>
                        </div>

                        {/* Tabs */}
                        <div className="flex border-b border-zinc-200">
                            <button
                                type="button"
                                onClick={() => setSettingsTab('topic')}
                                className={cn(
                                    'flex-1 px-4 py-2 text-xs font-medium transition-colors',
                                    settingsTab === 'topic'
                                        ? 'border-b-2 border-zinc-900 text-zinc-900'
                                        : 'text-zinc-500 hover:text-zinc-700'
                                )}
                            >
                                Thema
                            </button>
                            <button
                                type="button"
                                onClick={() => setSettingsTab('chapter')}
                                className={cn(
                                    'flex-1 px-4 py-2 text-xs font-medium transition-colors',
                                    settingsTab === 'chapter'
                                        ? 'border-b-2 border-zinc-900 text-zinc-900'
                                        : 'text-zinc-500 hover:text-zinc-700'
                                )}
                            >
                                Kapitel
                            </button>
                            <button
                                type="button"
                                onClick={() => setSettingsTab('section')}
                                className={cn(
                                    'flex-1 px-4 py-2 text-xs font-medium transition-colors',
                                    settingsTab === 'section'
                                        ? 'border-b-2 border-zinc-900 text-zinc-900'
                                        : 'text-zinc-500 hover:text-zinc-700'
                                )}
                            >
                                Seite
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-4">
                            {/* Topic Settings */}
                            {settingsTab === 'topic' && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label className="text-xs font-semibold">Titel</Label>
                                        <Input
                                            value={topicForm.data.title}
                                            onChange={(e) => topicForm.setData('title', e.target.value)}
                                            onBlur={handleTopicSave}
                                            className="h-9 text-sm"
                                        />
                                        <InputError message={topicForm.errors.title} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-xs font-semibold">Slug</Label>
                                        <Input
                                            value={topicForm.data.slug}
                                            onChange={(e) => topicForm.setData('slug', e.target.value)}
                                            onBlur={handleTopicSave}
                                            className="h-9 text-sm font-mono"
                                        />
                                        <InputError message={topicForm.errors.slug} />
                                        <p className="text-xs text-muted-foreground">
                                            URL-freundliche Version des Titels
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-xs font-semibold">Kategorie</Label>
                                        <Select
                                            value={String(topicForm.data.category_id)}
                                            onValueChange={(value) => {
                                                topicForm.setData('category_id', parseInt(value));
                                            }}
                                        >
                                            <SelectTrigger className="h-9 text-sm">
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

                                    {topicForm.isDirty && (
                                        <Button
                                            onClick={handleTopicSave}
                                            disabled={topicForm.processing}
                                            className="w-full h-9 text-sm"
                                        >
                                            {topicForm.processing ? 'Speichern...' : 'Änderungen speichern'}
                                        </Button>
                                    )}

                                    <div className="rounded-lg bg-zinc-50 p-3">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-zinc-500">Kapitel</span>
                                            <span className="font-medium text-zinc-900">{topic.chapters.length}</span>
                                        </div>
                                        <div className="mt-2 flex items-center justify-between text-sm">
                                            <span className="text-zinc-500">Seiten</span>
                                            <span className="font-medium text-zinc-900">{totalSections}</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Chapter Settings */}
                            {settingsTab === 'chapter' && (() => {
                                const selectedChapter = topic.chapters.find(ch => ch.id === selectedChapterId);
                                if (!selectedChapter) {
                                    return (
                                        <div className="flex flex-col items-center justify-center py-8 text-center">
                                            <FolderOpen className="h-12 w-12 text-zinc-300 mb-3" />
                                            <p className="text-sm text-zinc-500">Kein Kapitel ausgewählt</p>
                                            <p className="text-xs text-zinc-400 mt-1">Wähle ein Kapitel aus der Seitenleiste</p>
                                        </div>
                                    );
                                }
                                return (
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label className="text-xs font-semibold">Kapitel-Titel</Label>
                                            <Input
                                                defaultValue={selectedChapter.title}
                                                onBlur={(e) => {
                                                    if (e.target.value !== selectedChapter.title) {
                                                        router.patch(`/admin/chapters/${selectedChapter.id}`, {
                                                            title: e.target.value,
                                                        }, { preserveScroll: true });
                                                    }
                                                }}
                                                className="h-9 text-sm"
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label className="text-xs font-semibold">Slug</Label>
                                            <Input
                                                defaultValue={selectedChapter.slug}
                                                onBlur={(e) => {
                                                    if (e.target.value !== selectedChapter.slug) {
                                                        router.patch(`/admin/chapters/${selectedChapter.id}`, {
                                                            slug: e.target.value,
                                                        }, { preserveScroll: true });
                                                    }
                                                }}
                                                className="h-9 text-sm font-mono"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                URL-freundliche Version des Titels
                                            </p>
                                        </div>

                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id={`chapter-published-${selectedChapter.id}`}
                                                checked={selectedChapter.is_published}
                                                onCheckedChange={(checked) => {
                                                    router.patch(`/admin/chapters/${selectedChapter.id}`, {
                                                        is_published: checked,
                                                    }, { preserveScroll: true });
                                                }}
                                            />
                                            <Label
                                                htmlFor={`chapter-published-${selectedChapter.id}`}
                                                className="text-sm font-normal cursor-pointer"
                                            >
                                                Veröffentlicht
                                            </Label>
                                        </div>

                                        <div className="pt-4 border-t">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDeleteChapter(selectedChapter.id)}
                                                className="w-full text-red-600 hover:text-red-700 hover:bg-red-50"
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                Kapitel löschen
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })()}

                            {/* Section Settings */}
                            {settingsTab === 'section' && (
                                activeSection ? (
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label className="text-xs font-semibold">Seiten-Titel</Label>
                                            <Input
                                                value={sectionForm.data.title}
                                                onChange={(e) => sectionForm.setData('title', e.target.value)}
                                                onBlur={() => {
                                                    if (sectionForm.data.title !== activeSection.title) {
                                                        router.patch(`/admin/sections/${activeSection.id}`, {
                                                            title: sectionForm.data.title,
                                                        }, { preserveScroll: true });
                                                    }
                                                }}
                                                className="h-9 text-sm"
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label className="text-xs font-semibold">Slug</Label>
                                            <Input
                                                defaultValue={activeSection.slug}
                                                onBlur={(e) => {
                                                    if (e.target.value !== activeSection.slug) {
                                                        router.patch(`/admin/sections/${activeSection.id}`, {
                                                            slug: e.target.value,
                                                        }, { preserveScroll: true });
                                                    }
                                                }}
                                                className="h-9 text-sm font-mono"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                URL-freundliche Version des Titels
                                            </p>
                                        </div>

                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id={`section-published-${activeSection.id}`}
                                                checked={activeSection.is_published}
                                                onCheckedChange={(checked) => {
                                                    router.patch(`/admin/sections/${activeSection.id}`, {
                                                        is_published: checked,
                                                    }, { preserveScroll: true });
                                                }}
                                            />
                                            <Label
                                                htmlFor={`section-published-${activeSection.id}`}
                                                className="text-sm font-normal cursor-pointer"
                                            >
                                                Veröffentlicht
                                            </Label>
                                        </div>

                                        <div className="pt-4 border-t">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDeleteSection(activeSection.id)}
                                                className="w-full text-red-600 hover:text-red-700 hover:bg-red-50"
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                Seite löschen
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <FileText className="h-12 w-12 text-zinc-300 mb-3" />
                                        <p className="text-sm text-zinc-500">Keine Seite ausgewählt</p>
                                        <p className="text-xs text-zinc-400 mt-1">Wähle eine Seite aus der Seitenleiste</p>
                                    </div>
                                )
                            )}
                        </div>
                    </aside>
                </div>
            </div>

            {/* Dialog for creating chapters/sections */}
            <CreateItemDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
                type={dialogType}
                topicId={topic.id}
                chapterId={dialogChapterId}
            />
        </>
    );
}
