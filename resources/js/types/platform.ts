export type Workspace = {
    id: string;
    name: string;
    industry: string;
    locale: 'en' | 'bn';
    currency: string;
    timezone: string;
};
export type Lead = {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    source: string;
    status: string;
    notes: string | null;
    version: number;
    customer_exists?: boolean;
};
export type Customer = Omit<Lead, 'status' | 'notes' | 'version'> & {
    lead_id: string;
};
export type LeadActivity = {
    id: string;
    kind: string;
    body: string | null;
    created_at: string;
    metadata: {
        fields?: string[];
        from?: string;
        to?: string;
        customer_id?: string;
    };
};
export type Pagination<T> = {
    data: T[];
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};
