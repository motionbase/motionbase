import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Layers, Sparkles } from 'lucide-react';
import { type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Themen', href: '/topics' },
    { title: 'Neues Thema', href: '/topics/create' },
];

interface TopicsCreateProps {
    categories: Pick<Category, 'id' | 'name'>[];
}

type TopicFormData = {
    title: string;
    category_id: string;
};

export default function TopicsCreate({ categories }: TopicsCreateProps) {
    const form = useForm<TopicFormData>({
        title: '',
        category_id: categories[0]?.id?.toString() ?? '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/topics');
    };

    if (categories.length === 0) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Neues Thema" />
                <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                    <div className="flex min-h-[60vh] flex-col items-center justify-center px-6 py-16">
                        <div className="mx-auto max-w-md text-center">
                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                                <Layers className="h-8 w-8 text-amber-600" />
                            </div>
                            <h1 className="mt-6 text-2xl font-bold text-zinc-900">
                                Kategorien erforderlich
                            </h1>
                            <p className="mt-3 text-zinc-500">
                                Lege zuerst mindestens eine Kategorie an, um Themen zu erstellen und zu organisieren.
                            </p>
                            <Button
                                className="mt-8 h-11 gap-2 bg-zinc-900 px-6 text-white hover:bg-zinc-800"
                                asChild
                            >
                                <Link href="/categories">
                                    <Layers className="h-4 w-4" />
                                    Kategorien verwalten
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neues Thema" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Header */}
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-2xl">
                        <Link
                            href="/topics"
                            className="inline-flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Zurück zu Themen
                        </Link>
                        <div className="mt-4 flex items-center gap-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-900">
                                <Sparkles className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-zinc-900">
                                    Neues Thema erstellen
                                </h1>
                                <p className="text-zinc-500">
                                    Starte mit einem Titel und einer Kategorie
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
                                            htmlFor="title"
                                            className="text-sm font-medium text-zinc-700"
                                        >
                                            Titel
                                        </Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            value={form.data.title}
                                            onChange={(event) =>
                                                form.setData('title', event.target.value)
                                            }
                                            placeholder="z. B. Einführung in React"
                                            className="h-11"
                                            autoFocus
                                        />
                                        <InputError message={form.errors.title} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium text-zinc-700">
                                            Kategorie
                                        </Label>
                                        <Select
                                            value={form.data.category_id}
                                            onValueChange={(value) =>
                                                form.setData('category_id', value)
                                            }
                                        >
                                            <SelectTrigger className="h-11">
                                                <SelectValue placeholder="Kategorie auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((category) => (
                                                    <SelectItem
                                                        key={category.id}
                                                        value={String(category.id)}
                                                    >
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.category_id} />
                                    </div>
                                </div>

                                <div className="mt-8 flex items-center justify-between border-t border-zinc-100 pt-6">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="text-zinc-500 hover:text-zinc-900"
                                        asChild
                                    >
                                        <Link href="/topics">Abbrechen</Link>
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="h-10 gap-2 bg-zinc-900 px-6 text-white hover:bg-zinc-800"
                                    >
                                        <BookOpen className="h-4 w-4" />
                                        {form.processing ? 'Erstelle...' : 'Thema erstellen'}
                                    </Button>
                                </div>
                            </div>
                        </form>

                        {/* Info Card */}
                        <div className="mt-6 rounded-xl border border-zinc-100 bg-white p-6">
                            <h3 className="text-sm font-semibold text-zinc-900">
                                Was passiert als Nächstes?
                            </h3>
                            <ul className="mt-3 space-y-2 text-sm text-zinc-500">
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">
                                        1
                                    </span>
                                    Nach dem Erstellen wirst du zum Editor weitergeleitet
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">
                                        2
                                    </span>
                                    Ein erstes Kapitel mit einem Abschnitt wird automatisch angelegt
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">
                                        3
                                    </span>
                                    Füge weitere Kapitel und Abschnitte nach Bedarf hinzu
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
