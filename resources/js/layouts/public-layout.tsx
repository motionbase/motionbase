import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { type PropsWithChildren, type ReactNode, useState } from 'react';

interface PublicLayoutProps {
    children: ReactNode;
    headline?: string;
    description?: string;
    className?: string;
    stickyFooter?: boolean;
    fullWidth?: boolean;
}

export default function PublicLayout({
    children,
    headline,
    description,
    className,
    stickyFooter = false,
    fullWidth = false,
}: PropsWithChildren<PublicLayoutProps>) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <div className="min-h-screen bg-white text-zinc-950 font-sans antialiased selection:bg-zinc-900 selection:text-white">
            {/* Header */}
            <header className="sticky top-0 z-50 w-full border-b border-zinc-100 bg-white/95 backdrop-blur-sm">
                <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <Link
                        href="/"
                        className="flex items-center gap-2.5 text-xl font-bold tracking-tight text-zinc-900 hover:opacity-80 transition-opacity"
                    >
                        <AppLogoIcon className="h-8 w-8" />
                        <span className="hidden sm:inline">MotionBase</span>
                    </Link>

                    {/* Desktop Navigation */}
                    <nav className="hidden md:flex items-center gap-1">
                        <Link
                            href="/themen"
                            className="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                        >
                            Themen
                        </Link>
                    </nav>

                    {/* Mobile Menu Button */}
                    <button
                        type="button"
                        className="md:hidden flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-100"
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    >
                        {mobileMenuOpen ? (
                            <X className="h-5 w-5" />
                        ) : (
                            <Menu className="h-5 w-5" />
                        )}
                    </button>
                </div>

                {/* Mobile Menu */}
                {mobileMenuOpen && (
                    <div className="md:hidden border-t border-zinc-100 bg-white px-4 py-4">
                        <nav className="flex flex-col gap-1">
                            <Link
                                href="/themen"
                                className="rounded-lg px-4 py-3 text-sm font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900"
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                Themen
                            </Link>
                        </nav>
                    </div>
                )}
            </header>

            {/* Hero Section */}
            {(headline || description) && (
                <div className="border-b border-zinc-100 bg-zinc-50">
                    <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                        <div className="max-w-3xl">
                            {headline && (
                                <h1 className="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
                                    {headline}
                                </h1>
                            )}
                            {description && (
                                <p className="mt-4 text-base text-zinc-600 sm:text-lg lg:mt-6">
                                    {description}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Main Content */}
            <main
                className={cn(
                    'relative mx-auto w-full',
                    fullWidth ? 'max-w-none' : 'max-w-7xl px-4 sm:px-6 lg:px-8',
                    stickyFooter ? '' : 'py-10 sm:py-12 lg:py-16',
                    className,
                )}
            >
                {children}
            </main>

            {/* Footer */}
            <footer
                className={cn(
                    'border-t border-zinc-100 bg-white',
                    stickyFooter ? 'sticky bottom-0 z-40' : 'mt-auto',
                )}
            >
                <div className="mx-auto flex h-14 w-full max-w-7xl items-center justify-center px-4 sm:px-6 lg:px-8">
                    <span className="text-sm text-zinc-500">
                        © {new Date().getFullYear()} MotionBase
                    </span>
                </div>
            </footer>
        </div>
    );
}
