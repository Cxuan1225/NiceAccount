<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as coaCreate, destroy as coaDestroy, edit as coaEdit, index as coaIndex } from '@/routes/coa'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { confirmDelete, notyf } from '@/utils/alerts'

type CoaRow = {
    id: number
    account_code: string
    name: string
    type: string
    parent_id: number | null
    is_active: boolean
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: PaginationLink[]; from?: number; to?: number; total?: number }

const props = defineProps<{
    accounts: Paginated<CoaRow>
    filters: { q: string; type: string | null }
    types: string[]
}>()

const breadcrumbs = [{ title: 'Accountings', href: '#' }, { title: 'Chart of Accounts', href: coaIndex().url }]

const form = reactive({
    q: props.filters.q ?? '',
    type: props.filters.type ?? '',
})

function apply() {
    router.get(coaIndex().url, { q: form.q || undefined, type: form.type || undefined }, { preserveState: true, replace: true })
}

function reset() {
    form.q = ''
    form.type = ''
    apply()
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}

function destroyAccount(id: number) {
    confirmDelete('Deleting an account may affect reports if it is used.').then((r) => {
        if (!r.isConfirmed) return
        router.delete(coaDestroy(id).url, {
            preserveScroll: true,
            onSuccess: () => notyf.success('Account deleted'),
            onError: () => notyf.error('Failed to delete account'),
        })
    })
}
</script>

<template>

    <Head title="Chart of Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Chart of Accounts</h1>
                <Link :href="coaCreate().url" class="text-sm underline">New Account</Link>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Search</label>
                    <input v-model="form.q" class="w-full border rounded px-3 py-2 text-sm" placeholder="code or name"
                        @keyup.enter="apply" />
                </div>

                <div>
                    <label class="block text-sm mb-1">Type</label>
                    <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm" @change="apply">
                        <option value="">All</option>
                        <option v-for="t in props.types" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button class="border rounded px-3 py-2 text-sm w-full" @click="apply">Apply</button>
                    <button class="border rounded px-3 py-2 text-sm" @click="reset">Reset</button>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Active</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.accounts.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="5">No accounts found.</td>
                        </tr>

                        <tr v-for="a in props.accounts.data" :key="a.id" class="border-t hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium whitespace-nowrap">{{ a.account_code }}</td>
                            <td class="px-3 py-2">{{ a.name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ a.type }}</td>
                            <td class="px-3 py-2">{{ a.is_active ? 'Yes' : 'No' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <Link :href="coaEdit(a.id).url"
                                    class="text-blue-600 hover:underline mr-3">Edit</Link>
                                <button class="text-red-600 hover:underline"
                                    @click="destroyAccount(a.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.accounts.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.accounts.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>
        </div>
    </AppLayout>
</template>
