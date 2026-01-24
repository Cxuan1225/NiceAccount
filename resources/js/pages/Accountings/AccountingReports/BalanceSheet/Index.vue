<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { index as balanceSheetIndex } from '@/routes/accountings/accounting-reports/balance-sheet'
import { index as generalLedgerIndex } from '@/routes/accountings/accounting-reports/general-ledger'
import { index as profitLossIndex } from '@/routes/accountings/accounting-reports/profit-loss'
import { index as trialBalanceIndex } from '@/routes/accountings/accounting-reports/trial-balance'
import ReportHeader from '@/components/accounting/ReportHeader.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive, ref, onMounted, onBeforeUnmount } from 'vue'

type Tab = {
    label: string
    href: string
    activePrefix: string
}

type BsRow = {
    id: number
    account_code: string
    name: string
    type: 'ASSET' | 'LIABILITY' | 'EQUITY'
    parent_id: number | null
    debit: number
    credit: number
    balance: number
    is_active: boolean
}

type Filters = {
    as_at: string | null
    status: string | null
    status_label?: string | null
    show_zero: boolean
}

type Totals = {
    totalAssets: number
    totalLiabilities: number
    totalEquityBase: number
    totalEquity: number
    totalLiabEquity: number
    isBalanced: boolean
    difference: number
}

const props = defineProps<{
    filters: Filters
    assets: BsRow[]
    liabilities: BsRow[]
    equity: BsRow[]
    retainedEarnings: number
    totals: Totals

    exportPdfHref?: string
    exportExcelHref?: string
}>()

// loading (match your P&L pattern)
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

// form mirrors backend filters EXACTLY
const form = reactive({
    as_at: props.filters.as_at ?? '',
    status: props.filters.status ?? 'posted',
    show_zero: !!props.filters.show_zero,
})


function apply() {
    router.get(
        balanceSheetIndex().url,
        {
            as_at: form.as_at || null,
            status: form.status || null,
            show_zero: form.show_zero ? 1 : 0,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    )
}

function reset() {
    form.as_at = ''
    form.status = 'posted'
    form.show_zero = false
    apply()
}

function money(n: number) {
    const v = Math.abs(n || 0) < 0.000001 ? 0 : (n || 0)
    return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const totalAssets = computed(() => props.totals?.totalAssets || 0)
const totalLiabilities = computed(() => props.totals?.totalLiabilities || 0)
const totalEquity = computed(() => props.totals?.totalEquity || 0)
const totalLE = computed(() => props.totals?.totalLiabEquity || 0)
</script>

<template>

    <Head title="Balance Sheet" />

    <AppLayout>
        <div class="px-6 py-4">
            <ReportHeader title="Accounting Reports" subtitle="Balance Sheet" :tabs="tabs"
                :status="props.filters.status" :showZero="form.show_zero" :asAt="props.filters.as_at"
                :isLoading="isLoading" :exportPdfHref="exportPdfHref" :exportExcelHref="exportExcelHref" />

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3 mb-4">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">As at</label>
                    <input v-model="form.as_at" type="date" class="h-9 border rounded px-2 text-sm" />
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
                    Show zero accounts
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
                <div class="min-w-[160px]">
                    <div class="text-slate-500">Total Assets</div>
                    <div class="font-semibold tabular-nums">{{ money(totalAssets) }}</div>
                </div>

                <div class="min-w-[160px]">
                    <div class="text-slate-500">Total Liabilities</div>
                    <div class="font-semibold tabular-nums">{{ money(totalLiabilities) }}</div>
                </div>

                <div class="min-w-[160px]">
                    <div class="text-slate-500">Total Equity</div>
                    <div class="font-semibold tabular-nums">{{ money(totalEquity) }}</div>
                </div>

                <div class="min-w-[220px]">
                    <div class="text-slate-500">Balanced</div>
                    <div class="font-semibold" :class="props.totals.isBalanced ? 'text-emerald-600' : 'text-red-600'">
                        {{ props.totals.isBalanced ? 'Yes' : 'No' }}
                        <span v-if="!props.totals.isBalanced" class="ml-2 text-slate-500 font-normal">
                            (Diff: {{ money(props.totals.difference) }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Body (same style as P&L scroll container) -->
            <div class="border rounded max-h-[70vh] overflow-auto">
                <!-- Assets -->
                <div class="bg-slate-50 px-4 py-3 font-semibold">Assets</div>
                <div class="border-t">
                    <div v-for="r in props.assets" :key="r.id"
                        class="flex items-center justify-between px-4 py-2 border-t text-sm">
                        <div class="flex items-center">
                            <span class="font-mono text-slate-400 w-[80px] text-xs">{{ r.account_code }}</span>
                            <span class="pl-4 text-slate-900">{{ r.name }}</span>
                        </div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(r.balance) }}</div>
                    </div>

                    <div
                        class="flex items-center justify-between px-4 py-3 border-t bg-slate-100 text-sm font-semibold">
                        <div>Total Assets</div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(props.totals.totalAssets) }}</div>
                    </div>
                </div>

                <!-- Liabilities -->
                <div class="mt-6 bg-slate-50 px-4 py-3 font-semibold border-t">Liabilities</div>
                <div class="border-t">
                    <div v-for="r in props.liabilities" :key="r.id"
                        class="flex items-center justify-between px-4 py-2 border-t text-sm">
                        <div class="flex items-center">
                            <span class="font-mono text-slate-400 w-[80px] text-xs">{{ r.account_code }}</span>
                            <span class="pl-4 text-slate-900">{{ r.name }}</span>
                        </div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(r.balance) }}</div>
                    </div>

                    <div
                        class="flex items-center justify-between px-4 py-3 border-t bg-slate-100 text-sm font-semibold">
                        <div>Total Liabilities</div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(props.totals.totalLiabilities) }}</div>
                    </div>
                </div>

                <!-- Equity -->
                <div class="mt-6 bg-slate-50 px-4 py-3 font-semibold border-t">Equity</div>
                <div class="border-t">
                    <div v-for="r in props.equity" :key="r.id"
                        class="flex items-center justify-between px-4 py-2 border-t text-sm">
                        <div class="flex items-center">
                            <span class="font-mono text-slate-400 w-[80px] text-xs">{{ r.account_code }}</span>
                            <span class="pl-4 text-slate-900">{{ r.name }}</span>
                        </div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(r.balance) }}</div>
                    </div>

                    <div class="flex items-center justify-between px-4 py-2 border-t text-sm">
                        <div class="flex items-center">
                            <span class="font-mono text-slate-400 w-[80px] text-xs">—</span>
                            <span class="pl-4 text-slate-900">Retained Earnings</span>
                        </div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(props.retainedEarnings) }}</div>
                    </div>

                    <div
                        class="flex items-center justify-between px-4 py-3 border-t bg-slate-100 text-sm font-semibold">
                        <div>Total Equity</div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(props.totals.totalEquity) }}</div>
                    </div>
                </div>

                <!-- Sticky bottom summary -->
                <div
                    class="sticky bottom-0 flex items-center justify-between px-4 py-4 border-t bg-slate-50 text-base font-bold">
                    <div>Total Liabilities + Equity</div>
                    <div class="tabular-nums text-right w-[140px]">{{ money(totalLE) }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
