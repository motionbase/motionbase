import { cn } from '@/lib/utils';
import { Minus, Plus } from 'lucide-react';

interface DiffItem {
    type: 'added' | 'removed' | 'changed';
    old: unknown;
    new: unknown;
}

interface DiffViewerProps {
    diff: Record<string, DiffItem>;
    revisionContent: Record<string, unknown>;
    currentContent?: Record<string, unknown>;
    hasPrevious?: boolean;
}

interface EditorBlock {
    id?: string;
    type: string;
    data: Record<string, unknown>;
}

interface EditorData {
    blocks?: EditorBlock[];
    time?: number;
    version?: string;
}

type BlockDiff = {
    type: 'unchanged' | 'added' | 'removed' | 'changed';
    oldBlock?: EditorBlock;
    newBlock?: EditorBlock;
};

export function DiffViewer({ diff, revisionContent, currentContent, hasPrevious = true }: DiffViewerProps) {
    const entries = Object.entries(diff);

    const formatValue = (value: unknown): string => {
        if (value === null || value === undefined) {
            return '(leer)';
        }
        if (typeof value === 'object') {
            return JSON.stringify(value, null, 2);
        }
        return String(value);
    };

    // Compare two blocks to see if they're the same
    const blocksAreEqual = (a: EditorBlock, b: EditorBlock): boolean => {
        if (a.type !== b.type) return false;
        return JSON.stringify(a.data) === JSON.stringify(b.data);
    };

    // Compute block-level diff between old and new content using block IDs
    const computeBlockDiff = (oldContent: unknown, newContent: unknown): BlockDiff[] => {
        const oldData = oldContent as EditorData | null;
        const newData = newContent as EditorData | null;

        const oldBlocks = oldData?.blocks || [];
        const newBlocks = newData?.blocks || [];

        const diffs: BlockDiff[] = [];

        // Create maps by block ID for efficient lookup
        const oldBlocksById = new Map<string, EditorBlock>();
        const newBlocksById = new Map<string, EditorBlock>();

        oldBlocks.forEach((block, index) => {
            const id = block.id || `index-${index}`;
            oldBlocksById.set(id, block);
        });

        newBlocks.forEach((block, index) => {
            const id = block.id || `index-${index}`;
            newBlocksById.set(id, block);
        });

        // Track which new blocks we've already matched
        const matchedNewIds = new Set<string>();

        // First pass: find removed and changed blocks
        oldBlocks.forEach((oldBlock, index) => {
            const oldId = oldBlock.id || `index-${index}`;
            const newBlock = newBlocksById.get(oldId);

            if (!newBlock) {
                diffs.push({ type: 'removed', oldBlock });
            } else {
                matchedNewIds.add(oldId);
                if (!blocksAreEqual(oldBlock, newBlock)) {
                    diffs.push({ type: 'changed', oldBlock, newBlock });
                }
            }
        });

        // Second pass: find added blocks
        newBlocks.forEach((newBlock, index) => {
            const newId = newBlock.id || `index-${index}`;
            if (!matchedNewIds.has(newId)) {
                diffs.push({ type: 'added', newBlock });
            }
        });

        return diffs;
    };

    // Render block content
    const renderBlockContent = (block: EditorBlock) => {
        return (
            <div className="prose prose-sm max-w-none dark:prose-invert">
                {block.type === 'paragraph' && (
                    <div dangerouslySetInnerHTML={{ __html: String(block.data.text || '') }} />
                )}
                {block.type === 'header' && (
                    <div
                        className={cn(
                            'font-bold',
                            block.data.level === 1 && 'text-2xl',
                            block.data.level === 2 && 'text-xl',
                            block.data.level === 3 && 'text-lg'
                        )}
                        dangerouslySetInnerHTML={{ __html: String(block.data.text || '') }}
                    />
                )}
                {block.type === 'list' && (
                    <ul className={cn(
                        'list-inside space-y-1',
                        block.data.style === 'ordered' ? 'list-decimal' : 'list-disc'
                    )}>
                        {(block.data.items as string[] || []).map((item, i) => (
                            <li key={i} dangerouslySetInnerHTML={{ __html: item }} />
                        ))}
                    </ul>
                )}
                {block.type === 'quote' && (
                    <blockquote className="border-l-4 border-muted-foreground/30 pl-4 italic">
                        <div dangerouslySetInnerHTML={{ __html: String(block.data.text || '') }} />
                    </blockquote>
                )}
                {block.type === 'code' && (
                    <pre className="bg-muted rounded-md p-3 overflow-auto">
                        <code className="text-xs">{String(block.data.code || '')}</code>
                    </pre>
                )}
                {!['paragraph', 'header', 'list', 'quote', 'code'].includes(block.type) && (
                    <div className="text-xs text-muted-foreground">
                        <span className="font-medium">{block.type}</span>
                        <pre className="mt-1 text-[10px] overflow-auto">
                            {JSON.stringify(block.data, null, 2)}
                        </pre>
                    </div>
                )}
            </div>
        );
    };

    const shouldShowFullDiff = (key: string) => {
        return ['title', 'content', 'category_id'].includes(key);
    };

    // Render full content section
    const renderFullContent = () => {
        const content = revisionContent.content as EditorData | null;
        const blocks = content?.blocks || [];

        if (blocks.length === 0) {
            return (
                <div className="text-center text-muted-foreground py-6">
                    Kein Inhalt
                </div>
            );
        }

        return (
            <div className="space-y-3">
                {blocks.map((block, idx) => (
                    <div key={block.id || idx}>
                        {renderBlockContent(block)}
                    </div>
                ))}
            </div>
        );
    };

    return (
        <div className="space-y-6">
            {/* Full content section - always show */}
            <div className="rounded-lg border-2 border-blue-500/50">
                <div className="px-4 py-2 border-b bg-blue-50 dark:bg-blue-950/30">
                    <div className="text-sm font-semibold">
                        Inhalt dieser Version
                    </div>
                    <div className="text-xs text-muted-foreground mt-0.5">
                        So sah die Version aus
                    </div>
                </div>
                <div className="p-4">
                    {renderFullContent()}
                </div>
            </div>

            {/* Diff section - only show if there's a previous version */}
            {hasPrevious && entries.length > 0 && (
                <div>
                    <div className="mb-3 px-1">
                        <h3 className="text-sm font-semibold mb-1">
                            Änderungen gegenüber vorheriger Version
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            Was wurde in dieser Version geändert
                        </p>
                    </div>

                    <div className="space-y-4">
                        {entries.map(([key, item]) => {
                            if (!shouldShowFullDiff(key)) {
                                return null;
                            }

                            // Special handling for content (Editor.js blocks)
                            if (key === 'content') {
                                const blockDiffs = computeBlockDiff(item.old, item.new);

                                if (blockDiffs.length === 0) {
                                    return (
                                        <div key={key} className="text-center text-muted-foreground py-6">
                                            Keine Änderungen am Inhalt
                                        </div>
                                    );
                                }

                                return (
                                    <div key={key} className="space-y-3">
                                        {blockDiffs.map((blockDiff, idx) => (
                                            <div key={idx}>
                                                {/* Added block */}
                                                {blockDiff.type === 'added' && blockDiff.newBlock && (
                                                    <div className="rounded-lg border-2 border-green-500/50">
                                                        <div className="px-4 py-2 border-b flex items-center gap-2">
                                                            <div className="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                                                                <Plus className="h-3 w-3 text-white" />
                                                            </div>
                                                            <span className="text-sm font-medium">In dieser Version hinzugefügt</span>
                                                        </div>
                                                        <div className="px-4 py-3">
                                                            {renderBlockContent(blockDiff.newBlock)}
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Removed block */}
                                                {blockDiff.type === 'removed' && blockDiff.oldBlock && (
                                                    <div className="rounded-lg border-2 border-red-500/50">
                                                        <div className="px-4 py-2 border-b flex items-center gap-2">
                                                            <div className="w-5 h-5 rounded-full bg-red-500 flex items-center justify-center">
                                                                <Minus className="h-3 w-3 text-white" />
                                                            </div>
                                                            <span className="text-sm font-medium">In dieser Version entfernt</span>
                                                        </div>
                                                        <div className="px-4 py-3 opacity-60">
                                                            {renderBlockContent(blockDiff.oldBlock)}
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Changed block */}
                                                {blockDiff.type === 'changed' && blockDiff.oldBlock && blockDiff.newBlock && (
                                                    <div className="rounded-lg border-2 border-orange-500/50">
                                                        <div className="px-4 py-2 border-b flex items-center gap-2">
                                                            <div className="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center text-white text-xs font-bold">
                                                                ~
                                                            </div>
                                                            <span className="text-sm font-medium">In dieser Version geändert</span>
                                                        </div>
                                                        <div className="grid grid-cols-2 divide-x">
                                                            {/* Old version */}
                                                            <div className="p-4">
                                                                <div className="text-xs font-medium text-muted-foreground mb-3 uppercase tracking-wide">
                                                                    Vorherige Version
                                                                </div>
                                                                <div className="opacity-60">
                                                                    {renderBlockContent(blockDiff.oldBlock)}
                                                                </div>
                                                            </div>
                                                            {/* New version */}
                                                            <div className="p-4">
                                                                <div className="text-xs font-medium text-muted-foreground mb-3 uppercase tracking-wide">
                                                                    Diese Version
                                                                </div>
                                                                {renderBlockContent(blockDiff.newBlock)}
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                );
                            }

                            // For non-content fields (title, category_id)
                            return (
                                <div key={key} className="rounded-lg border-2 border-orange-500/50">
                                    <div className="px-4 py-2 border-b">
                                        <div className="text-sm font-medium">
                                            {key === 'title' && 'Titel geändert'}
                                            {key === 'category_id' && 'Kategorie geändert'}
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 divide-x">
                                        {/* Old value */}
                                        <div className="px-4 py-3">
                                            <div className="text-xs font-medium text-muted-foreground mb-2 uppercase tracking-wide">
                                                Vorherige Version
                                            </div>
                                            <div className="text-sm font-medium opacity-60">
                                                {formatValue(item.old)}
                                            </div>
                                        </div>
                                        {/* New value */}
                                        <div className="px-4 py-3">
                                            <div className="text-xs font-medium text-muted-foreground mb-2 uppercase tracking-wide">
                                                Diese Version
                                            </div>
                                            <div className="text-sm font-medium">
                                                {formatValue(item.new)}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {!hasPrevious && (
                <div className="text-center text-muted-foreground py-4 text-sm">
                    Dies ist die erste Version - keine vorherige Version zum Vergleich
                </div>
            )}
        </div>
    );
}
