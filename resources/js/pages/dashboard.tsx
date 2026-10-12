import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowUpRight, Building2, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { FieldErrors } from '@/components/workspace-ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Workspace } from '@/types/platform';

const industries = [
    'service',
    'clinic',
    'retail',
    'distribution',
    'manufacturing',
    'education',
];

export default function Dashboard({
    workspaces,
}: {
    workspaces: (Workspace & { role: string })[];
}) {
    const [createOpen, setCreateOpen] = useState(workspaces.length === 0);
    const form = useForm({ name: '', industry: 'service', locale: 'bn' });
    const bn = workspaces[0]?.locale !== 'en';

    return (
        <main className="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8">
            <Head title={bn ? 'আপনার ব্যবসা' : 'Your businesses'} />
            <header className="flex flex-wrap items-end justify-between gap-4">
                <div className="space-y-2">
                    <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        NovaFlow
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {bn ? 'আপনার ব্যবসাসমূহ' : 'Your businesses'}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {bn
                            ? 'প্রতিটি ব্যবসার তথ্য ও কর্মীদের অ্যাক্সেস আলাদা রাখা হয়।'
                            : 'Each business has its own isolated workspace and team access.'}
                    </p>
                </div>
                <Button
                    type="button"
                    onClick={() => setCreateOpen((open) => !open)}
                    aria-expanded={createOpen}
                    aria-controls="create-workspace"
                    variant={createOpen ? 'outline' : 'default'}
                >
                    {createOpen ? (
                        <X className="size-4" aria-hidden="true" />
                    ) : (
                        <Plus className="size-4" aria-hidden="true" />
                    )}
                    {createOpen
                        ? bn ? 'বন্ধ করুন' : 'Close'
                        : bn ? 'নতুন ব্যবসা' : 'New business'}
                </Button>
            </header>

            {workspaces.length > 0 ? (
                <section
                    aria-label={bn ? 'ব্যবসার তালিকা' : 'Business workspaces'}
                    className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    {workspaces.map((workspace) => (
                        <Link
                            key={workspace.id}
                            href={'/workspaces/' + workspace.id}
                            className="group flex min-h-44 flex-col justify-between rounded-2xl border bg-card p-5 shadow-sm transition-colors hover:border-foreground/25 hover:bg-muted/40 focus-visible:outline-2 focus-visible:outline-ring"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="rounded-xl bg-muted p-3 text-muted-foreground">
                                    <Building2 className="size-5" aria-hidden="true" />
                                </div>
                                <ArrowUpRight
                                    className="size-4 text-muted-foreground transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
                                    aria-hidden="true"
                                />
                            </div>
                            <div>
                                <h2 className="font-semibold">{workspace.name}</h2>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {workspace.industry} · {workspace.currency} · {workspace.role}
                                </p>
                            </div>
                        </Link>
                    ))}
                </section>
            ) : (
                <section className="rounded-2xl border border-dashed p-6 text-center sm:p-10">
                    <Building2 className="mx-auto size-8 text-muted-foreground" aria-hidden="true" />
                    <h2 className="mt-3 text-lg font-semibold">
                        {bn ? 'এখনো কোনো ব্যবসা যোগ হয়নি' : 'No workspace yet'}
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {bn
                            ? 'নিচের ফর্ম থেকে প্রথম ব্যবসা তৈরি করুন।'
                            : 'Create your first business using the form below.'}
                    </p>
                </section>
            )}

            {createOpen && (
                <section
                    id="create-workspace"
                    className="max-w-2xl rounded-2xl border bg-card p-5 shadow-sm sm:p-6"
                >
                    <h2 className="text-lg font-semibold">
                        {bn ? 'নতুন ব্যবসার ওয়ার্কস্পেস' : 'Create a business workspace'}
                    </h2>
                    <p className="mt-1 mb-5 text-sm text-muted-foreground">
                        {bn
                            ? 'ব্যবসার ধরন এখন শুধু সেটিং হিসেবে সংরক্ষিত হবে; অতিরিক্ত industry module এখনো চালু নয়।'
                            : 'Industry is saved as workspace metadata; industry-specific modules are not enabled yet.'}
                    </p>
                    <form
                        className="grid gap-4 sm:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/workspaces');
                        }}
                    >
                        <label className="space-y-2 text-sm font-medium sm:col-span-2">
                            <span>{bn ? 'ব্যবসার নাম' : 'Business name'}</span>
                            <Input
                                required
                                maxLength={100}
                                autoFocus
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                        </label>
                        <label className="space-y-2 text-sm font-medium">
                            <span>{bn ? 'ব্যবসার ধরন' : 'Industry'}</span>
                            <select
                                className="flex min-h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                value={form.data.industry}
                                onChange={(event) => form.setData('industry', event.target.value)}
                            >
                                {industries.map((industry) => (
                                    <option key={industry} value={industry}>
                                        {industry}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="space-y-2 text-sm font-medium">
                            <span>{bn ? 'ভাষা' : 'Language'}</span>
                            <select
                                className="flex min-h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                value={form.data.locale}
                                onChange={(event) => form.setData('locale', event.target.value)}
                            >
                                <option value="bn">বাংলা</option>
                                <option value="en">English</option>
                            </select>
                        </label>
                        <div className="sm:col-span-2">
                            <FieldErrors errors={form.errors} />
                        </div>
                        <div className="sm:col-span-2">
                            <Button type="submit" disabled={form.processing}>
                                {bn ? 'ওয়ার্কস্পেস তৈরি করুন' : 'Create workspace'}
                            </Button>
                        </div>
                    </form>
                </section>
            )}
        </main>
    );
}
Dashboard.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }],
};
