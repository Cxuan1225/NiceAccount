<!-- resources/js/Pages/AuditTrails/Index.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'

type AuditTrailRow = {
    id: number
    user_id: number | null
    user_label: string | null
    screen_name: string | null
    table_name: string
    table_id: string | null
    action: string
    ip_address: string | null
    user_agent: string | null
    created_at: string | null
    old_data: any
    new_data: any
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type Paginated<T> = {
    data: T[]
    links: PaginationLink[]
    total?: number
    from?: number
    to?: number
    per_page?: number
    current_page?: number
    last_page?: number
}

const props = defineProps<{
    auditTrails: Paginated<AuditTrailRow>
    filters: {
        q: string
        action: string | null
        table_name: string | null
        date_from: string | null
        date_to: string | null
    }
    options: {
        tables: string[]
        actions: string[]
    }
}>()

const breadcrumbs = [
    { title: 'Audit Trails', href: '/audit-trails' },
]

const form = reactive({
    q: props.filters.q ?? '',
    action: props.filters.action ?? '',
    table_name: props.filters.table_name ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})

function applyFilters() {
    router.get(
        '/audit-trails',
        {
            q: form.q || undefined,
            action: form.action || undefined,
            table_name: form.table_name || undefined,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

function resetFilters() {
    form.q = ''
    form.action = ''
    form.table_name = ''
    form.date_from = ''
    form.date_to = ''
    applyFilters()
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}

const showing = ref(false)
const selected = ref<AuditTrailRow | null>(null)

function openDetails(row: AuditTrailRow) {
    selected.value = row
    showing.value = true
}

function closeDetails() {
    showing.value = false
    selected.value = null
}

const prettyOld = computed(() => {
    try {
        return JSON.stringify(selected.value?.old_data ?? null, null, 2)
    } catch {
        return String(selected.value?.old_data ?? '')
    }
})

const prettyNew = computed(() => {
    try {
        return JSON.stringify(selected.value?.new_data ?? null, null, 2)
    } catch {
        return String(selected.value?.new_data ?? '')
    }
})

async function copyText(text: string) {
    // Clipboard API + fallback
    try {
        await navigator.clipboard.writeText(text)
        alert('Copied!')
    } catch {
        const ta = document.createElement('textarea')
        ta.value = text
        document.body.appendChild(ta)
        ta.select()
        document.execCommand('copy')
        document.body.removeChild(ta)
        alert('Copied!')
    }
}

function badgeClass(action: string) {
    const a = (action || '').toUpperCase()
    if (a === 'INSERT') return 'bg-green-100 text-green-800'
    if (a === 'UPDATE') return 'bg-blue-100 text-blue-800'
    if (a === 'DELETE') return 'bg-red-100 text-red-800'
    if (a === 'DUPLICATE') return 'bg-purple-100 text-purple-800'
    if (a === 'SYSTEM') return 'bg-gray-100 text-gray-800'
    return 'bg-slate-100 text-slate-800'
}
</script>

<template>

    <Head title="Audit Trails" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Audit Trails</h1>
                <div class="flex gap-2">
                    <button class="text-sm underline" @click="resetFilters">Reset</button>
                </div>
            </div>

            <!-- Filters -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Search</label>
                    <input v-model="form.q" type="text" class="w-full border rounded px-3 py-2 text-sm"
                        placeholder="user, screen, table, id, ip..." @keyup.enter="applyFilters" />
                </div>

                <div>
                    <label class="block text-sm mb-1">Action</label>
                    <select v-model="form.action" class="w-full border rounded px-3 py-2 text-sm"
                        @change="applyFilters">
                        <option value="">All</option>
                        <option v-for="a in props.options.actions" :key="a" :value="a">{{ a }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm mb-1">Table</label>
                    <select v-model="form.table_name" class="w-full border rounded px-3 py-2 text-sm"
                        @change="applyFilters">
                        <option value="">All</option>
                        <option v-for="t in props.options.tables" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button class="border rounded px-3 py-2 text-sm w-full" @click="applyFilters">
                        Apply
                    </button>
                </div>

                <!-- Optional date range row -->
                <div class="md:col-span-5 grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-sm mb-1">From</label>
                        <input v-model="form.date_from" type="date" class="w-full border rounded px-3 py-2 text-sm"
                            @change="applyFilters" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">To</label>
                        <input v-model="form.date_to" type="date" class="w-full border rounded px-3 py-2 text-sm"
                            @change="applyFilters" />
                    </div>
                    <div class="md:col-span-3 flex items-end justify-end">
                        <div class="text-sm text-slate-600">
                            <span v-if="props.auditTrails.from && props.auditTrails.to">
                                Showing {{ props.auditTrails.from }}–{{ props.auditTrails.to }}
                                <span v-if="props.auditTrails.total">of {{ props.auditTrails.total }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">User</th>
                            <th class="px-3 py-2">Action</th>
                            <th class="px-3 py-2">Screen</th>
                            <th class="px-3 py-2">Table</th>
                            <th class="px-3 py-2">Record ID</th>
                            <th class="px-3 py-2">IP</th>
                            <th class="px-3 py-2 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.auditTrails.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="8">
                                No audit trails found.
                            </td>
                        </tr>

                        <tr v-for="row in props.auditTrails.data" :key="row.id" class="border-t hover:bg-slate-50">
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.created_at || '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ row.user_label || (row.user_id ? `User #${row.user_id}` :
                                    '-') }}</div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                    :class="badgeClass(row.action)">
                                    {{ row.action }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ row.screen_name || '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.table_name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.table_id || '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ row.ip_address || '-' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button class="text-blue-600 hover:underline" @click="openDetails(row)">
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="props.auditTrails.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.auditTrails.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>

            <!-- Details Modal -->
            <div v-if="showing" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/40" @click="closeDetails"></div>

                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="w-full max-w-4xl bg-white rounded shadow-lg overflow-hidden">
                        <div class="px-4 py-3 border-b flex items-center justify-between">
                            <div>
                                <div class="font-semibold">Audit Details</div>
                                <div class="text-xs text-slate-600">
                                    ID: {{ selected?.id }} • {{ selected?.created_at || '-' }}
                                </div>
                            </div>
                            <button class="text-sm underline" @click="closeDetails">Close</button>
                        </div>

                        <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="text-sm">
                                <div class="text-slate-500 text-xs">User</div>
                                <div class="font-medium">{{ selected?.user_label || (selected?.user_id ? `User
                                    #${selected?.user_id}` : '-') }}</div>
                            </div>
                            <div class="text-sm">
                                <div class="text-slate-500 text-xs">Action</div>
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        :class="badgeClass(selected?.action || '')">
                                        {{ selected?.action }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-sm">
                                <div class="text-slate-500 text-xs">Screen</div>
                                <div>{{ selected?.screen_name || '-' }}</div>
                            </div>
                            <div class="text-sm">
                                <div class="text-slate-500 text-xs">Table / Record</div>
                                <div>{{ selected?.table_name }} / {{ selected?.table_id || '-' }}</div>
                            </div>
                            <div class="text-sm">
                                <div class="text-slate-500 text-xs">IP</div>
                                <div>{{ selected?.ip_address || '-' }}</div>
                            </div>
                            <div class="text-sm">
                                <div class="text-slate-500 text-xs">User Agent</div>
                                <div class="truncate" :title="selected?.user_agent || ''">{{ selected?.user_agent || '-'
                                }}</div>
                            </div>
                        </div>

                        <div class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="border rounded overflow-hidden">
                                <div class="px-3 py-2 bg-slate-50 border-b flex items-center justify-between">
                                    <div class="text-sm font-medium">Old Data</div>
                                    <button class="text-sm underline" @click="copyText(prettyOld)">Copy</button>
                                </div>
                                <pre class="p-3 text-xs overflow-auto max-h-80">{{ prettyOld }}</pre>
                            </div>

                            <div class="border rounded overflow-hidden">
                                <div class="px-3 py-2 bg-slate-50 border-b flex items-center justify-between">
                                    <div class="text-sm font-medium">New Data</div>
                                    <button class="text-sm underline" @click="copyText(prettyNew)">Copy</button>
                                </div>
                                <pre class="p-3 text-xs overflow-auto max-h-80">{{ prettyNew }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Modal -->
        </div>
    </AppLayout>
</template>
