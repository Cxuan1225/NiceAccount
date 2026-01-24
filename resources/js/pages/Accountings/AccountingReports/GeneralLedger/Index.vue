<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { index as balanceSheetIndex } from '@/routes/accountings/accounting-reports/balance-sheet'
import { index as generalLedgerIndex } from '@/routes/accountings/accounting-reports/general-ledger'
import { index as profitLossIndex } from '@/routes/accountings/accounting-reports/profit-loss'
import { index as trialBalanceIndex } from '@/routes/accountings/accounting-reports/trial-balance'
import ReportHeader from '@/components/accounting/ReportHeader.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive, ref, onMounted, onBeforeUnmount } from 'vue'

type Tab = { label: string; href: string; activePrefix: string }

type Account = {
    id: number
    account_code: string
    name: string
    type: 'ASSET' | 'LIABILITY' | 'EQUITY' | 'INCOME' | 'EXPENSE'
    parent_id: number | null
    is_active: boolean
}

type SelectedAccount = {
    id: number
    account_code: string
    name: string
    type: Account['type']
} | null

type Row = {
    entry_date: string
    reference_no: string | null
    memo: string | null
    source_type: string | null
    source_id: number | null
    line_description: string | null
    debit: number
    credit: number
    running_balance: number
    journal_entry_id: number
    line_id: number
}

type Filters = {
    from: string | null
    to: string | null
    status: string | null
    status_label?: string | null
    show_zero: boolean
    account_id: string | null
}

type Opening = { debit: number; credit: number; balance: number }
type Totals = { periodDebit: number; periodCredit: number; closingBalance: number }

const props = defineProps<{
    filters: Filters
    accounts: Account[]
    selectedAccount: SelectedAccount
    opening: Opening
    rows: Row[]
    totals: Totals
    exportPdfHref?: string
    exportExcelHref?: string
}>()

const isLoading = ref(false)
let unsubs: Array<() => void> = []

onMounted(() => {
    try {
        const offStart = router.on('start', () => (isLoading.value = true))
        const offFinish = router.on('finish', () => (isLoading.value = false))
        const offError = router.on('error', () => (isLoading.value = false))
        unsubs = [offStart, offFinish, offError].filter(Boolean) as Array<() => void>
    } catch {
        isLoading.value = false
    }
})

onBeforeUnmount(() => {
    for (let i = 0; i < unsubs.length; i++) {
        try { unsubs[i]() } catch { }
    }
})

const tabs: Tab[] = [
    { label: 'Trial Balance', href: trialBalanceIndex().url, activePrefix: trialBalanceIndex().url },
    { label: 'Profit & Loss', href: profitLossIndex().url, activePrefix: profitLossIndex().url },
    { label: 'Balance Sheet', href: balanceSheetIndex().url, activePrefix: balanceSheetIndex().url },
    { label: 'General Ledger', href: generalLedgerIndex().url, activePrefix: generalLedgerIndex().url },
]

const form = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    status: props.filters.status ?? 'posted',
    show_zero: !!props.filters.show_zero,
    account_id:
        props.filters.account_id ??
        (props.selectedAccount?.id ? String(props.selectedAccount.id) : ''),
})

function apply() {
    router.get(
        generalLedgerIndex().url,
        {
            from: form.from || null,
            to: form.to || null,
            status: form.status || null,
            show_zero: form.show_zero ? 1 : 0,
            account_id: form.account_id || null,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    )
}

function reset() {
    form.from = ''
    form.to = ''
    form.status = 'posted'
    form.show_zero = false
    // keep selected account as-is (or reset to first)
    apply()
}

function money(n: number) {
    const v = Math.abs(n || 0) < 0.000001 ? 0 : (n || 0)
    return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const periodDebit = computed(() => props.totals?.periodDebit || 0)
const periodCredit = computed(() => props.totals?.periodCredit || 0)
const closingBalance = computed(() => props.totals?.closingBalance || 0)
</script>

<template>

    <Head title="General Ledger" />

    <AppLayout>
        <div class="px-6 py-4">
            <ReportHeader title="Accounting Reports" subtitle="General Ledger" :tabs="tabs"
                :status="props.filters.status" :isLoading="isLoading" :exportPdfHref="exportPdfHref"
                :exportExcelHref="exportExcelHref" />

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3 mb-4">
                <select v-model="form.account_id" class="h-9 border rounded px-2 text-sm w-full">
                    <option value="">-- Select Account --</option>
                    <option v-for="a in props.accounts" :key="a.id" :value="String(a.id)">
                        {{ a.account_code }} — {{ a.name }}
                    </option>
                </select>


                <div>
                    <label class="block text-xs text-slate-500 mb-1">From</label>
                    <input v-model="form.from" type="date" class="h-9 border rounded px-2 text-sm" />
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">To</label>
                    <input v-model="form.to" type="date" class="h-9 border rounded px-2 text-sm" />
                </div>

                <div>
                    <label class="block text-xs text-slate-500 mb-1">Status</label>
                    <select v-model="form.status" class="h-9 border rounded px-2 text-sm">
                        <option value="posted">Posted</option>
                        <option value="draft">Draft</option>
                        <option value="void">Void</option>
                    </select>
                </div>

                <label class="flex items-center gap-2 h-9 text-sm">
                    <input v-model="form.show_zero" type="checkbox" />
                    Show zero activity
                </label>

                <div class="flex gap-2">
                    <button type="button" @click="apply" :disabled="isLoading"
                        class="h-9 px-4 text-sm border rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        Apply
                    </button>

                    <button type="button" @click="reset" :disabled="isLoading"
                        class="h-9 px-4 text-sm border rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        Reset
                    </button>
                </div>
            </div>

            <!-- Summary -->
            <div class="flex flex-wrap gap-6 mb-4 text-sm">
                <div class="min-w-[220px]">
                    <div class="text-slate-500">Opening Balance</div>
                    <div class="font-semibold tabular-nums">{{ money(props.opening.balance) }}</div>
                </div>

                <div class="min-w-[160px]">
                    <div class="text-slate-500">Period Debit</div>
                    <div class="font-semibold tabular-nums">{{ money(periodDebit) }}</div>
                </div>

                <div class="min-w-[160px]">
                    <div class="text-slate-500">Period Credit</div>
                    <div class="font-semibold tabular-nums">{{ money(periodCredit) }}</div>
                </div>

                <div class="min-w-[200px]">
                    <div class="text-slate-500">Closing Balance</div>
                    <div class="font-semibold tabular-nums">{{ money(closingBalance) }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="border rounded max-h-[70vh] overflow-auto">
                <div class="bg-slate-50 px-4 py-3 font-semibold flex items-center justify-between">
                    <div>
                        <div>Ledger</div>
                        <div v-if="props.selectedAccount" class="text-xs text-slate-500 font-normal mt-1">
                            {{ props.selectedAccount.account_code }} — {{ props.selectedAccount.name }} ({{
                                props.selectedAccount.type }})
                        </div>
                    </div>
                    <div class="text-xs text-slate-500 font-normal">
                        Rows: {{ props.rows.length }}
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-2 px-4 py-2 text-xs text-slate-500 border-t bg-white sticky top-0">
                    <div class="col-span-2">Date</div>
                    <div class="col-span-2">Ref</div>
                    <div class="col-span-4">Description</div>
                    <div class="col-span-1 text-right">Debit</div>
                    <div class="col-span-1 text-right">Credit</div>
                    <div class="col-span-2 text-right">Running</div>
                </div>

                <div v-if="props.rows.length === 0" class="px-4 py-6 text-sm text-slate-500 border-t">
                    No transactions for this selection.
                </div>

                <div v-for="r in props.rows" :key="r.line_id"
                    class="grid grid-cols-12 gap-2 px-4 py-2 text-sm border-t">
                    <div class="col-span-2 tabular-nums">{{ r.entry_date }}</div>
                    <div class="col-span-2 text-slate-700">{{ r.reference_no || '—' }}</div>
                    <div class="col-span-4">
                        <div class="text-slate-900">{{ r.line_description || r.memo || '—' }}</div>
                        <div class="text-xs text-slate-500" v-if="r.source_type">
                            {{ r.source_type }} #{{ r.source_id }}
                        </div>
                    </div>
                    <div class="col-span-1 text-right tabular-nums">{{ money(r.debit) }}</div>
                    <div class="col-span-1 text-right tabular-nums">{{ money(r.credit) }}</div>
                    <div class="col-span-2 text-right tabular-nums font-semibold">{{ money(r.running_balance) }}</div>
                </div>

                <div
                    class="sticky bottom-0 flex items-center justify-between px-4 py-4 border-t bg-slate-50 text-base font-bold">
                    <div>Closing Balance</div>
                    <div class="tabular-nums text-right">{{ money(closingBalance) }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
