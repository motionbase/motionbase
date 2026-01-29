import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { History, Clock, User, FileText, FolderOpen, ListTree, RotateCcw, Eye } from 'lucide-react';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { DiffViewer } from './diff-viewer';

interface TopicRevision {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    revision_type: string;
    model_type: string;
    model_id: number;
    title: string;
    created_at: string;
    created_at_human: string;
}

interface RevisionDetail extends TopicRevision {
    content: Record<string, unknown>;
    diff: Record<string, {
        type: 'added' | 'removed' | 'changed';
        old: unknown;
        new: unknown;
    }>;
    revisionable?: Record<string, unknown>;
    has_previous: boolean;
}

interface TopicHistoryProps {
    topicId: number;
}

export function TopicHistory({ topicId }: TopicHistoryProps) {
    const [revisions, setRevisions] = useState<TopicRevision[]>([]);
    const [selectedRevision, setSelectedRevision] = useState<RevisionDetail | null>(null);
    const [loading, setLoading] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    const loadHistory = async () => {
        setLoading(true);
        try {
            const response = await fetch(`/admin/topics/${topicId}/history`);
            const data = await response.json();
            setRevisions(data);
            setIsOpen(true);
        } catch (error) {
            console.error('Failed to load topic history:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadRevisionDetails = async (revisionId: number) => {
        setLoading(true);
        try {
            const response = await fetch(`/admin/revisions/${revisionId}`);
            const data = await response.json();
            setSelectedRevision(data);
        } catch (error) {
            console.error('Failed to load revision details:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleRestore = async (revisionId: number) => {
        if (!confirm('Möchten Sie diese Version wirklich wiederherstellen?')) {
            return;
        }

        setLoading(true);
        try {
            const response = await fetch(`/admin/revisions/${revisionId}/restore`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            if (response.ok) {
                // Force a full page reload to ensure Editor.js reinitializes
                window.location.reload();
            } else {
                alert('Fehler beim Wiederherstellen der Version');
            }
        } catch (error) {
            console.error('Failed to restore revision:', error);
            alert('Fehler beim Wiederherstellen der Version');
        } finally {
            setLoading(false);
        }
    };

    const getModelIcon = (modelType: string) => {
        switch (modelType) {
            case 'Topic':
                return <ListTree className="h-4 w-4 text-purple-600" />;
            case 'Chapter':
                return <FolderOpen className="h-4 w-4 text-amber-600" />;
            case 'Section':
                return <FileText className="h-4 w-4 text-blue-600" />;
            default:
                return <FileText className="h-4 w-4" />;
        }
    };

    const getModelLabel = (modelType: string) => {
        switch (modelType) {
            case 'Topic':
                return 'Thema';
            case 'Chapter':
                return 'Kapitel';
            case 'Section':
                return 'Abschnitt';
            default:
                return modelType;
        }
    };

    const getActionLabel = (revisionType: string) => {
        switch (revisionType) {
            case 'create':
                return 'erstellt';
            case 'update':
                return 'bearbeitet';
            case 'delete':
                return 'gelöscht';
            default:
                return revisionType;
        }
    };

    const getActionColor = (revisionType: string) => {
        switch (revisionType) {
            case 'create':
                return 'text-green-700 dark:text-green-400';
            case 'update':
                return 'text-blue-700 dark:text-blue-400';
            case 'delete':
                return 'text-red-700 dark:text-red-400';
            default:
                return 'text-gray-700 dark:text-gray-400';
        }
    };

    if (!isOpen) {
        return (
            <Button
                variant="ghost"
                size="icon"
                className="h-9 w-9 text-zinc-500 hover:text-zinc-900"
                onClick={loadHistory}
                disabled={loading}
            >
                <History className="h-5 w-5" />
            </Button>
        );
    }

    return (
        <div className="fixed inset-y-0 right-0 w-[56rem] bg-background border-l shadow-lg z-50 flex flex-col">
            {/* Header */}
            <div className="p-4 border-b">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold flex items-center">
                        <History className="h-5 w-5 mr-2" />
                        Versionshistorie
                    </h2>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setIsOpen(false);
                            setSelectedRevision(null);
                        }}
                    >
                        ×
                    </Button>
                </div>
                {!selectedRevision && (
                    <p className="text-sm text-muted-foreground mt-1">
                        Alle Änderungen an diesem Thema
                    </p>
                )}
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto p-4">
                {selectedRevision ? (
                    <div>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSelectedRevision(null)}
                            className="mb-4"
                        >
                            ← Zurück zur Timeline
                        </Button>

                        <div className="mb-4 p-3 bg-muted rounded-lg">
                            <div className="flex items-center gap-2 text-sm mb-2">
                                <User className="h-4 w-4" />
                                <span className="font-medium">{selectedRevision.user.name}</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-muted-foreground mb-2">
                                <Clock className="h-4 w-4" />
                                <span>{selectedRevision.created_at_human}</span>
                            </div>
                            <div className="text-sm">
                                <span className="text-muted-foreground">
                                    {getModelLabel(selectedRevision.model_type)}
                                </span>
                                {' '}
                                <span className={cn('font-medium', getActionColor(selectedRevision.revision_type))}>
                                    {getActionLabel(selectedRevision.revision_type)}
                                </span>
                                {': '}
                                <span className="font-medium">"{selectedRevision.title}"</span>
                            </div>
                        </div>

                        <DiffViewer
                            diff={selectedRevision.diff}
                            revisionContent={selectedRevision.content}
                            currentContent={selectedRevision.revisionable}
                            hasPrevious={selectedRevision.has_previous}
                        />

                        <div className="mt-4 sticky bottom-0 bg-background pt-4 border-t">
                            <Button
                                onClick={() => handleRestore(selectedRevision.id)}
                                disabled={loading}
                                className="w-full"
                            >
                                <RotateCcw className="h-4 w-4 mr-2" />
                                Version wiederherstellen
                            </Button>
                        </div>
                    </div>
                ) : revisions.length === 0 ? (
                    <div className="text-center text-muted-foreground py-8">
                        Keine Historie vorhanden
                    </div>
                ) : (
                    <div className="relative">
                        {/* Timeline line */}
                        <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-border" />

                        <div className="space-y-6">
                            {revisions.map((revision, index) => (
                                <div key={revision.id} className="relative flex gap-4">
                                    {/* Timeline dot */}
                                    <div className="relative z-10 flex items-start">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full border-2 border-background bg-muted">
                                            {getModelIcon(revision.model_type)}
                                        </div>
                                    </div>

                                    {/* Content */}
                                    <div className="flex-1 pt-1">
                                        <button
                                            onClick={() => loadRevisionDetails(revision.id)}
                                            className="w-full text-left rounded-lg border bg-card p-3 shadow-sm hover:bg-accent transition-colors cursor-pointer"
                                        >
                                            <div className="flex items-start justify-between gap-2 mb-2">
                                                <div className="flex items-center gap-2">
                                                    <User className="h-3 w-3 text-muted-foreground" />
                                                    <span className="font-medium text-sm">
                                                        {revision.user.name}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <Clock className="h-3 w-3" />
                                                    {revision.created_at_human}
                                                </div>
                                            </div>

                                            <div className="text-sm">
                                                <span className="text-muted-foreground">
                                                    {getModelLabel(revision.model_type)}
                                                </span>
                                                {' '}
                                                <span className={cn('font-medium', getActionColor(revision.revision_type))}>
                                                    {getActionLabel(revision.revision_type)}
                                                </span>
                                                {': '}
                                                <span className="font-medium">
                                                    "{revision.title}"
                                                </span>
                                            </div>

                                            {/* Badge for type and View button */}
                                            <div className="mt-2 flex items-center justify-between">
                                                <span className={cn(
                                                    'inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium',
                                                    revision.model_type === 'Topic' && 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                    revision.model_type === 'Chapter' && 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                                    revision.model_type === 'Section' && 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                                )}>
                                                    {getModelIcon(revision.model_type)}
                                                    {getModelLabel(revision.model_type)}
                                                </span>
                                                <span className="text-xs text-muted-foreground flex items-center gap-1">
                                                    <Eye className="h-3 w-3" />
                                                    Details anzeigen
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
