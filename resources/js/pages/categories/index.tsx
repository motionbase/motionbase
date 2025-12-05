import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Layers, MoreHorizontal, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kategorien',
        href: '/categories',
    },
];

export default function Categories({ categories }: { categories: Category[] }) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editingName, setEditingName] = useState('');
    const [editingDescription, setEditingDescription] = useState('');

    const handleStartEdit = (category: Category) => {
        setEditingId(category.id);
        setEditingName(category.name);
        setEditingDescription(category.description ?? '');
    };

    const handleSaveEdit = (categoryId: number) => {
        router.put(
            `/categories/${categoryId}`,
            { name: editingName, description: editingDescription },
            { preserveScroll: true }
        );
        setEditingId(null);
    };

    const handleCancelEdit = () => {
        setEditingId(null);
        setEditingName('');
        setEditingDescription('');
    };

    const handleDelete = (categoryId: number) => {
        if (!confirm('Diese Kategorie wirklich löschen?')) {
            return;
        }
        router.delete(`/categories/${categoryId}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kategorien" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Header */}
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
                                    Kategorien
                                </h1>
                                <p className="mt-1 text-zinc-500">
                                    Organisiere deine Themen in übersichtliche Bereiche
                                </p>
                            </div>
                            <Button
                                className="h-10 gap-2 bg-zinc-900 px-5 text-white hover:bg-zinc-800"
                                asChild
                            >
                                <Link href="/categories/create">
                                    <Plus className="h-4 w-4" />
                                    Neue Kategorie
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        {categories.length === 0 ? (
                                    <div className="rounded-2xl border-2 border-dashed border-zinc-200 bg-white py-16 text-center">
                                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">
                                            <Layers className="h-8 w-8 text-zinc-400" />
                                        </div>
                                        <h3 className="mt-6 text-lg font-semibold text-zinc-900">
                                            Noch keine Kategorien
                                        </h3>
                                        <p className="mt-2 text-sm text-zinc-500">
                                            Erstelle deine erste Kategorie, um loszulegen.
                                        </p>
                                        <Button
                                            className="mt-6 bg-zinc-900 text-white hover:bg-zinc-800"
                                            asChild
                                        >
                                            <Link href="/categories/create">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Kategorie erstellen
                                            </Link>
                                        </Button>
                                    </div>
                        ) : (
                            <div className="grid gap-4">
                                {categories.map((category) => (
                                    <div
                                        key={category.id}
                                        className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm transition-all hover:border-zinc-200 hover:shadow-md"
                                    >
                                                {editingId === category.id ? (
                                                    <div className="space-y-3">
                                                        <Input
                                                            value={editingName}
                                                            onChange={(e) =>
                                                                setEditingName(e.target.value)
                                                            }
                                                            className="h-9"
                                                            autoFocus
                                                        />
                                                        <Textarea
                                                            value={editingDescription}
                                                            onChange={(e) =>
                                                                setEditingDescription(e.target.value)
                                                            }
                                                            placeholder="Beschreibung..."
                                                            rows={2}
                                                        />
                                                        <div className="flex gap-2">
                                                            <Button
                                                                size="sm"
                                                                onClick={() =>
                                                                    handleSaveEdit(category.id)
                                                                }
                                                                className="h-8 gap-1 bg-zinc-900 text-white hover:bg-zinc-800"
                                                            >
                                                                <Check className="h-4 w-4" />
                                                                Speichern
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={handleCancelEdit}
                                                                className="h-8 gap-1"
                                                            >
                                                                <X className="h-4 w-4" />
                                                                Abbrechen
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div className="flex items-start gap-3">
                                                            <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100">
                                                                <Layers className="h-4 w-4 text-zinc-600" />
                                                            </div>
                                                            <div>
                                                                <p className="font-medium text-zinc-900">
                                                                    {category.name}
                                                                </p>
                                                                {category.description && (
                                                                    <p className="mt-0.5 text-sm text-zinc-500">
                                                                        {category.description}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <Badge
                                                                variant="secondary"
                                                                className="bg-zinc-100 text-xs font-medium text-zinc-600"
                                                            >
                                                                {category.my_topics_count ?? 0} Themen
                                                            </Badge>
                                                            <DropdownMenu>
                                                                <DropdownMenuTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-8 w-8 text-zinc-400 hover:text-zinc-600"
                                                                    >
                                                                        <MoreHorizontal className="h-4 w-4" />
                                                                    </Button>
                                                                </DropdownMenuTrigger>
                                                                <DropdownMenuContent
                                                                    align="end"
                                                                    className="w-40"
                                                                >
                                                                    <DropdownMenuItem
                                                                        onClick={() =>
                                                                            handleStartEdit(category)
                                                                        }
                                                                    >
                                                                        <Pencil className="mr-2 h-4 w-4" />
                                                                        Bearbeiten
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        className="text-red-600 focus:text-red-600"
                                                                        onClick={() =>
                                                                            handleDelete(category.id)
                                                                        }
                                                                    >
                                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                                        Löschen
                                                                    </DropdownMenuItem>
                                                                </DropdownMenuContent>
                                                            </DropdownMenu>
                                                        </div>
                                                    </div>
                                                )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
