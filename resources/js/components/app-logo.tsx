import { cn } from '@/lib/utils';

export default function AppLogo({ className }: { className?: string }) {
    return (
        <span className={cn('text-xl font-bold tracking-tight text-zinc-900', className)}>
            MotionBase
                </span>
    );
}
