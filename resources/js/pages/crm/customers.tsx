import { Head, Link, useForm } from '@inertiajs/react';
import { RecordPagination } from '@/components/record-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FieldErrors, WorkspaceNav } from '@/components/workspace-ui';
import { translator } from '@/lib/platform-i18n';
import type { Customer, Pagination, Workspace } from '@/types/platform';

export default function Customers({
    workspace,
    customers,
    filters,
    terminology,
    canConfigure,
}: {
    workspace: Workspace;
    customers: Pagination<Customer>;
    filters: { search?: string };
    terminology: { customer: string };
    canConfigure: boolean;
}) {
    const t = translator(workspace.locale);
    const base = '/workspaces/' + workspace.id;
    const search = useForm({ search: filters.search ?? '' });
    return (
        <div className="space-y-6 p-6" lang={workspace.locale}>
            <Head title={terminology.customer} />
            <h1 className="text-2xl font-semibold">
                {terminology.customer} · {workspace.name}
            </h1>
            <WorkspaceNav workspace={workspace} canConfigure={canConfigure} />
            <form
                className="flex flex-wrap gap-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    search.get(base + '/customers', { preserveState: true });
                }}
            >
                <Input
                    className="max-w-sm"
                    aria-label={t('searchHint')}
                    placeholder={t('searchHint')}
                    value={search.data.search}
                    onChange={(event) =>
                        search.setData('search', event.target.value)
                    }
                />
                <Button disabled={search.processing}>{t('search')}</Button>
                <FieldErrors errors={search.errors} />
            </form>
            <p>
                {customers.total} {t('records')}
            </p>
            <div className="overflow-auto rounded-xl border">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b">
                            {['name', 'contact', 'source', 'details'].map(
                                (field) => (
                                    <th className="p-3" key={field}>
                                        {t(field)}
                                    </th>
                                ),
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {customers.data.map((customer) => (
                            <tr className="border-b" key={customer.id}>
                                <td className="p-3">{customer.name}</td>
                                <td className="p-3">
                                    <div>{customer.email}</div>
                                    <div>{customer.phone}</div>
                                </td>
                                <td className="p-3">{t(customer.source)}</td>
                                <td className="p-3">
                                    <Link
                                        className="underline"
                                        href={
                                            base + '/leads/' + customer.lead_id
                                        }
                                    >
                                        {t('timeline')}
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {customers.data.length === 0 && (
                    <p className="p-6">{t('noCustomers')}</p>
                )}
                <RecordPagination links={customers.links} />
            </div>
        </div>
    );
}
Customers.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }],
};
