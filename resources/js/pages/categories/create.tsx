import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Layers, Sparkles } from 'lucide-react';
import { type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kategorien', href: '/admin/categories' },
    { title: 'Neue Kategorie', href: '/admin/categories/create' },
];

type CategoryFormData = {
    name: string;
    description: string;
};

export default function CategoriesCreate() {
    const form = useForm<CategoryFormData>({
        name: '',
        description: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/admin/categories');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neue Kategorie" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Header */}
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-2xl">
                        <Link
                            href="/admin/categories"
                            className="inline-flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Zurück zu Kategorien
                        </Link>
                        <div className="mt-4 flex items-center gap-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-900">
                                <Sparkles className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-zinc-900">
                                    Neue Kategorie erstellen
                                </h1>
                                <p className="text-zinc-500">
                                    Organisiere deine Themen in übersichtliche Bereiche
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <div className="px-6 py-10 lg:px-10">
                    <div className="mx-auto max-w-2xl">
                        <form onSubmit={handleSubmit}>
                            <div className="rounded-2xl border border-zinc-100 bg-white p-8 shadow-sm">
                                <div className="space-y-6">
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
                                            className="h-11"
                                            autoFocus
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
                                            rows={4}
                                        />
                                        <InputError message={form.errors.description} />
                                    </div>
                                </div>

                                <div className="mt-8 flex items-center justify-between border-t border-zinc-100 pt-6">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="text-zinc-500 hover:text-zinc-900"
                                        asChild
                                    >
                                        <Link href="/admin/categories">Abbrechen</Link>
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="h-10 gap-2 bg-zinc-900 px-6 text-white hover:bg-zinc-800"
                                    >
                                        <Layers className="h-4 w-4" />
                                        {form.processing ? 'Erstelle...' : 'Kategorie erstellen'}
                                    </Button>
                                </div>
                            </div>
                        </form>

                        {/* Info Card */}
                        <div className="mt-6 rounded-xl border border-zinc-100 bg-white p-6">
                            <h3 className="text-sm font-semibold text-zinc-900">
                                Was sind Kategorien?
                            </h3>
                            <ul className="mt-3 space-y-2 text-sm text-zinc-500">
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">
                                        1
                                    </span>
                                    Kategorien helfen dir, deine Themen zu organisieren
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">
                                        2
                                    </span>
                                    Du kannst Themen nach Kategorien filtern
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">
                                        3
                                    </span>
                                    Jedes Thema kann einer Kategorie zugeordnet werden
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}





