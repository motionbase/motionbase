import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/editor/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { OutputData } from '@editorjs/editorjs';
import { type FormEvent, useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Themen', href: '/topics' },
    { title: 'Neues Thema', href: '/topics/create' },
];

interface TopicsCreateProps {
    categories: Pick<Category, 'id' | 'name'>[];
}

type EditorContent = {
    time: number;
    blocks: Array<Record<string, unknown>>;
    version: string;
};

type TopicFormData = {
    title: string;
    category_id: string;
    content: EditorContent;
};

export default function TopicsCreate({ categories }: TopicsCreateProps) {
    const defaultContent = useMemo(
        (): EditorContent => ({
            time: Date.now(),
            blocks: [
                {
                    type: 'paragraph',
                    data: {
                        text: 'Starte mit einer Einordnung oder einer Leitfrage…',
                    },
                },
            ],
            version: '2.31.0',
        }),
        [],
    );

    const form = useForm<TopicFormData>({
        title: '',
        category_id: categories[0]?.id?.toString() ?? '',
        content: defaultContent,
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/topics', {
            onSuccess: () => form.reset('title', 'category_id', 'content'),
        });
    };

    if (categories.length === 0) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Neues Thema" />
                <Card>
                    <CardHeader>
                        <CardTitle>Kategorien erforderlich</CardTitle>
                        <CardDescription>Lege zunächst mindestens eine Kategorie an.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button asChild>
                            <Link href="/categories">Zu den Kategorien</Link>
                        </Button>
                    </CardContent>
                </Card>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neues Thema" />
            <Card>
                <CardHeader>
                    <CardTitle>Neues Thema anlegen</CardTitle>
                    <CardDescription>
                        Formuliere dein Thema mit Editor.js und verknüpfe es mit einer Kategorie.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <form className="space-y-6" onSubmit={handleSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="title">Titel</Label>
                            <Input
                                id="title"
                                name="title"
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                                placeholder="z. B. Kampagnenplanung Q1"
                            />
                            <InputError message={form.errors.title} />
                        </div>

                        <div className="space-y-2">
                            <Label>Kategorie</Label>
                            <Select
                                value={form.data.category_id}
                                onValueChange={(value) => form.setData('category_id', value)}
                            >
                                <SelectTrigger aria-label="Kategorie auswählen" aria-invalid={!!form.errors.category_id}>
                                    <SelectValue placeholder="Kategorie auswählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.category_id} />
                        </div>

                        <div className="space-y-3">
                            <Label>Inhalt</Label>
                            <RichTextEditor
                                initialValue={form.data.content as OutputData}
                                onChange={(data) => form.setData('content', data as EditorContent)}
                            />
                            <InputError message={form.errors.content} />
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Button disabled={form.processing}>Speichern</Button>
                            <Button type="button" variant="ghost" asChild>
                                <Link href="/topics">Abbrechen</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}

