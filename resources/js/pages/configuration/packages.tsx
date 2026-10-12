import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { FieldErrors, WorkspaceNav } from '@/components/workspace-ui';
import type { Workspace } from '@/types/platform';

type Manifest = {
    schema_version: number;
    id: string;
    version: string;
    digest: string;
    name: { en: string; bn: string };
    core_min: string;
    core_max: string;
    modules: string[];
    fields: {
        key: string;
        entity: string;
        type: string;
        required: boolean;
        label: { en: string; bn: string };
        options?: string[];
    }[];
};
export default function Packages({
    workspace,
    configurationVersion,
    packages,
    preview,
    errors,
}: {
    workspace: Workspace;
    configurationVersion: number;
    packages: Manifest[];
    preview: null | {
        manifest: Manifest;
        base_version: number;
        terminology: { lead: string; customer: string };
        preserved_override: boolean;
        blockers: string[];
        preview_only: true;
    };
    errors: Record<string, string>;
}) {
    const bn = workspace.locale === 'bn';
    const base = '/workspaces/' + workspace.id + '/configuration';
    return (
        <div className="space-y-6 p-6">
            <Head
                title={bn ? 'ব্যবসার প্যাকেজ প্রিভিউ' : 'Industry package preview'}
            />
            <h1 className="text-2xl font-semibold">
                {bn ? 'ব্যবসার প্যাকেজ প্রিভিউ' : 'Industry package preview'} ·{' '}
                {workspace.name}
            </h1>
            <WorkspaceNav workspace={workspace} canConfigure />
            <p role="status">
                {bn
                    ? 'এটি শুধু প্রিভিউ। প্যাকেজ install, settings publish বা lead-এর অতিরিক্ত তথ্য সংরক্ষণ এখনো চালু হয়নি।'
                    : 'Preview only. Package installation, settings publication and saved custom values are not enabled yet.'}
            </p>
            <Link href={base} className="underline">
                {bn
                    ? 'নামের কনফিগারেশনে ফিরুন'
                    : 'Back to terminology configuration'}
            </Link>
            <FieldErrors errors={errors} />
            <section
                className="grid gap-4 md:grid-cols-2"
                aria-label={bn ? 'প্যাকেজ তালিকা' : 'Package catalog'}
            >
                {packages.map((pkg) => (
                    <article
                        key={pkg.id + '@' + pkg.version}
                        className="space-y-3 rounded-lg border p-4"
                    >
                        <h2 className="text-lg font-semibold">
                            {pkg.name[workspace.locale]}
                        </h2>
                        <p>
                            {pkg.id} · v{pkg.version} · schema{' '}
                            {pkg.schema_version}
                        </p>
                        <p>
                            {bn ? 'মডিউল' : 'Modules'}: {pkg.modules.join(', ')}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.get(
                                    base + '/packages',
                                    {
                                        package_id: pkg.id,
                                        package_version: pkg.version,
                                        version: configurationVersion,
                                    },
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {bn ? 'প্রভাব দেখুন' : 'Preview impact'}
                        </Button>
                    </article>
                ))}
            </section>
            {preview && (
                <section
                    aria-label={bn ? 'প্যাকেজের প্রভাব' : 'Package impact'}
                    className="space-y-4 rounded-lg border p-4"
                >
                    <h2 className="text-xl font-semibold">
                        {preview.manifest.name[workspace.locale]} · v
                        {preview.manifest.version}
                    </h2>
                    <p>
                        {bn
                            ? 'কনফিগারেশনের বর্তমান ভার্সন'
                            : 'Base configuration version'}
                        : {preview.base_version}
                    </p>
                    <p>
                        {bn ? 'প্রস্তাবিত নাম' : 'Proposed labels'}:{' '}
                        {preview.terminology.lead} /{' '}
                        {preview.terminology.customer}
                    </p>
                    <p>
                        {preview.preserved_override
                            ? bn
                                ? 'তোমার আগে প্রকাশিত নামগুলো রাখা হয়েছে; প্যাকেজ সেগুলো overwrite করবে না।'
                                : 'Existing published terminology overrides are preserved.'
                            : bn
                              ? 'আগে নাম প্রকাশ করা হয়নি; প্রিভিউতে প্যাকেজের default নাম দেখানো হচ্ছে।'
                              : 'No published overrides; preview uses package defaults.'}
                    </p>
                    {preview.blockers.length > 0 ? (
                        <div role="alert">
                            <p>
                                {bn
                                    ? 'সামঞ্জস্যের সমস্যা'
                                    : 'Compatibility blockers'}
                            </p>
                            <ul className="list-inside list-disc">
                                {preview.blockers.map((item) => (
                                    <li key={item}>{item}</li>
                                ))}
                            </ul>
                        </div>
                    ) : (
                        <p>
                            {bn
                                ? 'প্রিভিউর সামঞ্জস্য পরীক্ষা পাস। এটি install-এর অনুমতি বা সম্পূর্ণ clinic software হওয়ার দাবি নয়।'
                                : 'Preview compatibility passed. This does not enable installation or imply a complete clinic product.'}
                        </p>
                    )}
                    <h3 className="font-semibold">
                        {bn ? 'প্রস্তাবিত অতিরিক্ত ফিল্ড' : 'Proposed custom fields'}
                    </h3>
                    <ul className="space-y-3">
                        {preview.manifest.fields.map((field) => (
                            <li key={field.key} className="rounded border p-3">
                                <p>
                                    {field.label[workspace.locale]} ·{' '}
                                    {field.type} ·{' '}
                                    {field.required
                                        ? bn
                                            ? 'আবশ্যক'
                                            : 'Required'
                                        : bn
                                          ? 'ঐচ্ছিক'
                                          : 'Optional'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {field.entity}.{field.key}
                                    {field.options
                                        ? ' · ' + field.options.join(', ')
                                        : ''}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <p className="text-xs break-all text-muted-foreground">
                        SHA-256: {preview.manifest.digest}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {bn
                            ? 'পরের ধাপ: immutable configuration publish/rollback এবং নিরাপদ custom-field data migration। কোনো business record এই প্রিভিউতে বদলায়নি।'
                            : 'Next: immutable configuration publish/rollback and safe custom-value migrations. No business records change in this preview.'}
                    </p>
                </section>
            )}
        </div>
    );
}
Packages.layout = {
    breadcrumbs: [{ title: 'Workspaces', href: '/dashboard' }],
};
