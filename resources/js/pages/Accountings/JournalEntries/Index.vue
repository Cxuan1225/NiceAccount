<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { notyf, confirmReverse } from '@/utils/alerts'

type Row = {
    id: number
    entry_date: string | null
    reference_no: string | null
    memo: string | null
    status: string
    source_type: string | null
    created_at: string | null
}

type Line = {
    id: number
    account_id: number
    account_code: string | null
    account_name: string | null
    memo: string | null
    debit: number
    credit: number
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginated<T> = { data: T[]; links: PaginationLink[]; from?: number; to?: number; total?: number }

type JournalDetailPayload = {
    entry: Row
    lines: Line[]
    total: { debit: number; credit: number; balanced: boolean }
}

type LedgerRow = {
    date: string | null
    reference: string | null
    description: string | null
    debit: number
    credit: number
    balance: number
}

type LedgerPayload = {
    account: { id: number; code?: string | null; name?: string | null }
    ledger: LedgerRow[]
    closing_balance?: number
}

const props = defineProps<{
    entries: Paginated<Row>
    filters: { q: string }
}>()

const breadcrumbs = [
    { title: 'Accountings', href: '#' },
    { title: 'Journal Entries', href: '/accountings/journal-entries' },
]

// -------------------------
// Filters / Pagination
// -------------------------
const form = reactive({ q: props.filters.q ?? '' })

function apply() {
    router.get('/accountings/journal-entries', { q: form.q || undefined }, { preserveState: true, replace: true })
}

function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
}

// -------------------------
// Journal Detail Modal
// -------------------------
const showDetailModal = ref(false)
const detailLoading = ref(false)
const detail = ref<JournalDetailPayload | null>(null)

const isPosted = computed(() => detail.value?.entry?.status === 'POSTED')

async function openDetail(id: number) {
    showDetailModal.value = true
    detailLoading.value = true
    detail.value = null

    try {
        const res = await fetch(`/accountings/journal-entries/${id}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })

        if (!res.ok) {
            const data = await safeJson(res)
            throw new Error(data?.message || 'Failed to load journal detail')
        }

        detail.value = (await res.json()) as JournalDetailPayload
    } catch (e: any) {
        showDetailModal.value = false
        notyf.error(e?.message || 'Failed to load journal entry detail.')
    } finally {
        detailLoading.value = false
    }
}

function closeDetail() {
    showDetailModal.value = false
    detail.value = null

    // optional: also close ledger if it’s open
    closeLedger()
}

function isDebit(l: Line) {
    return !!l.debit && l.debit > 0
}
function isCredit(l: Line) {
    return !!l.credit && l.credit > 0
}

async function reverseEntry() {
    if (!detail.value?.entry?.id) return

    const result = await confirmReverse('A reversing journal entry will be created and posted automatically.')
    if (!result.isConfirmed) return

    try {
        const res = await fetch(`/accountings/journal-entries/${detail.value.entry.id}/reverse`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({}),
        })

        const data = await safeJson(res)
        if (!res.ok) throw new Error(data?.message || 'Failed to reverse journal entry')

        notyf.success('Journal entry reversed successfully')

        closeDetail()
        router.reload({ preserveScroll: true, preserveState: true })
    } catch (e: any) {
        notyf.error(e?.message || 'Failed to reverse journal entry')
    }
}

// -------------------------
// Ledger Modal
// -------------------------
const showLedgerModal = ref(false)
const ledgerLoading = ref(false)
const ledgerData = ref<LedgerPayload | null>(null)

async function openLedger(accountId: number) {
    showLedgerModal.value = true
    ledgerLoading.value = true
    ledgerData.value = null

    try {
        const res = await fetch(`/accountings/ledger/${accountId}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })

        if (!res.ok) {
            const data = await safeJson(res)
            throw new Error(data?.message || 'Failed to load ledger')
        }

        ledgerData.value = (await res.json()) as LedgerPayload
    } catch (e: any) {
        notyf.error(e?.message || 'Failed to load ledger')
        showLedgerModal.value = false
    } finally {
        ledgerLoading.value = false
    }
}

function closeLedger() {
    showLedgerModal.value = false
    ledgerData.value = null
}

// -------------------------
// Helpers
// -------------------------
async function safeJson(res: Response): Promise<any> {
    try {
        return await res.json()
    } catch {
        return null
    }
}

// -------------------------
// Keyboard UX (ESC closes top-most modal)
// -------------------------
function onKeydown(e: KeyboardEvent) {
    if (e.key !== 'Escape') return

    // Close the top-most modal first
    if (showLedgerModal.value) {
        closeLedger()
        return
    }
    if (showDetailModal.value) {
        closeDetail()
        return
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>

    <Head title="Journal Entries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Journal Entries</h1>
                <Link href="/accountings/journal-entries/create" class="text-sm underline">New Journal Entry</Link>
            </div>

            <div class="mt-4 flex gap-2 max-w-xl">
                <input v-model="form.q" class="w-full border rounded px-3 py-2 text-sm"
                    placeholder="Search reference, memo, source..." @keyup.enter="apply" />
                <button class="border rounded px-3 py-2 text-sm" @click="apply">Search</button>
            </div>

            <div class="mt-6 overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Reference</th>
                            <th class="px-3 py-2">Memo</th>
                            <th class="px-3 py-2">Source</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!props.entries.data.length">
                            <td class="px-3 py-6 text-center text-slate-500" colspan="5">No journal entries found.</td>
                        </tr>

                        <tr v-for="e in props.entries.data" :key="e.id"
                            class="border-t hover:bg-slate-50 cursor-pointer" @click="openDetail(e.id)">
                            <td class="px-3 py-2 whitespace-nowrap">{{ e.entry_date || '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ e.reference_no || '-' }}</td>
                            <td class="px-3 py-2">{{ e.memo || '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ e.source_type || '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ e.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.entries.links?.length" class="mt-4 flex flex-wrap gap-1">
                <button v-for="(l, idx) in props.entries.links" :key="idx" class="border rounded px-3 py-1 text-sm"
                    :class="l.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white'" :disabled="!l.url"
                    v-html="l.label" @click="goTo(l.url)" />
            </div>
        </div>

        <!-- ========================= -->
        <!-- Journal Detail Modal -->
        <!-- ========================= -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/40" @click="closeDetail"></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-4xl bg-white rounded-lg shadow-lg overflow-hidden" @click.stop>
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="font-semibold">
                            Journal Entry Detail
                            <span v-if="detail?.entry?.reference_no" class="text-slate-500 font-normal">
                                — {{ detail.entry.reference_no }}
                            </span>

                            <span v-if="isPosted"
                                class="ml-2 inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-slate-200 text-slate-700">
                                🔒 POSTED
                            </span>
                        </div>

                        <button class="text-sm underline" @click="closeDetail">Close</button>
                    </div>

                    <div class="p-4">
                        <div v-if="detailLoading" class="py-10 text-center text-slate-500">Loading...</div>

                        <div v-else-if="detail" class="space-y-4">
                            <!-- Header -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div><span class="text-slate-500">Date:</span> {{ detail.entry.entry_date || '-' }}
                                </div>
                                <div><span class="text-slate-500">Status:</span> {{ detail.entry.status }}</div>

                                <div class="md:col-span-2">
                                    <span class="text-slate-500">Memo:</span> {{ detail.entry.memo || '-' }}
                                </div>

                                <div><span class="text-slate-500">Source:</span> {{ detail.entry.source_type || '-' }}
                                </div>
                                <div><span class="text-slate-500">Created:</span> {{ detail.entry.created_at || '-' }}
                                </div>
                            </div>

                            <!-- Lines -->
                            <div class="border rounded overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr class="text-left">
                                            <th class="px-3 py-2">Account</th>
                                            <th class="px-3 py-2">Line Memo</th>
                                            <th class="px-3 py-2 text-right">Debit (RM)</th>
                                            <th class="px-3 py-2 text-right">Credit (RM)</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr v-for="l in detail.lines" :key="l.id" class="border-t">
                                            <td class="px-3 py-2">
                                                <button class="text-blue-600 underline"
                                                    @click="openLedger(l.account_id)">
                                                    {{ l.account_code || '-' }} - {{ l.account_name || '-' }}
                                                </button>
                                            </td>

                                            <td class="px-3 py-2">{{ l.memo || '-' }}</td>

                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <span v-if="isDebit(l)"
                                                    class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">
                                                    DR {{ l.debit.toFixed(2) }}
                                                </span>
                                            </td>

                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <span v-if="isCredit(l)"
                                                    class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                                                    CR {{ l.credit.toFixed(2) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tfoot>
                                        <tr class="border-t bg-slate-50 font-semibold">
                                            <td class="px-3 py-2" colspan="2">Total (RM)</td>
                                            <td class="px-3 py-2 text-right">{{ detail.total.debit.toFixed(2) }}</td>
                                            <td class="px-3 py-2 text-right">{{ detail.total.credit.toFixed(2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div v-if="!detail.total.balanced" class="text-sm text-red-600">
                                Warning: This journal entry is not balanced.
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t flex justify-end gap-2">
                        <Link v-if="detail?.entry?.id && !isPosted"
                            :href="`/accountings/journal-entries/${detail.entry.id}/edit`"
                            class="border rounded px-3 py-2 text-sm">
                            Edit
                        </Link>

                        <span v-else class="text-sm text-slate-400 cursor-not-allowed self-center">
                            Posted entries cannot be edited
                        </span>

                        <button v-if="detail?.entry?.status === 'POSTED'"
                            class="border rounded px-3 py-2 text-sm text-red-600 border-red-300 hover:bg-red-50"
                            @click="reverseEntry">
                            Reverse
                        </button>

                        <button class="border rounded px-3 py-2 text-sm" @click="closeDetail">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================= -->
        <!-- Ledger Modal (stack above) -->
        <!-- ========================= -->
        <div v-if="showLedgerModal" class="fixed inset-0 z-[60]">
            <div class="absolute inset-0 bg-black/40" @click="closeLedger"></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-5xl bg-white rounded-lg shadow-lg overflow-hidden" @click.stop>
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="font-semibold">
                            Ledger
                            <span v-if="ledgerData?.account" class="text-slate-500 font-normal">
                                — {{ ledgerData?.account?.code || '' }} {{ ledgerData?.account?.name || '' }}
                            </span>
                        </div>

                        <button class="text-sm underline" @click="closeLedger">Close</button>
                    </div>

                    <div class="p-4">
                        <div v-if="ledgerLoading" class="py-10 text-center text-slate-500">Loading...</div>

                        <div v-else-if="ledgerData" class="border rounded overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr class="text-left">
                                        <th class="px-3 py-2">Date</th>
                                        <th class="px-3 py-2">Ref</th>
                                        <th class="px-3 py-2">Description</th>
                                        <th class="px-3 py-2 text-right">Debit</th>
                                        <th class="px-3 py-2 text-right">Credit</th>
                                        <th class="px-3 py-2 text-right">Balance</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-if="!ledgerData.ledger?.length">
                                        <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                            No ledger records.
                                        </td>
                                    </tr>

                                    <tr v-for="(r, i) in ledgerData.ledger" :key="i" class="border-t">
                                        <td class="px-3 py-2 whitespace-nowrap">{{ r.date || '-' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ r.reference || '-' }}</td>
                                        <td class="px-3 py-2">{{ r.description || '-' }}</td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            {{ r.debit ? r.debit.toFixed(2) : '' }}
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            {{ r.credit ? r.credit.toFixed(2) : '' }}
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            {{ r.balance.toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="py-10 text-center text-slate-500">
                            Unable to load ledger.
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t flex justify-end gap-2">
                        <button class="border rounded px-3 py-2 text-sm" @click="closeLedger">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
