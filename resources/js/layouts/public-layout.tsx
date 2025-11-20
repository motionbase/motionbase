import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren, type ReactNode } from 'react';

interface PublicLayoutProps {
    children: ReactNode;
    headline?: string;
    description?: string;
    className?: string;
}

export default function PublicLayout({
    children,
    headline,
    description,
    className,
}: PropsWithChildren<PublicLayoutProps>) {
    return (
        <div className="min-h-screen bg-black text-white">
            <div className="relative flex min-h-screen flex-col overflow-hidden">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,#ff0055_0%,rgba(0,0,0,0)_55%)] opacity-60" />
                <header className="relative border-b border-white/10 bg-black/70 py-6 backdrop-blur">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4">
                        <Link href="/" className="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl">
                            MotionBase
                        </Link>
                        <div className="flex gap-2">
                            <Button
                                variant="ghost"
                                className="text-white hover:bg-white/10 hover:text-white"
                                asChild
                            >
                                <Link href="/">Startseite</Link>
                            </Button>
                            <Button className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90" asChild>
                                <Link href="/login">Zum Dashboard</Link>
                            </Button>
                        </div>
                    </div>
                    {(headline || description) && (
                        <div className="mx-auto mt-10 w-full max-w-4xl px-4 text-center">
                            {headline && (
                                <p className="text-sm uppercase tracking-[0.3em] text-white/60">Showcase</p>
                            )}
                            {headline && (
                                <h1 className="mt-3 text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                                    {headline}
                                </h1>
                            )}
                            {description && (
                                <p className="mt-4 text-lg text-white/70">{description}</p>
                            )}
                        </div>
                    )}
                </header>

                <main className={cn('relative mx-auto w-full px-4 py-16 lg:px-10', className)}>{children}</main>

                <footer className="relative mt-auto border-t border-white/10 bg-black/70 py-6 text-center text-xs text-white/60">
                    © {new Date().getFullYear()} Motionbase. Gestalte deine Themen im eigenen Branding.
                </footer>
            </div>
        </div>
    );
}
