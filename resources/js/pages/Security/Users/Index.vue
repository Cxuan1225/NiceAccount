<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as usersCreate, destroy as usersDestroy, edit as usersEdit, index as usersIndex } from '@/routes/security/users'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

type CompanyOption = { id: number; name: string }
type UserRow = {
    id: number
    name: string
    email: string
    company_id: number | null
    company: { id: number; name: string } | null
    roles: string[]
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: PaginationLink[] }

const props = defineProps<{
    users: Paginated<UserRow>
    filters: { q: string; company_id: number | null }
    companies: CompanyOption[]
}>()

const form = reactive({
    q: props.filters.q ?? '',
    company_id: props.filters.company_id ?? null,
})

function apply() {
    router.get(
        usersIndex().url,
        {
            q: form.q || undefined,
            company_id: form.company_id || undefined,
        },
        { preserveState: true, replace: true }
    )
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}

function destroyUser(id: number) {
    if (!confirm('Delete this user?')) return
    router.delete(usersDestroy(id).url, { preserveScroll: true })
}
</script>

<template>
    <Head title="Users" />

    <AppLayout>
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Users</h1>
                <Link :href="usersCreate().url" class="text-sm underline">New User</Link>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 max-w-3xl">
                <input v-model="form.q" class="flex-1 min-w-[220px] border rounded px-3 py-2 text-sm"
                    placeholder="Search name or email" @keyup.enter="apply" />
                <select v-if="props.companies.length" v-model="form.company_id"
                    class="border rounded px-3 py-2 text-sm">
                    <option :value="null">All companies</option>
                    <option v-for="c in props.companies" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
                <button class="border rounded px-3 py-2 text-sm" @click="apply">Search</button>
            </div>

            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Email</th>
                            <th class="px-3 py-2">Company</th>
                            <th class="px-3 py-2">Roles</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.users.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="5">No users found.</td>
                        </tr>
                        <tr v-for="u in props.users.data" :key="u.id" class="border-t hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ u.name }}</td>
                            <td class="px-3 py-2">{{ u.email }}</td>
                            <td class="px-3 py-2">{{ u.company?.name || '-' }}</td>
                            <td class="px-3 py-2">{{ u.roles.join(', ') || '-' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <Link :href="usersEdit(u.id).url" class="text-blue-600 hover:underline mr-3">Edit</Link>
                                <button class="text-red-600 hover:underline" @click="destroyUser(u.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.users.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.users.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>
        </div>
    </AppLayout>
</template>
