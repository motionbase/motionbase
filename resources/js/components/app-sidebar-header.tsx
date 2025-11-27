import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center border-b border-zinc-100 bg-white px-6 lg:px-8">
            <div className="flex items-center gap-3">
                <SidebarTrigger className="-ml-1 h-8 w-8 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600" />
                <div className="h-5 w-px bg-zinc-200" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}
