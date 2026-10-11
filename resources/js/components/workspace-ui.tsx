import { Link, usePage } from '@inertiajs/react';
import { translator } from '@/lib/platform-i18n';
import type { Workspace } from '@/types/platform';

export function WorkspaceNav({
    workspace,
    canConfigure = false,
}: {
    workspace: Workspace;
    canConfigure?: boolean;
}) {
    const base = '/workspaces/' + workspace.id;
    const t = translator(workspace.locale);
    const canManageTeam = usePage().props.canManageTeam === true;
    return (
        <nav
            aria-label="Workspace navigation"
            className="flex flex-wrap gap-4 border-b pb-4"
        >
            <Link href={base}>{t('overview')}</Link>
            <Link href={base + '/leads'}>{t('leads')}</Link>
            <Link href={base + '/customers'}>{t('customers')}</Link>
            {canManageTeam && <Link href={base + '/team'}>{t('team')}</Link>}
            {canConfigure && (
                <Link href={base + '/configuration'}>{t('configuration')}</Link>
            )}
        </nav>
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
