import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kategorien',
        href: '/categories',
    },
];

export default function Categories({ categories }: { categories: Category[] }) {
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kategorien" />

            <div className="space-y-8 px-6 py-10">
                <div className="space-y-3">
                    <p className="text-xs font-semibold uppercase tracking-[0.35em] text-[#ff0055]">Kategorien</p>
                    <h1 className="text-4xl font-semibold tracking-tight text-zinc-900">Bereiche strukturieren</h1>
                    <p className="text-base text-zinc-500 max-w-2xl">
                        Ordne deine Themen klar zu – so finden Lernende schneller das, wonach sie suchen.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <Card className="h-fit border-zinc-100 bg-white shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-zinc-900">Neue Kategorie</CardTitle>
                            <CardDescription>Strukturiere deine Themen mit wenigen Klicks.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-4" onSubmit={handleSubmit}>
                                <div className="space-y-2">
                                    <Label htmlFor="name" className="text-sm font-medium text-zinc-700">
                                        Name
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(event) => form.setData('name', event.target.value)}
                                        autoComplete="off"
                                        placeholder="z. B. Animation"
                                        autoFocus
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description" className="text-sm font-medium text-zinc-700">
                                        Beschreibung
                                    </Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        value={form.data.description}
                                        onChange={(event) => form.setData('description', event.target.value)}
                                        placeholder="Optional – helfe deinem Team beim Einordnen."
                                    />
                                    <InputError message={form.errors.description} />
                                </div>

                                <Button
                                    className="w-full bg-[#ff0055] text-white hover:bg-[#ff0055]/90"
                                    disabled={form.processing}
                                >
                                    Kategorie speichern
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="border-zinc-100 bg-white shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-zinc-900">Deine Kategorien</CardTitle>
                            <CardDescription>Fasse Themen nach Bereichen zusammen und behalte den Überblick.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {categories.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-zinc-200 p-8 text-center text-sm text-zinc-500">
                                    Noch keine Kategorien vorhanden. Lege deine erste Kategorie an, um zu starten.
                                </div>
                            ) : (
                                <ul className="space-y-3">
                                    {categories.map((category) => (
                                        <li
                                            key={category.id}
                                            className="flex flex-col gap-3 rounded-2xl border border-zinc-100 px-5 py-4 transition hover:border-zinc-200 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p className="font-medium text-zinc-900">{category.name}</p>
                                                {category.description && (
                                                    <p className="text-sm text-zinc-500">{category.description}</p>
                                                )}
                                            </div>
                                            <Badge variant="outline" className="border-zinc-200 bg-zinc-50 text-xs text-zinc-600">
                                                {category.my_topics_count ?? 0} Themen
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

