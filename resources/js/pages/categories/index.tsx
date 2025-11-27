import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Layers, MoreHorizontal, Pencil, Plus, Trash2, X } from 'lucide-react';
import { type FormEvent, useState } from 'react';

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

    const form = useForm({
        name: '',
        description: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

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
                    <div className="mx-auto max-w-5xl">
                        <div className="flex items-center gap-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-900">
                                <Layers className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-zinc-900">
                                    Kategorien
                                </h1>
                                <p className="text-zinc-500">
                                    Organisiere deine Themen in übersichtliche Bereiche
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="px-6 py-10 lg:px-10">
                    <div className="mx-auto max-w-5xl">
                        <div className="grid gap-8 lg:grid-cols-[380px_1fr]">
                            {/* Create Form */}
                            <div className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm h-fit">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100">
                                        <Plus className="h-5 w-5 text-zinc-600" />
                                    </div>
                                    <div>
                                        <h2 className="font-semibold text-zinc-900">
                                            Neue Kategorie
                                        </h2>
                                        <p className="text-sm text-zinc-500">
                                            Erstelle einen neuen Bereich
                                        </p>
                                    </div>
                                </div>

                                <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="name"
                                            className="text-sm font-medium text-zinc-700"
                                        >
                                            Name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={form.data.name}
                                            onChange={(event) =>
                                                form.setData('name', event.target.value)
                                            }
                                            placeholder="z. B. Frontend"
                                            className="h-10"
                                        />
                                        <InputError message={form.errors.name} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="description"
                                            className="text-sm font-medium text-zinc-700"
                                        >
                                            Beschreibung
                                            <span className="ml-1 text-zinc-400">(optional)</span>
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            value={form.data.description}
                                            onChange={(event) =>
                                                form.setData('description', event.target.value)
                                            }
                                            placeholder="Kurze Beschreibung..."
                                            rows={3}
                                        />
                                        <InputError message={form.errors.description} />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="w-full h-10 bg-zinc-900 text-white hover:bg-zinc-800"
                                    >
                                        {form.processing ? 'Speichere...' : 'Kategorie erstellen'}
                                    </Button>
                                </form>
                            </div>

                            {/* Categories List */}
                            <div className="rounded-2xl border border-zinc-100 bg-white shadow-sm">
                                <div className="border-b border-zinc-100 px-6 py-4">
                                    <h2 className="font-semibold text-zinc-900">
                                        Deine Kategorien
                                    </h2>
                                    <p className="text-sm text-zinc-500">
                                        {categories.length} {categories.length === 1 ? 'Kategorie' : 'Kategorien'} vorhanden
                                    </p>
                                </div>

                                {categories.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-16 text-center">
                                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">
                                            <Layers className="h-8 w-8 text-zinc-400" />
                                        </div>
                                        <h3 className="mt-4 font-medium text-zinc-900">
                                            Noch keine Kategorien
                                        </h3>
                                        <p className="mt-1 text-sm text-zinc-500">
                                            Erstelle deine erste Kategorie, um loszulegen.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="divide-y divide-zinc-50">
                                        {categories.map((category) => (
                                            <div
                                                key={category.id}
                                                className="px-6 py-4 transition-colors hover:bg-zinc-50/50"
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
                </div>
            </div>
        </AppLayout>
    );
}
