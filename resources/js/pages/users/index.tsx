import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, Check, MoreHorizontal, Pencil, Plus, Shield, Trash2, UserCircle, X } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Benutzer',
        href: '/admin/users',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    topics_count: number;
    email_verified_at: string | null;
    created_at: string;
}

export default function UsersIndex({ users }: { users: User[] }) {
    const handleDelete = (userId: number) => {
        if (!confirm('Diesen Benutzer wirklich löschen?')) {
            return;
        }
        router.delete(`/admin/users/${userId}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Benutzer" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Header */}
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
                                    Benutzerverwaltung
                                </h1>
                                <p className="mt-1 text-zinc-500">
                                    Verwalte Benutzer und ihre Zugriffsrechte
                                </p>
                            </div>
                            <Button
                                className="h-10 gap-2 bg-zinc-900 px-5 text-white hover:bg-zinc-800"
                                asChild
                            >
                                <Link href="/admin/users/create">
                                    <Plus className="h-4 w-4" />
                                    Neuer Benutzer
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        {users.length === 0 ? (
                            <div className="rounded-2xl border-2 border-dashed border-zinc-200 bg-white py-16 text-center">
                                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">
                                    <UserCircle className="h-8 w-8 text-zinc-400" />
                                </div>
                                <h3 className="mt-6 text-lg font-semibold text-zinc-900">
                                    Noch keine Benutzer
                                </h3>
                                <p className="mt-2 text-sm text-zinc-500">
                                    Erstelle den ersten Benutzer, um loszulegen.
                                </p>
                                <Button
                                    className="mt-6 bg-zinc-900 text-white hover:bg-zinc-800"
                                    asChild
                                >
                                    <Link href="/admin/users/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Benutzer erstellen
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="grid gap-4">
                                {users.map((user) => (
                                    <div
                                        key={user.id}
                                        className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm transition-all hover:border-zinc-200 hover:shadow-md"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex items-start gap-3">
                                                <div className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100">
                                                    <UserCircle className="h-5 w-5 text-zinc-600" />
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium text-zinc-900">
                                                            {user.name}
                                                        </p>
                                                        {user.is_admin && (
                                                            <Badge
                                                                variant="secondary"
                                                                className="gap-1 bg-amber-100 text-xs font-medium text-amber-700"
                                                            >
                                                                <Shield className="h-3 w-3" />
                                                                Admin
                                                            </Badge>
                                                        )}
                                                        {user.email_verified_at ? (
                                                            <Check className="h-4 w-4 text-green-600" title="E-Mail verifiziert" />
                                                        ) : (
                                                            <X className="h-4 w-4 text-zinc-400" title="E-Mail nicht verifiziert" />
                                                        )}
                                                    </div>
                                                    <p className="mt-0.5 text-sm text-zinc-500">
                                                        {user.email}
                                                    </p>
                                                    <div className="mt-2 flex items-center gap-1 text-xs text-zinc-400">
                                                        <BookOpen className="h-3.5 w-3.5" />
                                                        {user.topics_count} Themen
                                                    </div>
                                                </div>
                                            </div>
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
                                                <DropdownMenuContent align="end" className="w-40">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/users/${user.id}/edit`}>
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Bearbeiten
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        className="text-red-600 focus:text-red-600"
                                                        onClick={() => handleDelete(user.id)}
                                                    >
                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                        Löschen
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
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
