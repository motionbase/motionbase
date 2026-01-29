import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Benutzer',
        href: '/admin/users',
    },
    {
        title: 'Neu',
        href: '/admin/users/create',
    },
];

export default function CreateUser() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        is_admin: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/users');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neuer Benutzer" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-3xl">
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" size="icon" asChild>
                                <Link href="/admin/users">
                                    <ArrowLeft className="h-5 w-5" />
                                </Link>
                            </Button>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
                                    Neuer Benutzer
                                </h1>
                                <p className="mt-1 text-zinc-500">
                                    Erstelle einen neuen Benutzer
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-3xl">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-4 rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        required
                                    />
                                    {form.errors.name && (
                                        <p className="text-sm text-red-600">{form.errors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">E-Mail</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        required
                                    />
                                    {form.errors.email && (
                                        <p className="text-sm text-red-600">{form.errors.email}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">Passwort</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                        required
                                    />
                                    {form.errors.password && (
                                        <p className="text-sm text-red-600">{form.errors.password}</p>
                                    )}
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_admin"
                                        checked={form.data.is_admin}
                                        onCheckedChange={(checked) =>
                                            form.setData('is_admin', checked === true)
                                        }
                                    />
                                    <Label htmlFor="is_admin" className="cursor-pointer">
                                        Administrator-Rechte
                                    </Label>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/admin/users">Abbrechen</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="bg-zinc-900 text-white hover:bg-zinc-800"
                                >
                                    {form.processing ? 'Erstellen...' : 'Benutzer erstellen'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
