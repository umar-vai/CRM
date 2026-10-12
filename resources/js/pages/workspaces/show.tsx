import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, CircleDot, UsersRound } from 'lucide-react';
import { WorkspaceNav } from '@/components/workspace-ui';
import { translator } from '@/lib/platform-i18n';
import type { Workspace } from '@/types/platform';

export default function Show({
    workspace,
    metrics,
    modules,
    canConfigure,
}: {
    workspace: Workspace;
    metrics: Record<string, number>;
    modules: Record<string, { name: string; status: string }>;
    canConfigure: boolean;
}) {
    const t = translator(workspace.locale);
    const bn = workspace.locale === 'bn';
    const base = '/workspaces/' + workspace.id;
    const metricCards = [
        { key: 'leads', label: t('leads'), icon: UsersRound },
        { key: 'newLeads', label: t('newLeads'), icon: CircleDot },
        { key: 'wonLeads', label: t('wonLeads'), icon: CheckCircle2 },
    ];
    const plannedModules = Object.entries(modules).filter(
        ([, module]) => module.status !== 'available',
    );

    return (
        <div
            className="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8"
            lang={workspace.locale}
        >
            <Head title={workspace.name} />
            <WorkspaceNav workspace={workspace} canConfigure={canConfigure} />

            <header className="flex flex-wrap items-end justify-between gap-4">
                <div className="space-y-2">
                    <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {bn ? 'আপনার ব্যবসার ওভারভিউ' : 'Business overview'}
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">{workspace.name}</h1>
                    <p className="max-w-2xl text-sm text-muted-foreground">
                        {bn
                            ? 'লিডের বর্তমান অবস্থা দেখুন এবং প্রয়োজনীয় কাজ শুরু করুন।'
                            : 'Track your leads and jump back into your work.'}
                    </p>
                </div>
                <Link
                    className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    href={base + '/leads'}
                >
                    {t('manageLeads')}
                    <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
            </header>

            <section aria-label={bn ? 'CRM পরিসংখ্যান' : 'CRM metrics'} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {metricCards.map(({ key, label, icon: Icon }) => (
                    <div
                        key={key}
                        className="rounded-2xl border border-border/80 bg-card p-5 shadow-sm"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <span className="text-sm font-medium text-muted-foreground">
                                {label}
                            </span>
                            <span className="rounded-lg bg-muted p-2 text-muted-foreground">
                                <Icon className="size-4" aria-hidden="true" />
                            </span>
                        </div>
                        <p className="mt-4 text-3xl font-semibold tracking-tight tabular-nums">
                            {typeof metrics[key] === 'number'
                                ? metrics[key].toLocaleString(bn ? 'bn-BD' : 'en-US')
                                : '—'}
                        </p>
                    </div>
                ))}
            </section>

            <div className="grid gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
                <section className="rounded-2xl border bg-card p-5 sm:p-6">
                    <div className="mb-5 space-y-1">
                        <h2 className="text-lg font-semibold">
                            {bn ? 'দ্রুত কাজ শুরু করুন' : 'Quick actions'}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {bn
                                ? 'আপনার দৈনন্দিন CRM কাজের শর্টকাট।'
                                : 'Shortcuts to your daily CRM workflows.'}
                        </p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Link
                            className="group flex min-h-24 flex-col justify-between rounded-xl border p-4 transition-colors hover:border-foreground/30 hover:bg-muted/50 focus-visible:outline-2 focus-visible:outline-ring"
                            href={base + '/leads'}
                        >
                            <div className="flex items-center justify-between">
                                <UsersRound className="size-5 text-muted-foreground" aria-hidden="true" />
                                <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-1" aria-hidden="true" />
                            </div>
                            <span className="font-medium">{t('manageLeads')}</span>
                        </Link>
                        <Link
                            className="group flex min-h-24 flex-col justify-between rounded-xl border p-4 transition-colors hover:border-foreground/30 hover:bg-muted/50 focus-visible:outline-2 focus-visible:outline-ring"
                            href={base + '/customers'}
                        >
                            <div className="flex items-center justify-between">
                                <CheckCircle2 className="size-5 text-muted-foreground" aria-hidden="true" />
                                <ArrowRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-1" aria-hidden="true" />
                            </div>
                            <span className="font-medium">{t('customers')}</span>
                        </Link>
                    </div>
                </section>

                <section className="rounded-2xl border bg-card p-5 sm:p-6">
                    <h2 className="text-lg font-semibold">
                        {bn ? 'পরবর্তী মডিউলগুলো' : 'Upcoming modules'}
                    </h2>
                    <p className="mt-1 mb-4 text-sm text-muted-foreground">
                        {bn
                            ? 'এগুলো এখনো তৈরি হয়নি। বর্তমান CRM-এর অংশ হিসেবে দেখানো হচ্ছে না।'
                            : 'Planned features; not yet available in this workspace.'}
                    </p>
                    {plannedModules.length === 0 ? (
                        <p className="text-sm text-muted-foreground">—</p>
                    ) : (
                        <ul className="space-y-3">
                            {plannedModules.map(([key, module]) => (
                                <li key={key} className="flex items-center justify-between gap-3 border-b pb-3 last:border-b-0 last:pb-0">
                                    <span className="text-sm font-medium">{module.name}</span>
                                    <span className="rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground">
                                        {t('planned')}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </div>
    );
}
Show.layout = { breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }] };
