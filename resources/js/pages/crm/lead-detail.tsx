import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { RecordPagination } from '@/components/record-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FieldErrors, WorkspaceNav } from '@/components/workspace-ui';
import { translator } from '@/lib/platform-i18n';
import type {
    Lead,
    LeadActivity,
    Pagination,
    Workspace,
} from '@/types/platform';

type Props = {
    workspace: Workspace;
    lead: Lead;
    activities: Pagination<LeadActivity>;
    duplicates: { id: string; name: string }[];
    customer: { id: string; name: string } | null;
    terminology: { lead: string; customer: string };
    canUpdate: boolean;
    canConvert: boolean;
    canAddActivity: boolean;
    canConfigure: boolean;
};
export default function LeadDetail({
    workspace,
    lead,
    activities,
    duplicates,
    customer,
    terminology,
    canUpdate,
    canConvert,
    canAddActivity,
    canConfigure,
}: Props) {
    const t = translator(workspace.locale);
    const base = '/workspaces/' + workspace.id;
    const url = base + '/leads/' + lead.id;
    const edit = useForm({
        name: lead.name,
        email: lead.email ?? '',
        phone: lead.phone ?? '',
        source: lead.source,
        status: lead.status,
        notes: lead.notes ?? '',
        version: lead.version,
    });
    const activity = useForm({
        kind: 'note',
        body: '',
        request_key: crypto.randomUUID(),
        version: lead.version,
    });
    const conversion = useForm({ version: lead.version });
    useEffect(() => {
        edit.setData({
            name: lead.name,
            email: lead.email ?? '',
            phone: lead.phone ?? '',
            source: lead.source,
            status: lead.status,
            notes: lead.notes ?? '',
            version: lead.version,
        });
        activity.setData('version', lead.version);
        conversion.setData('version', lead.version);
        // A successful write or explicit reload establishes the new server version.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [lead.id, lead.version]);
    return (
        <div className="space-y-6 p-6" lang={workspace.locale}>
            <Head title={lead.name + ' · ' + terminology.lead} />
            <WorkspaceNav workspace={workspace} canConfigure={canConfigure} />
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link className="underline" href={base + '/leads'}>
                        {t('leads')}
                    </Link>
                    <h1 className="text-2xl font-semibold">{lead.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {t('version')} {lead.version} · {t(lead.status)}
                    </p>
                </div>
                <Button variant="outline" onClick={() => router.reload()}>
                    {t('reload')}
                </Button>
            </div>
            {duplicates.length > 0 && (
                <section className="rounded-xl border border-amber-500 p-4">
                    <h2 className="font-semibold">{t('duplicates')}</h2>
                    <ul className="mt-2 space-y-1">
                        {duplicates.map((item) => (
                            <li key={item.id}>
                                <Link
                                    className="underline"
                                    href={base + '/leads/' + item.id}
                                >
                                    {item.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
            <div className="grid gap-6 lg:grid-cols-2">
                <section className="space-y-4 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">
                        {canUpdate && !customer ? t('editLead') : t('details')}
                    </h2>
                    {canUpdate && !customer ? (
                        <form
                            className="space-y-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                edit.patch(url, { preserveScroll: true });
                            }}
                        >
                            {(['name', 'email', 'phone'] as const).map(
                                (field) => (
                                    <label className="block" key={field}>
                                        {t(field)}
                                        <Input
                                            maxLength={
                                                field === 'name'
                                                    ? 150
                                                    : field === 'phone'
                                                      ? 30
                                                      : 254
                                            }
                                            type={
                                                field === 'email'
                                                    ? 'email'
                                                    : field === 'phone'
                                                      ? 'tel'
                                                      : 'text'
                                            }
                                            required={field === 'name'}
                                            value={edit.data[field]}
                                            onChange={(event) =>
                                                edit.setData(
                                                    field,
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                ),
                            )}
                            <label className="block">
                                {t('source')}
                                <select
                                    className="block w-full rounded border bg-background p-2"
                                    value={edit.data.source}
                                    onChange={(event) =>
                                        edit.setData(
                                            'source',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {[
                                        'direct',
                                        'whatsapp',
                                        'messenger',
                                        'referral',
                                        'website',
                                    ].map((value) => (
                                        <option key={value} value={value}>
                                            {t(value)}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="block">
                                {t('status')}
                                <select
                                    className="block w-full rounded border bg-background p-2"
                                    value={edit.data.status}
                                    onChange={(event) =>
                                        edit.setData(
                                            'status',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {[
                                        'new',
                                        'contacted',
                                        'qualified',
                                        'won',
                                        'lost',
                                    ].map((value) => (
                                        <option key={value} value={value}>
                                            {t(value)}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="block">
                                {t('notes')}
                                <textarea
                                    className="block w-full rounded border bg-background p-2"
                                    maxLength={3000}
                                    value={edit.data.notes}
                                    onChange={(event) =>
                                        edit.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                            <FieldErrors errors={edit.errors} />
                            <Button disabled={edit.processing}>
                                {t('save')}
                            </Button>
                        </form>
                    ) : (
                        <dl className="space-y-2">
                            {(
                                [
                                    'name',
                                    'email',
                                    'phone',
                                    'source',
                                    'notes',
                                ] as const
                            ).map((field) => (
                                <div key={field}>
                                    <dt className="text-sm text-muted-foreground">
                                        {t(field)}
                                    </dt>
                                    <dd className="break-words whitespace-pre-wrap">
                                        {field === 'source'
                                            ? t(lead[field])
                                            : lead[field] || '—'}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}
                    {customer ? (
                        <div className="space-y-2 border-t pt-4">
                            <p>{t('convertedHint')}</p>
                            <Link
                                className="underline"
                                href={base + '/customers'}
                            >
                                {terminology.customer}: {customer.name}
                            </Link>
                        </div>
                    ) : (
                        canConvert && (
                            <form
                                className="space-y-3 border-t pt-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    conversion.post(url + '/convert', {
                                        preserveScroll: true,
                                    });
                                }}
                            >
                                <p className="text-sm text-muted-foreground">
                                    {t('convertHint')}
                                </p>
                                <FieldErrors errors={conversion.errors} />
                                <Button disabled={conversion.processing}>
                                    {t('convert')}
                                </Button>
                            </form>
                        )
                    )}
                </section>
                <section className="space-y-4 rounded-xl border p-5">
                    <h2 className="text-lg font-semibold">{t('timeline')}</h2>
                    {canAddActivity && (
                        <form
                            className="space-y-3 border-b pb-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                activity.post(url + '/activities', {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        activity.reset('body');
                                        activity.setData(
                                            'request_key',
                                            crypto.randomUUID(),
                                        );
                                    },
                                });
                            }}
                        >
                            <label className="block">
                                {t('addActivity')}
                                <select
                                    className="block w-full rounded border bg-background p-2"
                                    value={activity.data.kind}
                                    onChange={(event) =>
                                        activity.setData(
                                            'kind',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {['note', 'call', 'meeting'].map(
                                        (value) => (
                                            <option key={value} value={value}>
                                                {t(value)}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </label>
                            <label className="block">
                                {t('notes')}
                                <textarea
                                    className="block w-full rounded border bg-background p-2"
                                    required
                                    maxLength={3000}
                                    value={activity.data.body}
                                    onChange={(event) =>
                                        activity.setData(
                                            'body',
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                            <FieldErrors errors={activity.errors} />
                            <Button disabled={activity.processing}>
                                {t('save')}
                            </Button>
                        </form>
                    )}
                    {activities.data.length === 0 && <p>{t('noActivities')}</p>}
                    <ol className="space-y-4">
                        {activities.data.map((item) => (
                            <li className="border-l-2 pl-4" key={item.id}>
                                <div className="font-medium">
                                    {t(item.kind)}
                                </div>
                                <time
                                    className="text-xs text-muted-foreground"
                                    dateTime={item.created_at}
                                >
                                    {new Intl.DateTimeFormat(workspace.locale, {
                                        dateStyle: 'medium',
                                        timeStyle: 'short',
                                        timeZone: workspace.timezone,
                                    }).format(new Date(item.created_at))}
                                </time>
                                {item.body && (
                                    <p className="mt-2 break-words whitespace-pre-wrap">
                                        {item.body}
                                    </p>
                                )}
                                {item.metadata.fields && (
                                    <p className="text-sm">
                                        {t('changedFields')}:{' '}
                                        {item.metadata.fields.map(t).join(', ')}
                                    </p>
                                )}
                                {item.metadata.from && (
                                    <p className="text-sm">
                                        {t(item.metadata.from)} →{' '}
                                        {t(item.metadata.to ?? '')}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ol>
                    <RecordPagination links={activities.links} />
                </section>
            </div>
        </div>
    );
}
LeadDetail.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }],
};
