import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Github } from 'lucide-react';
import { type PropsWithChildren, type ReactNode } from 'react';

interface PublicLayoutProps {
    children: ReactNode;
    headline?: string;
    description?: string;
    className?: string;
    stickyFooter?: boolean;
}

export default function PublicLayout({
    children,
    headline,
    description,
    className,
    stickyFooter = false,
}: PropsWithChildren<PublicLayoutProps>) {
    return (
        <div className="min-h-screen bg-white text-zinc-950 font-sans antialiased selection:bg-[#ff0055] selection:text-white">

            <header className="sticky top-0 z-50 w-full border-b border-zinc-100 bg-white/85 backdrop-blur-md">
                <div className="mx-auto flex h-20 w-full max-w-[1600px] items-center justify-between px-6">
                    <Link 
                        href="/" 
                        className="flex items-center gap-2 text-lg font-bold tracking-tight text-zinc-900 hover:opacity-80 transition-opacity"
                    >
                        MotionBase
                    </Link>
                    
                    <div className="flex items-center gap-4">
                        <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-zinc-600">
                            <Link href="/themen" className="hover:text-zinc-900 transition-colors">Themen</Link>
                            <a href="#" className="hover:text-zinc-900 transition-colors">Features</a>
                            <a href="#" className="hover:text-zinc-900 transition-colors">Community</a>
                        </nav>
                        <div className="h-4 w-px bg-zinc-200 hidden md:block" />
                        <div className="flex gap-3">
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                className="text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100" 
                                asChild
                            >
                                <Link href="/login">Login</Link>
                            </Button>
                            <Button 
                                size="sm" 
                                className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90 shadow-[0_4px_14px_-4px_#ff0055]" 
                                asChild
                            >
                                <Link href="/register">Get Started</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </header>

            {(headline || description) && (
                <div className="relative border-b border-zinc-100 bg-zinc-50/50">
                    <div className="mx-auto max-w-7xl px-6 py-20 lg:py-28">
                        <div className="max-w-3xl">
                            {headline && (
                                <h1 className="text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl mb-6">
                                    {headline}
                                </h1>
                            )}
                            {description && (
                                <p className="text-lg text-zinc-600 leading-relaxed max-w-2xl">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <main
                className={cn(
                    'relative mx-auto w-full max-w-[1600px] px-6 lg:px-12',
                    stickyFooter ? 'pt-0 pb-0' : 'pt-12 pb-16',
                    className,
                )}
            >
                {children}
            </main>

            <footer
                className={cn(
                    'h-[60px] w-full border-t border-zinc-100 bg-white',
                    stickyFooter && 'sticky bottom-0 z-50 bg-white/90 backdrop-blur-md',
                )}
            >
                <div className="mx-auto flex h-full w-full max-w-[1600px] items-center justify-between px-6 text-sm text-zinc-500">
                    <span>© {new Date().getFullYear()} MotionBase</span>
                            <div className="flex items-center gap-4">
                                <a
                                    href="https://github.com/motionbase/motionbase"
                                    className="flex items-center gap-2 hover:text-zinc-900 transition-colors"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Github className="h-4 w-4" />
                                    Github
                                </a>
                            </div>
                </div>
            </footer>
        </div>
    );
}
