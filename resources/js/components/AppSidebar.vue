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
import { index as auditTrailsIndex } from '@/routes/audit-trails';
import { index as coaIndex } from '@/routes/coa';
import { index as companiesIndex, select as companiesSelect } from '@/routes/companies';
import { index as customersIndex } from '@/routes/customers';
import { index as expensesBillsIndex } from '@/routes/expenses/bills';
import { index as journalEntriesIndex } from '@/routes/je';
import { create as openingBalanceCreate } from '@/routes/opening-balance';
import { index as reportsIndex } from '@/routes/reports';
import { index as salesInvoicesIndex } from '@/routes/sales/invoices';
import { index as salesPaymentsIndex } from '@/routes/sales/payments';
import { index as settingsIndex } from '@/routes/settings';
import { index as accountingReportsIndex } from '@/routes/accountings/accounting-reports';
import { index as postingPeriodsIndex } from '@/routes/accountings/posting-periods';
import { index as securityUsersIndex } from '@/routes/security/users';
import { index as securityRolesIndex } from '@/routes/security/roles';
import { index as securityPermissionsIndex } from '@/routes/security/permissions';
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
        roles: ['Super Admin', 'Admin'], // or remove if all users can switch
        children: [
            {
                title: 'Switch Company',
                href: companiesSelect(),
            },
            {
                title: 'Companies',
                href: companiesIndex(),
            },
        ],
    },
    {
        title: 'Sales',
        icon: Receipt,
        children: [
            { title: 'Invoices', href: salesInvoicesIndex() },
            { title: 'Payments', href: salesPaymentsIndex() },
        ],
    },
    {
        title: 'Expenses (Bills)',
        href: expensesBillsIndex(),
        icon: Wallet,
    },
    {
        title: 'Accountings',
        icon: Landmark,
        roles: ['Super Admin', 'Admin'],
        children: [
            { title: 'Chart of Accounts', href: coaIndex() },
            { title: 'Opening Balance', href: openingBalanceCreate() },
            { title: 'Posting Periods', href: postingPeriodsIndex() },
            { title: 'Journal Entries', href: journalEntriesIndex() },
            {
                title: 'Accounting Reports', href: accountingReportsIndex(),
            },
        ],
    },
    {
        title: 'Customers',
        href: customersIndex(),
        icon: Users,
    },
    {
        title: 'Reports',
        href: reportsIndex(),
        icon: FileText,
    },
    {
        title: 'Security',
        icon: Settings,
        roles: ['Super Admin', 'Admin'],
        children: [
            { title: 'Users', href: securityUsersIndex() },
            { title: 'Roles', href: securityRolesIndex() },
            { title: 'Permissions', href: securityPermissionsIndex() },
        ],
    },
    {
        title: 'Settings',
        href: settingsIndex(),
        icon: Settings,
    },
    {
        title: 'Audit Trails',
        href: auditTrailsIndex(),
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
