<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Receipt,
    Wallet,
    Landmark,
    Users,
    FileText,
    Settings,
    Building2
} from 'lucide-vue-next'; import AppLogo from './AppLogo.vue';
import { ref, computed } from 'vue'

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Company',
        icon: Building2,
        roles: ['admin'], // or remove if all users can switch
        children: [
            {
                title: 'Switch Company',
                href: '/companies',
            },
        ],
    },
    {
        title: 'Sales',
        icon: Receipt,
        children: [
            { title: 'Invoices', href: '/sales/invoices' },
            { title: 'Payments', href: '/sales/payments' },
        ],
    },
    {
        title: 'Expenses (Bills)',
        href: '/expenses/bills',
        icon: Wallet,
    },
    {
        title: 'Accountings',
        icon: Landmark,
        roles: ['admin'],
        children: [
            { title: 'Chart of Accounts', href: '/accountings/chart-of-accounts' },
            { title: 'Opening Balance', href: '/accountings/opening-balance' },
            { title: 'Posting Periods', href: '/accountings/posting-periods' },
            { title: 'Journal Entries', href: '/accountings/journal-entries' },
            {
                title: 'Accounting Reports', href: '/accountings/accounting-reports',
            },
        ],
    },
    {
        title: 'Customers',
        href: '/customers',
        icon: Users,
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: FileText,
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: Settings,
    },
    {
        title: 'Audit Trails',
        href: '/audit-trails',
        icon: Settings,
    },
];
const search = ref('')

function filterItems(items: NavItem[], q: string): NavItem[] {
    if (!q) return items
    const query = q.toLowerCase()

    function walk(list: NavItem[]): NavItem[] {
        return list
            .map((item) => {
                const titleMatch = (item.title || '').toLowerCase().includes(query)

                // no children
                if (!item.children || !item.children.length) {
                    return titleMatch ? item : null
                }

                // has children (recursive)
                const filteredChildren = walk(item.children as NavItem[])

                if (titleMatch || filteredChildren.length) {
                    return { ...item, children: filteredChildren }
                }

                return null
            })
            .filter(Boolean) as NavItem[]
    }

    return walk(items)
}


const filteredNavItems = computed(() => filterItems(mainNavItems, search.value))

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>

                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="px-3 py-2">
                <input v-model="search" type="text" placeholder="Search menu..."
                    class="w-full rounded-md border px-3 py-2 text-sm bg-white" />
                <div v-if="search && !filteredNavItems.length" class="pt-2 text-xs text-slate-500">
                    No menu items found.
                </div>
            </div>

            <NavMain :items="filteredNavItems" />
        </SidebarContent>


        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
