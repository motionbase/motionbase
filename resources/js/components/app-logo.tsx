import { cn } from '@/lib/utils';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo({ className }: { className?: string }) {
    return (
        <div className={cn('flex items-center gap-2', className)}>
            <AppLogoIcon className="h-8 w-8" />
            <span className="text-xl font-bold tracking-tight text-zinc-900">
                MotionBase
            </span>
        </div>
    );
}
