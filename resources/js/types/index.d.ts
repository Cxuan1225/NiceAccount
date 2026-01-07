import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

type NavBase = {
    title: string;
    icon?: LucideIcon;
    isActive?: boolean;
    roles?: string[];
};

export type NavItem =
    | (NavBase & {
          href: NonNullable<InertiaLinkProps['href']>;
          children?: never;
      })
    | (NavBase & {
          href?: never;
          children: NavItem[];
      });

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

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
