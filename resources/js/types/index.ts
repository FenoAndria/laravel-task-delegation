import type { LucideIcon } from 'lucide-vue-next';

export type OrganizationRole = 'owner' | 'admin' | 'member';

export interface CurrentOrganization {
    id: number;
    name: string;
    slug: string;
    role: OrganizationRole | null;
    can_manage: boolean;
}

export interface OrganizationSummary {
    id: number;
    name: string;
}

export interface Auth {
    user: User;
    organization: CurrentOrganization | null;
    organizations: OrganizationSummary[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    badge?: number;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    unreadNotifications: number;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
