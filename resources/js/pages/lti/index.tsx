import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Copy, ExternalLink, MoreHorizontal, Pencil, Plus, Server, Trash2, X } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'LTI-Plattformen',
        href: '/admin/lti',
    },
];

interface LtiPlatform {
    id: number;
    name: string;
    issuer: string;
    client_id: string;
    deployment_id: string | null;
    auth_login_url: string;
    auth_token_url: string;
    jwks_url: string;
    is_active: boolean;
    created_at: string;
}

interface ToolConfig {
    name: string;
    description: string;
    target_link_uri: string;
    oidc_initiation_url: string;
    jwks_url: string;
}

interface Props {
    platforms: LtiPlatform[];
    toolConfig: ToolConfig;
}

export default function LtiIndex({ platforms, toolConfig }: Props) {
    const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
    const [editingPlatform, setEditingPlatform] = useState<LtiPlatform | null>(null);
    const [copiedField, setCopiedField] = useState<string | null>(null);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: '',
        issuer: '',
        client_id: '',
        deployment_id: '',
        auth_login_url: '',
        auth_token_url: '',
        jwks_url: '',
    });

    const handleCopy = (text: string, field: string) => {
        navigator.clipboard.writeText(text);
        setCopiedField(field);
        setTimeout(() => setCopiedField(null), 2000);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingPlatform) {
            patch(`/admin/lti/${editingPlatform.id}`, {
                onSuccess: () => {
                    setEditingPlatform(null);
                    reset();
                },
            });
        } else {
            post('/admin/lti', {
                onSuccess: () => {
                    setIsAddDialogOpen(false);
                    reset();
                },
            });
        }
    };

    const handleEdit = (platform: LtiPlatform) => {
        setEditingPlatform(platform);
        setData({
            name: platform.name,
            issuer: platform.issuer,
            client_id: platform.client_id,
            deployment_id: platform.deployment_id || '',
            auth_login_url: platform.auth_login_url,
            auth_token_url: platform.auth_token_url,
            jwks_url: platform.jwks_url,
        });
    };

    const handleDelete = (platformId: number) => {
        if (!confirm('Diese LTI-Plattform wirklich löschen?')) {
            return;
        }
        router.delete(`/admin/lti/${platformId}`, { preserveScroll: true });
    };

    const handleToggleActive = (platform: LtiPlatform) => {
        router.patch(`/admin/lti/${platform.id}`, {
            ...platform,
            is_active: !platform.is_active,
        }, { preserveScroll: true });
    };

    const PlatformForm = () => (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="z.B. Moodle Schule XY"
                />
                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="issuer">Plattform-ID (Issuer)</Label>
                <Input
                    id="issuer"
                    value={data.issuer}
                    onChange={(e) => setData('issuer', e.target.value)}
                    placeholder="https://moodle.example.com"
                />
                {errors.issuer && <p className="text-sm text-red-500">{errors.issuer}</p>}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="client_id">Client-ID</Label>
                    <Input
                        id="client_id"
                        value={data.client_id}
                        onChange={(e) => setData('client_id', e.target.value)}
                        placeholder="abc123..."
                    />
                    {errors.client_id && <p className="text-sm text-red-500">{errors.client_id}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="deployment_id">Deployment-ID (optional)</Label>
                    <Input
                        id="deployment_id"
                        value={data.deployment_id}
                        onChange={(e) => setData('deployment_id', e.target.value)}
                        placeholder="1"
                    />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="auth_login_url">Auth Login URL</Label>
                <Input
                    id="auth_login_url"
                    value={data.auth_login_url}
                    onChange={(e) => setData('auth_login_url', e.target.value)}
                    placeholder="https://moodle.example.com/mod/lti/auth.php"
                />
                {errors.auth_login_url && <p className="text-sm text-red-500">{errors.auth_login_url}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="auth_token_url">Token URL</Label>
                <Input
                    id="auth_token_url"
                    value={data.auth_token_url}
                    onChange={(e) => setData('auth_token_url', e.target.value)}
                    placeholder="https://moodle.example.com/mod/lti/token.php"
                />
                {errors.auth_token_url && <p className="text-sm text-red-500">{errors.auth_token_url}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="jwks_url">JWKS URL</Label>
                <Input
                    id="jwks_url"
                    value={data.jwks_url}
                    onChange={(e) => setData('jwks_url', e.target.value)}
                    placeholder="https://moodle.example.com/mod/lti/certs.php"
                />
                {errors.jwks_url && <p className="text-sm text-red-500">{errors.jwks_url}</p>}
            </div>

            <DialogFooter>
                <Button type="submit" disabled={processing} className="bg-zinc-900 text-white hover:bg-zinc-800">
                    {editingPlatform ? 'Speichern' : 'Hinzufuegen'}
                </Button>
            </DialogFooter>
        </form>
    );

    const ConfigField = ({ label, value, fieldKey }: { label: string; value: string; fieldKey: string }) => (
        <div className="flex items-center justify-between gap-4 rounded-lg bg-zinc-50 px-4 py-3">
            <div className="min-w-0 flex-1">
                <p className="text-xs font-medium text-zinc-500">{label}</p>
                <p className="truncate text-sm font-mono text-zinc-900">{value}</p>
            </div>
            <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 shrink-0"
                onClick={() => handleCopy(value, fieldKey)}
            >
                {copiedField === fieldKey ? (
                    <Check className="h-4 w-4 text-green-600" />
                ) : (
                    <Copy className="h-4 w-4 text-zinc-400" />
                )}
            </Button>
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="LTI-Plattformen" />

            <div className="min-h-[calc(100vh-80px)] bg-zinc-50/50">
                {/* Header */}
                <div className="border-b border-zinc-100 bg-white px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900">
                                    LTI 1.3 Integration
                                </h1>
                                <p className="mt-1 text-zinc-500">
                                    Verbinde MotionBase mit Moodle und anderen LMS-Plattformen
                                </p>
                            </div>
                            <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
                                <DialogTrigger asChild>
                                    <Button className="h-10 gap-2 bg-zinc-900 px-5 text-white hover:bg-zinc-800">
                                        <Plus className="h-4 w-4" />
                                        Plattform hinzufuegen
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="max-w-lg">
                                    <DialogHeader>
                                        <DialogTitle>LTI-Plattform hinzufuegen</DialogTitle>
                                        <DialogDescription>
                                            Trage die Daten ein, die du von deiner Moodle-Installation erhalten hast.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <PlatformForm />
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="px-6 py-8 lg:px-10">
                    <div className="mx-auto max-w-6xl space-y-8">
                        {/* Tool Configuration */}
                        <div className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm">
                            <div className="mb-4 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-900">
                                    <ExternalLink className="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <h2 className="font-semibold text-zinc-900">Tool-Konfiguration</h2>
                                    <p className="text-sm text-zinc-500">Diese Werte in Moodle eintragen</p>
                                </div>
                            </div>
                            <div className="grid gap-3">
                                <ConfigField label="Tool URL / Launch URL" value={toolConfig.target_link_uri} fieldKey="launch" />
                                <ConfigField label="Initiate Login URL" value={toolConfig.oidc_initiation_url} fieldKey="login" />
                                <ConfigField label="Public Keyset URL (JWKS)" value={toolConfig.jwks_url} fieldKey="jwks" />
                            </div>
                        </div>

                        {/* Platforms List */}
                        <div>
                            <h2 className="mb-4 text-lg font-semibold text-zinc-900">Verbundene Plattformen</h2>
                            {platforms.length === 0 ? (
                                <div className="rounded-2xl border-2 border-dashed border-zinc-200 bg-white py-16 text-center">
                                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100">
                                        <Server className="h-8 w-8 text-zinc-400" />
                                    </div>
                                    <h3 className="mt-6 text-lg font-semibold text-zinc-900">
                                        Noch keine Plattformen
                                    </h3>
                                    <p className="mx-auto mt-2 max-w-sm text-sm text-zinc-500">
                                        Konfiguriere zuerst MotionBase als externes Tool in Moodle,
                                        dann trage hier die erhaltenen Daten ein.
                                    </p>
                                </div>
                            ) : (
                                <div className="grid gap-4">
                                    {platforms.map((platform) => (
                                        <div
                                            key={platform.id}
                                            className="rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm transition-all hover:border-zinc-200 hover:shadow-md"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex items-start gap-3">
                                                    <div className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100">
                                                        <Server className="h-5 w-5 text-zinc-600" />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <p className="font-medium text-zinc-900">
                                                                {platform.name}
                                                            </p>
                                                            <Badge
                                                                variant="secondary"
                                                                className={platform.is_active
                                                                    ? 'bg-green-100 text-green-700'
                                                                    : 'bg-zinc-100 text-zinc-500'
                                                                }
                                                            >
                                                                {platform.is_active ? 'Aktiv' : 'Inaktiv'}
                                                            </Badge>
                                                        </div>
                                                        <p className="mt-0.5 text-sm text-zinc-500">
                                                            {platform.issuer}
                                                        </p>
                                                        <p className="mt-1 text-xs text-zinc-400">
                                                            Client-ID: {platform.client_id}
                                                        </p>
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
                                                    <DropdownMenuContent align="end" className="w-44">
                                                        <DropdownMenuItem onClick={() => handleEdit(platform)}>
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Bearbeiten
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => handleToggleActive(platform)}>
                                                            {platform.is_active ? (
                                                                <>
                                                                    <X className="mr-2 h-4 w-4" />
                                                                    Deaktivieren
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Check className="mr-2 h-4 w-4" />
                                                                    Aktivieren
                                                                </>
                                                            )}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            className="text-red-600 focus:text-red-600"
                                                            onClick={() => handleDelete(platform.id)}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Loeschen
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Edit Dialog */}
                        <Dialog open={!!editingPlatform} onOpenChange={(open) => !open && setEditingPlatform(null)}>
                            <DialogContent className="max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>Plattform bearbeiten</DialogTitle>
                                    <DialogDescription>
                                        Aktualisiere die LTI-Konfiguration fuer diese Plattform.
                                    </DialogDescription>
                                </DialogHeader>
                                <PlatformForm />
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
