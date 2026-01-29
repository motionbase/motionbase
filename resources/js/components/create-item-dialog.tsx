import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

interface CreateItemDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    type: 'chapter' | 'section';
    topicId?: number;
    chapterId?: number;
}

export function CreateItemDialog({ open, onOpenChange, type, topicId, chapterId }: CreateItemDialogProps) {
    const [title, setTitle] = useState('');
    const [slug, setSlug] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [autoSlug, setAutoSlug] = useState(true);

    // Auto-generate slug from title
    useEffect(() => {
        if (autoSlug && title) {
            const generatedSlug = title
                .toLowerCase()
                .replace(/[äöüß]/g, (match) => {
                    const map: Record<string, string> = { 'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss' };
                    return map[match] || match;
                })
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            setSlug(generatedSlug);
        }
    }, [title, autoSlug]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!title.trim()) return;

        setIsSubmitting(true);

        if (type === 'chapter' && topicId) {
            router.post(`/admin/topics/${topicId}/chapters`, {
                title: title.trim(),
                slug: slug.trim() || undefined,
            }, {
                onSuccess: () => {
                    setTitle('');
                    setSlug('');
                    setAutoSlug(true);
                    onOpenChange(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            });
        } else if (type === 'section' && chapterId) {
            router.post(`/admin/chapters/${chapterId}/sections`, {
                title: title.trim(),
                slug: slug.trim() || undefined,
            }, {
                onSuccess: () => {
                    setTitle('');
                    setSlug('');
                    setAutoSlug(true);
                    onOpenChange(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            });
        }
    };

    const handleCancel = () => {
        setTitle('');
        setSlug('');
        setAutoSlug(true);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {type === 'chapter' ? 'Neues Kapitel' : 'Neue Seite'}
                        </DialogTitle>
                        <DialogDescription>
                            {type === 'chapter'
                                ? 'Gib einen Titel für das neue Kapitel ein.'
                                : 'Gib einen Titel für die neue Seite ein.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="title">Titel</Label>
                            <Input
                                id="title"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder={type === 'chapter' ? 'z.B. Einführung' : 'z.B. Grundlagen'}
                                autoFocus
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={slug}
                                onChange={(e) => {
                                    setSlug(e.target.value);
                                    setAutoSlug(false);
                                }}
                                placeholder="automatisch generiert"
                                className="font-mono text-sm"
                            />
                            <p className="text-xs text-muted-foreground">
                                Wird automatisch aus dem Titel generiert. Du kannst es manuell ändern.
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCancel}
                            disabled={isSubmitting}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={!title.trim() || isSubmitting}>
                            {isSubmitting ? 'Erstellen...' : 'Erstellen'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
