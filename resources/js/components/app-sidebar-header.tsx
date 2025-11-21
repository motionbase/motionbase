import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-20 shrink-0 items-center border-b border-zinc-100 bg-white/90 px-6 backdrop-blur-md transition-[height] ease-linear supports-[backdrop-filter]:bg-white/80 lg:px-10">
            <div className="flex items-center gap-3">
                <SidebarTrigger className="-ml-1 text-zinc-500 hover:text-zinc-900" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}
