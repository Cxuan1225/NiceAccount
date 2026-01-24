<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as companiesCreate, destroy as companiesDestroy, edit as companiesEdit, index as companiesIndex } from '@/routes/companies'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

type Company = {
    id: number
    code: string
    name: string
    base_currency: string
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: PaginationLink[] }

const props = defineProps<{
    companies: Paginated<Company>
    filters: { q: string }
}>()

const form = reactive({
    q: props.filters.q ?? '',
})

function apply() {
    router.get(
        companiesIndex().url,
        { q: form.q || undefined },
        { preserveState: true, replace: true }
    )
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}

function destroyCompany(id: number) {
    if (!confirm('Delete this company?')) return
    router.delete(companiesDestroy(id).url, { preserveScroll: true })
}
</script>

<template>
    <Head title="Companies" />

    <AppLayout>
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Companies</h1>
                <Link :href="companiesCreate().url" class="text-sm underline">New Company</Link>
            </div>

            <div class="mt-4 flex gap-2 max-w-xl">
                <input v-model="form.q" class="w-full border rounded px-3 py-2 text-sm"
                    placeholder="Search name or code" @keyup.enter="apply" />
                <button class="border rounded px-3 py-2 text-sm" @click="apply">Search</button>
            </div>

            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Currency</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.companies.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="4">No companies found.</td>
                        </tr>

                        <tr v-for="c in props.companies.data" :key="c.id" class="border-t hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium">{{ c.code || '-' }}</td>
                            <td class="px-3 py-2">{{ c.name }}</td>
                            <td class="px-3 py-2">{{ c.base_currency }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <Link :href="companiesEdit(c.id).url" class="text-blue-600 hover:underline mr-3">Edit</Link>
                                <button class="text-red-600 hover:underline" @click="destroyCompany(c.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.companies.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.companies.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>
        </div>
    </AppLayout>
</template>
