<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { index as balanceSheetIndex } from '@/routes/accountings/accounting-reports/balance-sheet'
import { index as generalLedgerIndex } from '@/routes/accountings/accounting-reports/general-ledger'
import { index as profitLossIndex } from '@/routes/accountings/accounting-reports/profit-loss'
import { index as trialBalanceIndex } from '@/routes/accountings/accounting-reports/trial-balance'
import ReportHeader from '@/components/accounting/ReportHeader.vue'
import { Head, router } from '@inertiajs/vue3'
import { reactive, computed, ref, onMounted, onBeforeUnmount } from 'vue'

const isLoading = ref(false)
let unsubs: Array<() => void> = []

onMounted(() => {
    try {
        const offStart = router.on('start', () => { isLoading.value = true })
        const offFinish = router.on('finish', () => { isLoading.value = false })
        const offError = router.on('error', () => { isLoading.value = false })
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

const props = defineProps<{
    filters: { from: string | null, to: string | null, status: string | null, show_zero?: boolean }
    sections: {
        income: Array<{ account_id: number; account_code: string; name: string; type: 'INCOME'; amount: number }>
        expenses: Array<{ account_id: number; account_code: string; name: string; type: 'EXPENSE'; amount: number }>
    }
    totals: { income: number; expenses: number; net_profit: number }
}>()

const form = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    status: props.filters.status ?? 'posted',
    show_zero: !!props.filters.show_zero,
})

function apply() {
    router.get(
        profitLossIndex().url,
        {
            from: form.from || null,
            to: form.to || null,
            status: form.status || null,
            show_zero: form.show_zero ? 1 : 0,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    )
}

function reset() {
    form.from = ''
    form.to = ''
    form.status = 'posted'
    form.show_zero = false
    apply()
}

function money(n: number) {
    const v = Math.abs(n) < 0.000001 ? 0 : (n || 0)
    return v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function pad(n: number) {
    return n < 10 ? '0' + n : '' + n
}

function ymd(d: Date) {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

function setThisMonth() {
    const d = new Date()
    const first = new Date(d.getFullYear(), d.getMonth(), 1)
    const last = new Date(d.getFullYear(), d.getMonth() + 1, 0)
    form.from = ymd(first)
    form.to = ymd(last)
    apply()
}

function setThisYear() {
    const y = new Date().getFullYear()
    form.from = `${y}-01-01`
    form.to = `${y}-12-31`
    apply()
}

const reportTabs = [
    { label: 'Trial Balance', href: trialBalanceIndex().url, activePrefix: trialBalanceIndex().url },
    { label: 'Profit & Loss', href: profitLossIndex().url, activePrefix: profitLossIndex().url },
    { label: 'Balance Sheet', href: balanceSheetIndex().url, activePrefix: balanceSheetIndex().url },
    { label: 'General Ledger', href: generalLedgerIndex().url, activePrefix: generalLedgerIndex().url },
]

const totalIncome = computed(() => props.totals?.income || 0)
const totalExpenses = computed(() => props.totals?.expenses || 0)
const netProfit = computed(() => props.totals?.net_profit || 0)
</script>

<template>

    <Head title="Profit & Loss" />

    <AppLayout>
        <div class="px-6 py-4">
            <ReportHeader title="Accounting Reports" subtitle="Profit & Loss" :tabs="reportTabs"
                :from="props.filters.from" :to="props.filters.to" :status="props.filters.status"
                :showZero="form.show_zero" :isLoading="isLoading">
                <template #actions>
                    <button type="button"
                        class="h-9 px-3 text-sm border rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isLoading" @click="setThisMonth">
                        This month
                    </button>

                    <button type="button"
                        class="h-9 px-3 text-sm border rounded hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isLoading" @click="setThisYear">
                        This year
                    </button>
                </template>
            </ReportHeader>


            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3 mb-4">
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

            <!-- Quick Summary -->
            <div class="flex flex-wrap gap-6 mb-4 text-sm">
                <div class="min-w-[160px]">
                    <div class="text-slate-500">Total Income</div>
                    <div class="font-semibold tabular-nums">{{ money(totalIncome) }}</div>
                </div>

                <div class="min-w-[160px]">
                    <div class="text-slate-500">Total Expenses</div>
                    <div class="font-semibold tabular-nums">{{ money(totalExpenses) }}</div>
                </div>

                <div class="min-w-[160px]">
                    <div class="text-slate-500">Net Profit / Loss</div>
                    <div class="font-semibold tabular-nums"
                        :class="netProfit < 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ money(netProfit) }}
                    </div>
                </div>
            </div>

            <div v-if="props.sections.income.length === 0 && props.sections.expenses.length === 0"
                class="mb-3 rounded border bg-slate-50 px-4 py-3 text-sm text-slate-600">
                No Profit &amp; Loss activity for this period.
                <span class="text-slate-500">Tip: P&amp;L only shows INCOME and EXPENSE accounts.</span>
            </div>

            <div class="border rounded max-h-[70vh] overflow-auto">
                <div class="bg-slate-50 px-4 py-3 font-semibold">Income</div>

                <div v-if="props.sections.income.length === 0" class="px-4 py-4 text-sm text-slate-500 border-t italic">
                    No income transactions in this period.
                </div>

                <div v-else class="border-t">
                    <div v-for="r in props.sections.income" :key="r.account_id"
                        class="flex items-center justify-between px-4 py-2 border-t text-sm">
                        <div class="flex items-center">
                            <span class="font-mono text-slate-400 w-[80px] text-xs">{{ r.account_code }}</span>
                            <span class="pl-4 text-slate-900">{{ r.name }}</span>
                        </div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(r.amount) }}</div>
                    </div>

                    <div
                        class="flex items-center justify-between px-4 py-3 border-t bg-slate-100 text-sm font-semibold">
                        <div>Total Income</div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(totalIncome) }}</div>
                    </div>
                </div>

                <div class="mt-6 bg-slate-50 px-4 py-3 font-semibold border-t">Expenses</div>

                <div v-if="props.sections.expenses.length === 0"
                    class="px-4 py-4 text-sm text-slate-500 border-t italic">
                    No expense transactions in this period.
                </div>

                <div v-else class="border-t">
                    <div v-for="r in props.sections.expenses" :key="r.account_id"
                        class="flex items-center justify-between px-4 py-2 border-t text-sm">
                        <div class="flex items-center">
                            <span class="font-mono text-slate-400 w-[80px] text-xs">{{ r.account_code }}</span>
                            <span class="pl-4 text-slate-900">{{ r.name }}</span>
                        </div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(r.amount) }}</div>
                    </div>

                    <div
                        class="flex items-center justify-between px-4 py-3 border-t bg-slate-100 text-sm font-semibold">
                        <div>Total Expenses</div>
                        <div class="tabular-nums text-right w-[140px]">{{ money(totalExpenses) }}</div>
                    </div>
                </div>

                <div
                    class="sticky bottom-0 mt-8 flex items-center justify-between px-4 py-4 border-t bg-slate-50 text-base font-bold">
                    <div>Net Profit / Loss</div>
                    <div class="tabular-nums text-right w-[140px]"
                        :class="netProfit < 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ money(netProfit) }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
