<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { index as permissionsIndex } from '@/routes/security/permissions'
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

type PermissionRow = {
    id: number
    name: string
    label: string | null
    category: string | null
    description: string | null
    is_active: boolean
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: PaginationLink[] }

const props = defineProps<{
    permissions: Paginated<PermissionRow>
    filters: { q: string }
}>()

const form = reactive({ q: props.filters.q ?? '' })

function apply() {
    router.get(permissionsIndex().url, { q: form.q || undefined }, { preserveState: true, replace: true })
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}
</script>

<template>
    <Head title="Permissions" />

    <AppLayout>
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Permissions</h1>
            </div>

            <div class="mt-4 flex gap-2 max-w-xl">
                <input v-model="form.q" class="w-full border rounded px-3 py-2 text-sm"
                    placeholder="Search name or label" @keyup.enter="apply" />
                <button class="border rounded px-3 py-2 text-sm" @click="apply">Search</button>
            </div>

            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Label</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.permissions.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="4">No permissions found.</td>
                        </tr>
                        <tr v-for="p in props.permissions.data" :key="p.id" class="border-t">
                            <td class="px-3 py-2 font-mono">{{ p.name }}</td>
                            <td class="px-3 py-2">{{ p.label || '-' }}</td>
                            <td class="px-3 py-2">{{ p.category || '-' }}</td>
                            <td class="px-3 py-2">{{ p.is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.permissions.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.permissions.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>
        </div>
    </AppLayout>
</template>
