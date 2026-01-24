<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as rolesCreate, destroy as rolesDestroy, edit as rolesEdit, index as rolesIndex } from '@/routes/security/roles'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

type RoleRow = {
    id: number
    name: string
    permissions: string[]
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: PaginationLink[] }

const props = defineProps<{
    roles: Paginated<RoleRow>
    filters: { q: string }
}>()

const form = reactive({ q: props.filters.q ?? '' })

function apply() {
    router.get(rolesIndex().url, { q: form.q || undefined }, { preserveState: true, replace: true })
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}

function destroyRole(id: number) {
    if (!confirm('Delete this role?')) return
    router.delete(rolesDestroy(id).url, { preserveScroll: true })
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout>
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Roles</h1>
                <Link :href="rolesCreate().url" class="text-sm underline">New Role</Link>
            </div>

            <div class="mt-4 flex gap-2 max-w-xl">
                <input v-model="form.q" class="w-full border rounded px-3 py-2 text-sm"
                    placeholder="Search role name" @keyup.enter="apply" />
                <button class="border rounded px-3 py-2 text-sm" @click="apply">Search</button>
            </div>

            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Permissions</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.roles.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="3">No roles found.</td>
                        </tr>
                        <tr v-for="r in props.roles.data" :key="r.id" class="border-t hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ r.name }}</td>
                            <td class="px-3 py-2">{{ r.permissions.length }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <Link :href="rolesEdit(r.id).url" class="text-blue-600 hover:underline mr-3">Edit</Link>
                                <button class="text-red-600 hover:underline" @click="destroyRole(r.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.roles.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.roles.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>
        </div>
    </AppLayout>
</template>
