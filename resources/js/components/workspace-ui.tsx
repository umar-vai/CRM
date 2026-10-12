import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, Settings2 } from 'lucide-react';
import type { Workspace } from '@/types/platform';

export function WorkspaceNav({
    workspace,
    canConfigure = false,
}: {
    workspace: Workspace;
    canConfigure?: boolean;
}) {
    const base = '/workspaces/' + workspace.id;
    const bn = workspace.locale === 'bn';
    const currentPath = usePage().url.split('?')[0];

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-4">
            <nav
                aria-label={bn ? 'ওয়ার্কস্পেস অবস্থান' : 'Workspace breadcrumb'}
                className="flex min-w-0 items-center gap-2 text-sm text-muted-foreground"
            >
                <Link className="shrink-0 transition-colors hover:text-foreground" href="/dashboard">
                    {bn ? 'ব্যবসাসমূহ' : 'Businesses'}
                </Link>
                <ChevronRight className="size-4 shrink-0" aria-hidden="true" />
                <Link
                    className="truncate font-medium text-foreground transition-colors hover:underline"
                    href={base}
                    aria-current={currentPath === base ? 'page' : undefined}
                >
                    {workspace.name}
                </Link>
            </nav>
            {canConfigure && (
                <Link
                    className="inline-flex min-h-9 items-center gap-2 rounded-lg border px-3 text-xs font-medium transition-colors hover:bg-muted"
                    href={base + '/configuration'}
                >
                    <Settings2 className="size-3.5" aria-hidden="true" />
                    {bn ? 'কনফিগারেশন' : 'Configuration'}
                </Link>
            )}
        </div>
    );
}

export function FieldErrors({ errors }: { errors: Record<string, string> }) {
    return (
        Object.keys(errors).length > 0 && (
            <div role="alert" className="text-sm text-red-600">
                {Object.entries(errors).map(([key, message]) => (
                    <p key={key}>{message}</p>
                ))}
            </div>
        )
    );
}
