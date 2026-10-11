import { Link } from '@inertiajs/react';
import type { Pagination } from '@/types/platform';

export function RecordPagination({
    links,
}: {
    links: Pagination<unknown>['links'];
}) {
    return (
        <nav aria-label="Pagination" className="flex flex-wrap gap-3 p-4">
            {links.map((link, index) => {
                const label = link.label
                    .replace(/&laquo;/g, '‹')
                    .replace(/&raquo;/g, '›')
                    .replace(/<[^>]+>/g, '');
                return link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        className={link.active ? 'font-bold underline' : ''}
                    >
                        {label}
                    </Link>
                ) : (
                    <span key={index} className="text-muted-foreground">
                        {label}
                    </span>
                );
            })}
        </nav>
    );
}
