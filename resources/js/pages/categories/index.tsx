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
            <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Neue Kategorie</CardTitle>
                        <CardDescription>Strukturiere deine Themen mit Kategorien.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-4" onSubmit={handleSubmit}>
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    autoComplete="off"
                                    placeholder="z. B. Marketing"
                                    autoFocus
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Beschreibung</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    value={form.data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    placeholder="Optional – hilf deinem Team, den Zweck zu verstehen."
                                />
                                <InputError message={form.errors.description} />
                            </div>

                            <Button className="w-full" disabled={form.processing}>
                                Kategorie speichern
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Deine Kategorien</CardTitle>
                        <CardDescription>
                            Fasse Themen nach Bereichen zusammen und behalte den Überblick.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {categories.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                Noch keine Kategorien vorhanden. Lege deine erste Kategorie an, um zu starten.
                            </div>
                        ) : (
                            <ul className="space-y-3">
                                {categories.map((category) => (
                                    <li
                                        key={category.id}
                                        className="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p className="font-medium">{category.name}</p>
                                            {category.description && (
                                                <p className="text-sm text-muted-foreground">{category.description}</p>
                                            )}
                                        </div>
                                        <Badge variant="secondary" className="w-fit">
                                            {category.my_topics_count ?? 0} Themen
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

