import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { History, Clock, User, RotateCcw } from 'lucide-react';
import { cn } from '@/lib/utils';
import { DiffViewer } from './diff-viewer';
import { router } from '@inertiajs/react';

interface Revision {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    revision_type: string;
    created_at: string;
    created_at_human: string;
}

interface RevisionDetail extends Revision {
    content: Record<string, unknown>;
    diff: Record<string, {
        type: 'added' | 'removed' | 'changed';
        old: unknown;
        new: unknown;
    }>;
    revisionable?: Record<string, unknown>;
    has_previous: boolean;
}

interface VersionHistoryProps {
    modelType: 'topic' | 'chapter' | 'section';
    modelId: number;
    onRestore?: () => void;
}

export function VersionHistory({ modelType, modelId, onRestore }: VersionHistoryProps) {
    const [revisions, setRevisions] = useState<Revision[]>([]);
    const [selectedRevision, setSelectedRevision] = useState<RevisionDetail | null>(null);
    const [loading, setLoading] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    // Load revisions
    const loadRevisions = async () => {
        setLoading(true);
        try {
            const response = await fetch(`/admin/revisions/${modelType}/${modelId}`);
            const data = await response.json();
            setRevisions(data);
            setIsOpen(true);
        } catch (error) {
            console.error('Failed to load revisions:', error);
        } finally {
            setLoading(false);
        }
    };

    // Load revision details
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

    // Restore revision
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

    if (!isOpen) {
        return (
            <Button
                variant="outline"
                size="sm"
                onClick={loadRevisions}
                disabled={loading}
            >
                <History className="h-4 w-4 mr-2" />
                Versionshistorie
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
            </div>

            {/* Content */}
            <div className="flex-1 overflow-y-auto">
                {selectedRevision ? (
                    <div className="p-4">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSelectedRevision(null)}
                            className="mb-4"
                        >
                            ← Zurück
                        </Button>

                        <div className="mb-4 p-3 bg-muted rounded-lg">
                            <div className="flex items-center gap-2 text-sm mb-2">
                                <User className="h-4 w-4" />
                                <span className="font-medium">{selectedRevision.user.name}</span>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Clock className="h-4 w-4" />
                                <span>{selectedRevision.created_at_human}</span>
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
                ) : (
                    <div className="p-4 space-y-2">
                        {revisions.length === 0 ? (
                            <div className="text-center text-muted-foreground py-8">
                                Keine Versionen vorhanden
                            </div>
                        ) : (
                            revisions.map((revision) => (
                                <button
                                    key={revision.id}
                                    onClick={() => loadRevisionDetails(revision.id)}
                                    className={cn(
                                        'w-full text-left p-3 rounded-lg border hover:bg-accent transition-colors',
                                        'flex flex-col gap-2'
                                    )}
                                >
                                    <div className="flex items-center gap-2">
                                        <User className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium text-sm">{revision.user.name}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Clock className="h-3 w-3" />
                                        <span>{revision.created_at_human}</span>
                                    </div>
                                    <div className="text-xs">
                                        <span className={cn(
                                            'px-2 py-0.5 rounded',
                                            revision.revision_type === 'create' && 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            revision.revision_type === 'update' && 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            revision.revision_type === 'delete' && 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                        )}>
                                            {revision.revision_type === 'create' ? 'Erstellt' :
                                             revision.revision_type === 'update' ? 'Aktualisiert' :
                                             'Gelöscht'}
                                        </span>
                                    </div>
                                </button>
                            ))
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
