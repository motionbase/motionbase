import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { useCallback, useEffect, useState } from 'react';

interface MediaItem {
    id: number;
    filename: string;
    original_filename: string;
    url: string;
    mime_type: string;
    type: 'image' | 'lottie';
    size: number;
    width: number | null;
    height: number | null;
    alt: string | null;
    created_at: string;
}

interface MediaLibraryModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSelect: (media: MediaItem) => void;
    type?: 'image' | 'lottie' | 'all';
}

export function MediaLibraryModal({
    open,
    onOpenChange,
    onSelect,
    type = 'all',
}: MediaLibraryModalProps) {
    const [media, setMedia] = useState<MediaItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [activeType, setActiveType] = useState<'all' | 'image' | 'lottie'>(type === 'all' ? 'all' : type);

    const fetchMedia = useCallback(async (page = 1, searchQuery = '', mediaType = activeType) => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: page.toString(),
                per_page: '24',
            });
            
            if (searchQuery) {
                params.append('search', searchQuery);
            }
            
            if (mediaType !== 'all') {
                params.append('type', mediaType);
            }

            const response = await fetch(`/media?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch media');

            const data = await response.json();
            setMedia(data.data);
            setCurrentPage(data.meta.current_page);
            setTotalPages(data.meta.last_page);
        } catch (error) {
            console.error('Failed to fetch media:', error);
        } finally {
            setLoading(false);
        }
    }, [activeType]);

    useEffect(() => {
        if (open) {
            fetchMedia(1, search, activeType);
            setSelectedId(null);
        }
    }, [open, fetchMedia, search, activeType]);

    const handleSearch = useCallback((value: string) => {
        setSearch(value);
        setCurrentPage(1);
    }, []);

    const handleSelect = useCallback(() => {
        const selected = media.find(m => m.id === selectedId);
        if (selected) {
            onSelect(selected);
            onOpenChange(false);
        }
    }, [media, selectedId, onSelect, onOpenChange]);

    const handleDelete = useCallback(async (id: number, e: React.MouseEvent) => {
        e.stopPropagation();
        
        if (!confirm('Möchtest du diese Datei wirklich löschen?')) return;

        try {
            const response = await fetch(`/media/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            if (!response.ok) throw new Error('Failed to delete');

            setMedia(prev => prev.filter(m => m.id !== id));
            if (selectedId === id) {
                setSelectedId(null);
            }
        } catch (error) {
            console.error('Failed to delete media:', error);
        }
    }, [selectedId]);

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl max-h-[85vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Mediathek</DialogTitle>
                </DialogHeader>

                {/* Filters */}
                <div className="flex items-center gap-4 pb-4 border-b">
                    <Input
                        placeholder="Suchen..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-xs"
                    />
                    
                    {type === 'all' && (
                        <div className="flex gap-1">
                            <Button
                                variant={activeType === 'all' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setActiveType('all')}
                            >
                                Alle
                            </Button>
                            <Button
                                variant={activeType === 'image' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setActiveType('image')}
                            >
                                Bilder
                            </Button>
                            <Button
                                variant={activeType === 'lottie' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setActiveType('lottie')}
                            >
                                Lottie
                            </Button>
                        </div>
                    )}
                </div>

                {/* Media Grid */}
                <div className="flex-1 overflow-y-auto min-h-0 py-4">
                    {loading ? (
                        <div className="grid grid-cols-4 sm:grid-cols-6 gap-3">
                            {Array.from({ length: 12 }).map((_, i) => (
                                <Skeleton key={i} className="aspect-square rounded-lg" />
                            ))}
                        </div>
                    ) : media.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-zinc-500">
                            <svg
                                className="w-12 h-12 mb-4 text-zinc-300"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={1.5}
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            <p className="text-sm">Keine Medien gefunden</p>
                            <p className="text-xs text-zinc-400 mt-1">
                                Lade Dateien über den Editor hoch
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-4 sm:grid-cols-6 gap-3">
                            {media.map((item) => (
                                <div
                                    key={item.id}
                                    className={cn(
                                        'group relative aspect-square rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
                                        selectedId === item.id
                                            ? 'border-zinc-900 ring-2 ring-zinc-900/20'
                                            : 'border-transparent hover:border-zinc-300'
                                    )}
                                    onClick={() => setSelectedId(item.id)}
                                    onDoubleClick={() => {
                                        setSelectedId(item.id);
                                        handleSelect();
                                    }}
                                >
                                    {item.type === 'image' ? (
                                        <img
                                            src={item.url}
                                            alt={item.alt || item.original_filename}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full bg-zinc-100 flex items-center justify-center">
                                            <svg
                                                className="w-8 h-8 text-zinc-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={1.5}
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                                />
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={1.5}
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                    )}
                                    
                                    {/* Delete button */}
                                    <button
                                        className="absolute top-1 right-1 p-1 rounded bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                        onClick={(e) => handleDelete(item.id, e)}
                                        title="Löschen"
                                    >
                                        <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    {/* Selection indicator */}
                                    {selectedId === item.id && (
                                        <div className="absolute top-1 left-1 w-5 h-5 rounded-full bg-zinc-900 text-white flex items-center justify-center">
                                            <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Selected item info & actions */}
                <div className="flex items-center justify-between pt-4 border-t">
                    <div className="text-sm text-zinc-500">
                        {selectedId && media.find(m => m.id === selectedId) && (
                            <>
                                <span className="font-medium text-zinc-700">
                                    {media.find(m => m.id === selectedId)?.original_filename}
                                </span>
                                <span className="mx-2">•</span>
                                <span>
                                    {formatFileSize(media.find(m => m.id === selectedId)?.size || 0)}
                                </span>
                                {media.find(m => m.id === selectedId)?.width && (
                                    <>
                                        <span className="mx-2">•</span>
                                        <span>
                                            {media.find(m => m.id === selectedId)?.width} × {media.find(m => m.id === selectedId)?.height}
                                        </span>
                                    </>
                                )}
                            </>
                        )}
                    </div>
                    
                    <div className="flex gap-2">
                        {totalPages > 1 && (
                            <div className="flex items-center gap-1 mr-4">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={currentPage === 1}
                                    onClick={() => fetchMedia(currentPage - 1, search, activeType)}
                                >
                                    ←
                                </Button>
                                <span className="text-sm text-zinc-500 px-2">
                                    {currentPage} / {totalPages}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={currentPage === totalPages}
                                    onClick={() => fetchMedia(currentPage + 1, search, activeType)}
                                >
                                    →
                                </Button>
                            </div>
                        )}
                        
                        <Button variant="outline" onClick={() => onOpenChange(false)}>
                            Abbrechen
                        </Button>
                        <Button
                            disabled={!selectedId}
                            onClick={handleSelect}
                        >
                            Auswählen
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

