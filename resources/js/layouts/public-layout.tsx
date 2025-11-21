import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Github, Twitter } from 'lucide-react';
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
        <div className="min-h-screen bg-black text-white font-sans antialiased selection:bg-[#ff0055] selection:text-white">
            <div className="fixed inset-0 pointer-events-none z-0">
                <div className="absolute top-[-20%] left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-[#ff0055] opacity-[0.03] blur-[120px] rounded-full mix-blend-screen" />
            </div>

            <header className="sticky top-0 z-50 w-full border-b border-white/5 bg-black/50 backdrop-blur-xl">
                <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-6">
                    <Link 
                        href="/" 
                        className="flex items-center gap-2 text-lg font-bold tracking-tight hover:opacity-80 transition-opacity"
                    >
                        MotionBase
                    </Link>
                    
                    <div className="flex items-center gap-4">
                        <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-white/60">
                            <Link href="/themen" className="hover:text-white transition-colors">Themen</Link>
                            <a href="#" className="hover:text-white transition-colors">Features</a>
                            <a href="#" className="hover:text-white transition-colors">Community</a>
                        </nav>
                        <div className="h-4 w-px bg-white/10 hidden md:block" />
                        <div className="flex gap-3">
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                className="text-white/70 hover:text-white hover:bg-white/5" 
                                asChild
                            >
                                <Link href="/login">Login</Link>
                            </Button>
                            <Button 
                                size="sm" 
                                className="bg-[#ff0055] text-white hover:bg-[#ff0055]/90 shadow-[0_0_20px_-5px_#ff0055]" 
                                asChild
                            >
                                <Link href="/register">Get Started</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </header>

            {(headline || description) && (
                <div className="relative border-b border-white/5 bg-white/[0.02]">
                    <div className="mx-auto max-w-7xl px-6 py-20 lg:py-28">
                        <div className="max-w-3xl">
                            {headline && (
                                <h1 className="text-4xl font-bold tracking-tight text-white sm:text-6xl mb-6">
                                    {headline}
                                </h1>
                            )}
                            {description && (
                                <p className="text-lg text-white/60 leading-relaxed max-w-2xl">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <main className={cn('relative mx-auto w-full max-w-7xl px-6 py-12', className)}>
                {children}
            </main>

            <footer className="border-t border-white/5 bg-black py-12 mt-auto">
                <div className="mx-auto max-w-7xl px-6 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-bold text-white">MotionBase</span>
                        <span className="text-sm text-white/40">© {new Date().getFullYear()}</span>
                    </div>
                    <div className="flex gap-6">
                        <a href="#" className="text-white/40 hover:text-white transition-colors">
                            <Github className="w-5 h-5" />
                        </a>
                        <a href="#" className="text-white/40 hover:text-white transition-colors">
                            <Twitter className="w-5 h-5" />
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
