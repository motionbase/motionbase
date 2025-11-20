import { Button } from '@/components/ui/button';
import AppLogo from '@/components/app-logo';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Layers, Zap } from 'lucide-react';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Willkommen bei Motionbase" />
            <div className="min-h-screen bg-black text-white selection:bg-[#ff0055] selection:text-white">
                {/* Navigation */}
                <header className="absolute top-0 left-0 z-50 w-full border-b border-white/10 bg-black/50 backdrop-blur-md">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
                        <div className="flex items-center gap-3">
                            <AppLogo className="h-8 w-8 text-[#ff0055]" />
                            <span className="text-xl font-bold tracking-tight">Motionbase</span>
                        </div>
                        <nav className="flex items-center gap-4">
                        {auth.user ? (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="border-white/20 text-white hover:bg-white/10 hover:text-white"
                                >
                                    <Link href="/dashboard">Zum Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Link
                                        href="/login"
                                        className="text-sm font-medium text-white/70 transition hover:text-white"
                                    >
                                        Login
                                    </Link>
                                    {canRegister && (
                                        <Button
                                            asChild
                                            className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90"
                                        >
                                            <Link href="/register">Registrieren</Link>
                                        </Button>
                                )}
                            </>
                        )}
                    </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden pt-20 text-center">
                    <div className="pointer-events-none absolute top-0 left-1/2 -translate-x-1/2 opacity-40 blur-3xl">
                        <div className="h-[500px] w-[800px] rounded-full bg-[radial-gradient(circle,#ff0055_0%,transparent_70%)]" />
                    </div>

                    <div className="relative z-10 max-w-4xl px-6">
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-widest text-[#ff0055]">
                            <Zap className="h-3 w-3" />
                            <span>Next Gen LMS Platform</span>
                        </div>
                        <h1 className="mb-6 text-5xl font-bold tracking-tight sm:text-7xl">
                            Lernen neu definiert. <br />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-white via-white/80 to-white/50">
                                Einfach. Modern. Schnell.
                            </span>
                        </h1>
                        <p className="mx-auto mb-10 max-w-2xl text-lg text-white/60 sm:text-xl">
                            Erstelle Kurse, verwalte Inhalte und teile Wissen in einer Umgebung,
                            die sich nicht nach Arbeit anfühlt.
                        </p>
                        <div className="flex flex-wrap justify-center gap-4">
                            <Button
                                size="lg"
                                className="h-12 rounded-full bg-[#ff0055] px-8 text-base font-medium text-white transition-transform hover:scale-105 hover:bg-[#ff0055]/90"
                                asChild
                            >
                                <Link href={auth.user ? '/dashboard' : '/register'}>
                                    Jetzt starten <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                            <Button
                                size="lg"
                                variant="outline"
                                className="h-12 rounded-full border-white/20 bg-transparent px-8 text-base font-medium text-white hover:bg-white/10 hover:text-white"
                                asChild
                            >
                                <Link href="/themen">Themen entdecken</Link>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* Feature Section */}
                <section className="border-t border-white/10 bg-black py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="grid gap-12 md:grid-cols-3">
                            <div className="group rounded-2xl border border-white/10 bg-white/5 p-8 transition hover:-translate-y-1 hover:border-[#ff0055]/50">
                                <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-[#ff0055]/10 text-[#ff0055]">
                                    <Layers className="h-6 w-6" />
                                </div>
                                <h3 className="mb-2 text-xl font-bold text-white">Strukturierte Inhalte</h3>
                                <p className="text-white/60">
                                    Organisiere Wissen in klaren Kategorien und Themen. Perfekt für Teams und Klassen.
                                </p>
                            </div>
                            <div className="group rounded-2xl border border-white/10 bg-white/5 p-8 transition hover:-translate-y-1 hover:border-[#ff0055]/50">
                                <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-[#ff0055]/10 text-[#ff0055]">
                                    <Zap className="h-6 w-6" />
                                </div>
                                <h3 className="mb-2 text-xl font-bold text-white">Blitzschnell</h3>
                                <p className="text-white/60">
                                    Dank modernster Technologie laden deine Inhalte sofort. Keine Wartezeiten, voller Fokus.
                                </p>
                            </div>
                            <div className="group rounded-2xl border border-white/10 bg-white/5 p-8 transition hover:-translate-y-1 hover:border-[#ff0055]/50">
                                <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-[#ff0055]/10 text-[#ff0055]">
                                    <CheckCircle2 className="h-6 w-6" />
                                </div>
                                <h3 className="mb-2 text-xl font-bold text-white">Einfacher Editor</h3>
                                <p className="text-white/60">
                                    Erstelle ansprechende Artikel mit unserem intuitiven Block-Editor. Ohne Ablenkung.
                                </p>
                            </div>
                        </div>
                </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-white/10 bg-black py-12 text-center">
                    <p className="text-sm text-white/40">
                        &copy; {new Date().getFullYear()} Motionbase. All rights reserved.
                    </p>
                </footer>
            </div>
        </>
    );
}
