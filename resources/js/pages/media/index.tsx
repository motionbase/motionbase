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

            const response = await fetch(`/admin/media?${params.toString()}`, {
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
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/admin/media/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Failed to delete' }));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.success) {
                setMedia(prev => prev.filter(m => m.id !== id));
                if (meta) {
                    setMeta(prev => prev ? { ...prev, total: prev.total - 1 } : null);
                }
                if (selectedMedia?.id === id) {
                    setDetailsOpen(false);
                    setSelectedMedia(null);
                }
            }
        } catch (error) {
            console.error('Failed to delete media:', error);
            alert('Fehler beim Löschen: ' + (error instanceof Error ? error.message : 'Unbekannter Fehler'));
        }
    }, [selectedMedia, meta]);

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
        <AppLayout breadcrumbs={[{ title: 'Medien', href: '/admin/media' }]}>
            <Head title="Medien" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Header */}
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
                                    Medien
                                </h1>
                                <p className="mt-1 text-zinc-500">
                                    Alle hochgeladenen Bilder und Dateien
                                </p>
                            </div>
                        </div>

                        {/* Search & Filter Bar */}
                        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                                <Input
                                    type="text"
                                    placeholder="Medien durchsuchen..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 border-zinc-200 bg-zinc-50 pl-10 focus:bg-white"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant={activeType === 'all' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setActiveType('all')}
                                    className="h-10 border-zinc-200"
                                >
                                    Alle
                                </Button>
                                <Button
                                    variant={activeType === 'image' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setActiveType('image')}
                                    className="h-10 gap-1.5 border-zinc-200"
                                >
                                    <Image className="h-4 w-4" />
                                    Bilder
                                </Button>
                                <Button
                                    variant={activeType === 'lottie' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setActiveType('lottie')}
                                    className="h-10 gap-1.5 border-zinc-200"
                                >
                                    <Film className="h-4 w-4" />
                                    Lottie
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        {/* Media Grid */}
                {loading ? (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        {Array.from({ length: 16 }).map((_, i) => (
                            <Skeleton key={i} className="aspect-square rounded-lg" />
                        ))}
                    </div>
                ) : media.length === 0 ? (
                    <div className="rounded-2xl border-2 border-dashed border-zinc-200 bg-white py-16 text-center">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">
                            <Image className="h-8 w-8 text-zinc-400" />
                        </div>
                        <h3 className="mt-6 text-lg font-semibold text-zinc-900">
                            {search || activeType !== 'all' ? 'Keine Medien gefunden' : 'Noch keine Medien'}
                        </h3>
                        <p className="mt-2 text-sm text-zinc-500">
                            {search || activeType !== 'all'
                                ? 'Versuche andere Suchbegriffe oder Filter.'
                                : 'Lade Dateien über den Editor hoch, um sie hier zu sehen.'}
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
                </div>
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

