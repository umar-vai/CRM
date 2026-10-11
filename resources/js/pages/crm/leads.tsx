import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FieldErrors, WorkspaceNav } from '@/components/workspace-ui';
import { translator } from '@/lib/platform-i18n';
import type { Lead, Pagination, Workspace } from '@/types/platform';
const statuses = ['new', 'contacted', 'qualified', 'won', 'lost'];
export default function Leads({
    workspace,
    leads,
    filters,
    terminology,
    canCreate,
    canUpdate,
    canConfigure,
}: {
    workspace: Workspace;
    leads: Pagination<Lead>;
    filters: { search?: string; status?: string };
    terminology: { lead: string; customer: string };
    canCreate: boolean;
    canUpdate: boolean;
    canConfigure: boolean;
}) {
    const base = '/workspaces/' + workspace.id + '/leads';
    const t = translator(workspace.locale);
    const [statusErrors, setStatusErrors] = useState<Record<string, string>>(
        {},
    );
    const search = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
    });
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        source: 'direct',
        notes: '',
    });
    return (
        <div className="space-y-6 p-6" lang={workspace.locale}>
            <Head title={terminology.lead} />
            <h1 className="text-2xl font-semibold">
                {terminology.lead} · {workspace.name}
            </h1>
            <WorkspaceNav workspace={workspace} canConfigure={canConfigure} />
            <form
                className="flex flex-wrap gap-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    search.get(base, { preserveState: true });
                }}
            >
                <Input
                    className="max-w-sm"
                    aria-label={t('searchHint')}
                    placeholder={t('searchHint')}
                    value={search.data.search}
                    onChange={(e) => search.setData('search', e.target.value)}
                />
                <select
                    className="rounded border bg-background p-2"
                    aria-label="Filter status"
                    value={search.data.status}
                    onChange={(e) => search.setData('status', e.target.value)}
                >
                    <option value="">{t('allStatuses')}</option>
                    {statuses.map((s) => (
                        <option key={s} value={s}>
                            {t(s)}
                        </option>
                    ))}
                </select>
                <Button disabled={search.processing}>{t('search')}</Button>
                <FieldErrors errors={search.errors} />
            </form>
            <p>
                {leads.total} {t('records')}
            </p>
            <FieldErrors errors={statusErrors} />
            <div className="grid gap-6 xl:grid-cols-[2fr_1fr]">
                <div className="overflow-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="p-3">{t('name')}</th>
                                <th className="p-3">{t('contact')}</th>
                                <th className="p-3">{t('source')}</th>
                                <th className="p-3">{t('status')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leads.data.map((lead) => (
                                <tr className="border-b" key={lead.id}>
                                    <td className="p-3">
                                        <Link
                                            className="underline"
                                            href={base + '/' + lead.id}
                                        >
                                            {lead.name}
                                        </Link>
                                    </td>
                                    <td className="p-3">
                                        <div>{lead.email}</div>
                                        <div>{lead.phone}</div>
                                    </td>
                                    <td className="p-3">{t(lead.source)}</td>
                                    <td className="p-3">
                                        {canUpdate && !lead.customer_exists ? (
                                            <select
                                                className="rounded border bg-background p-2"
                                                aria-label={
                                                    t('status') +
                                                    ': ' +
                                                    lead.name
                                                }
                                                value={lead.status}
                                                onChange={(e) =>
                                                    router.patch(
                                                        base + '/' + lead.id,
                                                        {
                                                            version:
                                                                lead.version,
                                                            status: e.target
                                                                .value,
                                                        },
                                                        {
                                                            preserveScroll: true,
                                                            onError:
                                                                setStatusErrors,
                                                            onSuccess: () =>
                                                                setStatusErrors(
                                                                    {},
                                                                ),
                                                        },
                                                    )
                                                }
                                            >
                                                {statuses.map((s) => (
                                                    <option key={s} value={s}>
                                                        {t(s)}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            t(lead.status)
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {leads.data.length === 0 && (
                        <p className="p-6">{t('empty')}</p>
                    )}
                    <nav
                        aria-label="Pagination"
                        className="flex flex-wrap gap-3 p-4"
                    >
                        {leads.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    className={
                                        link.active ? 'font-bold underline' : ''
                                    }
                                    key={i}
                                    href={link.url}
                                >
                                    {link.label
                                        .replace(/&laquo;/g, '‹')
                                        .replace(/&raquo;/g, '›')
                                        .replace(/<[^>]+>/g, '')}
                                </Link>
                            ) : (
                                <span className="text-muted-foreground" key={i}>
                                    {link.label
                                        .replace(/&laquo;/g, '‹')
                                        .replace(/&raquo;/g, '›')}
                                </span>
                            ),
                        )}
                    </nav>
                </div>
                {canCreate && (
                    <form
                        className="space-y-4 rounded-xl border p-5"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(base, {
                                preserveScroll: true,
                                onSuccess: () => form.reset(),
                            });
                        }}
                    >
                        <h2 className="text-lg font-semibold">
                            {t('new')} {terminology.lead}
                        </h2>
                        {(['name', 'email', 'phone'] as const).map((field) => (
                            <label className="block capitalize" key={field}>
                                {t(field)}
                                <Input
                                    type={
                                        field === 'email'
                                            ? 'email'
                                            : field === 'phone'
                                              ? 'tel'
                                              : 'text'
                                    }
                                    required={field === 'name'}
                                    value={form.data[field]}
                                    onChange={(e) =>
                                        form.setData(field, e.target.value)
                                    }
                                />
                            </label>
                        ))}
                        <label className="block">
                            {t('source')}
                            <select
                                className="block w-full rounded border bg-background p-2"
                                value={form.data.source}
                                onChange={(e) =>
                                    form.setData('source', e.target.value)
                                }
                            >
                                {[
                                    'direct',
                                    'whatsapp',
                                    'messenger',
                                    'referral',
                                    'website',
                                ].map((s) => (
                                    <option key={s} value={s}>
                                        {t(s)}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="block">
                            {t('notes')}
                            <textarea
                                className="block w-full rounded border bg-background p-2"
                                maxLength={3000}
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                            />
                        </label>
                        <FieldErrors errors={form.errors} />
                        <Button disabled={form.processing}>{t('save')}</Button>
                    </form>
                )}
            </div>
        </div>
    );
}
Leads.layout = { breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }] };
