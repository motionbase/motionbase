import { Button } from '@/components/ui/button';
import AppLogo from '@/components/app-logo';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BookOpen, Layout, Users, Zap, GraduationCap } from 'lucide-react';
import PublicLayout from '@/layouts/public-layout';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Willkommen" />
            
            {/* Wir nutzen hier nicht das PublicLayout als Wrapper, da die Startseite ein eigenes, full-screen Hero-Layout hat 
                Aber wir übernehmen den Header/Footer Style manuell oder nutzen das Layout ohne Container.
                Hier baue ich eine komplett eigene Landingpage-Struktur.
            */}
            
            <div className="min-h-screen bg-black text-white selection:bg-[#ff0055] selection:text-white font-sans">
                {/* Background Effects */}
                <div className="fixed inset-0 pointer-events-none">
                    <div className="absolute top-[-10%] left-1/2 -translate-x-1/2 w-[80vw] h-[600px] bg-[#ff0055] opacity-[0.04] blur-[120px] rounded-full" />
                    <div className="absolute bottom-[-10%] right-0 w-[600px] h-[600px] bg-blue-500 opacity-[0.02] blur-[120px] rounded-full" />
                </div>

                {/* Navigation */}
                <header className="fixed top-0 z-50 w-full border-b border-white/5 bg-black/50 backdrop-blur-xl">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                        <div className="flex items-center gap-2 font-bold text-lg tracking-tight">
                            MotionBase
                        </div>
                        <nav className="flex items-center gap-4">
                            {auth.user ? (
                                <Button asChild variant="secondary" className="bg-white text-black hover:bg-white/90">
                                    <Link href="/dashboard">Dashboard öffnen</Link>
                                </Button>
                            ) : (
                                <>
                                    <Link href="/login" className="text-sm font-medium text-white/60 hover:text-white transition-colors">
                                        Login
                                    </Link>
                                    <Button asChild className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90 shadow-[0_0_15px_-3px_#ff0055]">
                                        <Link href="/register">Starten</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="relative pt-32 pb-20">
                    {/* Hero */}
                    <div className="mx-auto max-w-7xl px-6 text-center">
                        <div className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-[#ff0055] mb-8">
                            <span className="relative flex h-2 w-2">
                              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#ff0055] opacity-75"></span>
                              <span className="relative inline-flex rounded-full h-2 w-2 bg-[#ff0055]"></span>
                            </span>
                            Next Generation LMS
                        </div>
                        
                        <h1 className="mx-auto max-w-4xl text-5xl font-bold tracking-tight sm:text-7xl mb-8 bg-clip-text text-transparent bg-gradient-to-b from-white to-white/50">
                            Wissen vermitteln. <br/>
                            Einfach und Schnell.
                        </h1>
                        
                        <p className="mx-auto max-w-2xl text-lg text-white/60 mb-12 leading-relaxed">
                            Erstelle interaktive Kurse, verwalte deine Lernenden und behalte den Fortschritt im Auge. 
                            Alles in einer modernen Oberfläche, die Spaß macht.
                        </p>

                        <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Button size="lg" className="h-12 px-8 rounded-full bg-white text-black hover:bg-white/90 font-medium" asChild>
                                <Link href="/register">
                                    Kostenlos testen
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" className="h-12 px-8 rounded-full border-white/10 bg-white/5 hover:bg-white/10 text-white" asChild>
                                <Link href="/themen">
                                    Kurskatalog ansehen <ArrowRight className="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* UI Preview / Grid */}
                    <div className="mx-auto max-w-7xl px-6 mt-24">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Card 1 */}
                            <div className="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-8 hover:border-[#ff0055]/30 transition-colors duration-500">
                                <div className="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#ff0055]/10 text-[#ff0055]">
                                    <Layout className="h-5 w-5" />
                                </div>
                                <h3 className="text-xl font-bold mb-2">Intuitiver Editor</h3>
                                <p className="text-white/60 text-sm leading-relaxed">
                                    Schreibe Inhalte wie in einem Dokument. Blocksatz, Bilder und Videos per Drag & Drop.
                                </p>
                            </div>

                            {/* Card 2 */}
                            <div className="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-8 hover:border-[#ff0055]/30 transition-colors duration-500">
                                <div className="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#ff0055]/10 text-[#ff0055]">
                                    <BookOpen className="h-5 w-5" />
                                </div>
                                <h3 className="text-xl font-bold mb-2">Strukturierte Kurse</h3>
                                <p className="text-white/60 text-sm leading-relaxed">
                                    Organisiere Wissen in Themen und Abschnitte. Automatische Navigation und Fortschritt.
                                </p>
                            </div>

                            {/* Card 3 */}
                            <div className="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-8 hover:border-[#ff0055]/30 transition-colors duration-500">
                                <div className="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#ff0055]/10 text-[#ff0055]">
                                    <GraduationCap className="h-5 w-5" />
                                </div>
                                <h3 className="text-xl font-bold mb-2">Fokus Modus</h3>
                                <p className="text-white/60 text-sm leading-relaxed">
                                    Eine saubere Leseansicht ohne Ablenkungen für deine Teilnehmer.
                                </p>
                            </div>
                        </div>
                    </div>
                </main>

                <footer className="border-t border-white/5 bg-black py-12 text-center">
                    <p className="text-sm text-white/40">
                        &copy; {new Date().getFullYear()} MotionBase. All rights reserved.
                    </p>
                </footer>
            </div>
        </>
    );
}
