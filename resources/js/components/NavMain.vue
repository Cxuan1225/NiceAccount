<script setup lang="ts">
import { computed, reactive } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();

/**
 * Read roles from Inertia shared props:
 * page.props.auth.roles = ['Super Admin', 'Admin', 'Staff', ...]
 */
const userRoles = computed<string[]>(() => {
    const roles = (page.props as any)?.auth?.roles;
    return Array.isArray(roles) ? roles : [];
});

function canSee(item: NavItem): boolean {
    if (!item.roles || item.roles.length === 0) return true;

    // show if any role matches
    for (let i = 0; i < item.roles.length; i++) {
        if (userRoles.value.indexOf(item.roles[i]) !== -1) return true;
    }
    return false;
}

function isGroup(item: NavItem): boolean {
    return !!(item.children && item.children.length);
}

function groupHasActiveChild(item: NavItem): boolean {
    if (!item.children) return false;

    for (let i = 0; i < item.children.length; i++) {
        const child = item.children[i];
        if (!canSee(child)) continue;
        if (child.href && urlIsActive(child.href, page.url)) return true;
    }
    return false;
}

/**
 * Collapsible state (default: open if it contains active child, otherwise open)
 */
const openGroups = reactive<Record<string, boolean>>({});

function isOpen(title: string): boolean {
    if (typeof openGroups[title] === 'undefined') {
        // default open: true (or open when active child exists)
        openGroups[title] = true;
    }
    return openGroups[title];
}

function toggle(title: string) {
    openGroups[title] = !isOpen(title);
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>

        <SidebarMenu>
            <template v-for="item in props.items" :key="item.title">
                <template v-if="canSee(item)">
                    <!-- GROUP (collapsible) -->
                    <SidebarMenuItem v-if="isGroup(item)">
                        <SidebarMenuButton :is-active="groupHasActiveChild(item)" :tooltip="item.title"
                            @click="toggle(item.title)">
                            <component v-if="item.icon" :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <span class="ml-auto select-none">
                                {{ isOpen(item.title) ? '−' : '+' }}
                            </span>
                        </SidebarMenuButton>

                        <div v-show="isOpen(item.title)" class="mt-1 space-y-1 pl-6">
                            <template v-for="child in item.children" :key="child.title">
                                <template v-if="canSee(child)">
                                    <SidebarMenuItem>
                                        <SidebarMenuButton as-child
                                            :is-active="child.href ? urlIsActive(child.href, page.url) : false"
                                            :tooltip="child.title">
                                            <Link :href="child.href || '#'">
                                                <component v-if="child.icon" :is="child.icon" />
                                                <span>{{ child.title }}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                </template>
                            </template>
                        </div>
                    </SidebarMenuItem>

                    <!-- SINGLE LINK -->
                    <SidebarMenuItem v-else>
                        <SidebarMenuButton as-child :is-active="item.href ? urlIsActive(item.href, page.url) : false"
                            :tooltip="item.title">
                            <Link :href="item.href || '#'">
                                <component v-if="item.icon" :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </template>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
