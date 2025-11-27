import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head } from '@inertiajs/react';
import { Image, Film, Search, Trash2, Download, Copy, Check } from 'lucide-react';
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

interface MediaMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function MediaIndex() {
    const [media, setMedia] = useState<MediaItem[]>([]);
    const [meta, setMeta] = useState<MediaMeta | null>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [activeType, setActiveType] = useState<'all' | 'image' | 'lottie'>('all');
    const [selectedMedia, setSelectedMedia] = useState<MediaItem | null>(null);
    const [detailsOpen, setDetailsOpen] = useState(false);
    const [copied, setCopied] = useState(false);

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
            setMeta(data.meta);
        } catch (error) {
            console.error('Failed to fetch media:', error);
        } finally {
            setLoading(false);
        }
    }, [activeType]);

    useEffect(() => {
        fetchMedia(1, search, activeType);
    }, [fetchMedia, search, activeType]);

    const handleDelete = useCallback(async (id: number) => {
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
            if (selectedMedia?.id === id) {
                setDetailsOpen(false);
                setSelectedMedia(null);
            }
        } catch (error) {
            console.error('Failed to delete media:', error);
        }
    }, [selectedMedia]);

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const copyToClipboard = async (text: string) => {
        await navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const openDetails = (item: MediaItem) => {
        setSelectedMedia(item);
        setDetailsOpen(true);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Medien', href: '/media' }]}>
            <Head title="Medien" />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-zinc-900">Mediathek</h1>
                    <p className="mt-1 text-sm text-zinc-500">
                        Alle hochgeladenen Bilder und Dateien
                    </p>
                </div>

                {/* Filters */}
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative max-w-xs flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                        <Input
                            placeholder="Suchen..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                    
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
                            className="gap-1.5"
                        >
                            <Image className="h-4 w-4" />
                            Bilder
                        </Button>
                        <Button
                            variant={activeType === 'lottie' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setActiveType('lottie')}
                            className="gap-1.5"
                        >
                            <Film className="h-4 w-4" />
                            Lottie
                        </Button>
                    </div>
                </div>

                {/* Stats */}
                {meta && (
                    <div className="mb-6 text-sm text-zinc-500">
                        {meta.total} {meta.total === 1 ? 'Datei' : 'Dateien'} gefunden
                    </div>
                )}

                {/* Media Grid */}
                {loading ? (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        {Array.from({ length: 16 }).map((_, i) => (
                            <Skeleton key={i} className="aspect-square rounded-lg" />
                        ))}
                    </div>
                ) : media.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 py-16">
                        <div className="mb-4 rounded-full bg-zinc-100 p-4">
                            <Image className="h-8 w-8 text-zinc-400" />
                        </div>
                        <h3 className="text-lg font-medium text-zinc-900">Keine Medien gefunden</h3>
                        <p className="mt-1 text-sm text-zinc-500">
                            Lade Dateien über den Editor hoch, um sie hier zu sehen.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        {media.map((item) => (
                            <div
                                key={item.id}
                                className="group relative aspect-square cursor-pointer overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 transition-all hover:border-zinc-300 hover:shadow-md"
                                onClick={() => openDetails(item)}
                            >
                                {item.type === 'image' ? (
                                    <img
                                        src={item.url}
                                        alt={item.alt || item.original_filename}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center">
                                        <Film className="h-8 w-8 text-zinc-400" />
                                    </div>
                                )}
                                
                                {/* Overlay */}
                                <div className="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                                    <div className="w-full p-2">
                                        <p className="truncate text-xs font-medium text-white">
                                            {item.original_filename}
                                        </p>
                                        <p className="text-xs text-white/70">
                                            {formatFileSize(item.size)}
                                        </p>
                                    </div>
                                </div>

                                {/* Delete button */}
                                <button
                                    className="absolute right-1 top-1 rounded-md bg-red-500 p-1.5 text-white opacity-0 transition-opacity hover:bg-red-600 group-hover:opacity-100"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleDelete(item.id);
                                    }}
                                    title="Löschen"
                                >
                                    <Trash2 className="h-3 w-3" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {meta && meta.last_page > 1 && (
                    <div className="mt-8 flex items-center justify-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={meta.current_page === 1}
                            onClick={() => fetchMedia(meta.current_page - 1, search, activeType)}
                        >
                            Zurück
                        </Button>
                        <span className="px-4 text-sm text-zinc-500">
                            Seite {meta.current_page} von {meta.last_page}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={meta.current_page === meta.last_page}
                            onClick={() => fetchMedia(meta.current_page + 1, search, activeType)}
                        >
                            Weiter
                        </Button>
                    </div>
                )}
            </div>

            {/* Details Dialog */}
            <Dialog open={detailsOpen} onOpenChange={setDetailsOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Mediendetails</DialogTitle>
                    </DialogHeader>
                    
                    {selectedMedia && (
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Preview */}
                            <div className="flex items-center justify-center rounded-lg bg-zinc-100 p-4">
                                {selectedMedia.type === 'image' ? (
                                    <img
                                        src={selectedMedia.url}
                                        alt={selectedMedia.alt || selectedMedia.original_filename}
                                        className="max-h-64 rounded object-contain"
                                    />
                                ) : (
                                    <div className="flex h-32 w-32 items-center justify-center">
                                        <Film className="h-16 w-16 text-zinc-400" />
                                    </div>
                                )}
                            </div>

                            {/* Info */}
                            <div className="space-y-4">
                                <div>
                                    <label className="text-xs font-medium uppercase text-zinc-500">
                                        Dateiname
                                    </label>
                                    <p className="mt-1 text-sm font-medium text-zinc-900">
                                        {selectedMedia.original_filename}
                                    </p>
                                </div>

                                <div>
                                    <label className="text-xs font-medium uppercase text-zinc-500">
                                        Typ
                                    </label>
                                    <p className="mt-1 text-sm text-zinc-900">
                                        {selectedMedia.mime_type}
                                    </p>
                                </div>

                                <div>
                                    <label className="text-xs font-medium uppercase text-zinc-500">
                                        Größe
                                    </label>
                                    <p className="mt-1 text-sm text-zinc-900">
                                        {formatFileSize(selectedMedia.size)}
                                    </p>
                                </div>

                                {selectedMedia.width && selectedMedia.height && (
                                    <div>
                                        <label className="text-xs font-medium uppercase text-zinc-500">
                                            Abmessungen
                                        </label>
                                        <p className="mt-1 text-sm text-zinc-900">
                                            {selectedMedia.width} × {selectedMedia.height} px
                                        </p>
                                    </div>
                                )}

                                <div>
                                    <label className="text-xs font-medium uppercase text-zinc-500">
                                        Hochgeladen
                                    </label>
                                    <p className="mt-1 text-sm text-zinc-900">
                                        {formatDate(selectedMedia.created_at)}
                                    </p>
                                </div>

                                <div>
                                    <label className="text-xs font-medium uppercase text-zinc-500">
                                        URL
                                    </label>
                                    <div className="mt-1 flex gap-2">
                                        <Input
                                            value={selectedMedia.url}
                                            readOnly
                                            className="text-xs"
                                        />
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            onClick={() => copyToClipboard(selectedMedia.url)}
                                            title="URL kopieren"
                                        >
                                            {copied ? (
                                                <Check className="h-4 w-4 text-green-500" />
                                            ) : (
                                                <Copy className="h-4 w-4" />
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => selectedMedia && window.open(selectedMedia.url, '_blank')}
                            className="gap-1.5"
                        >
                            <Download className="h-4 w-4" />
                            Herunterladen
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => selectedMedia && handleDelete(selectedMedia.id)}
                            className="gap-1.5"
                        >
                            <Trash2 className="h-4 w-4" />
                            Löschen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

