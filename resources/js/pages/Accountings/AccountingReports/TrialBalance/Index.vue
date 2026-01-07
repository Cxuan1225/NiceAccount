<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { reactive, computed, ref, onMounted, onBeforeUnmount } from 'vue'
import ReportHeader from '@/components/accounting/ReportHeader.vue'

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
    filters: {
        from: string | null,
        to: string | null,
        status: string | null,
        status_label?: string,
        show_zero?: boolean
    }
    rows: Array<{
        account_id: number
        account_code: string
        name: string
        type: string | null
        is_active: boolean
        ending_debit: number
        ending_credit: number
    }>
    totals: { ending_debit: number, ending_credit: number }
}>()

const form = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    status: props.filters.status ?? 'posted',
    show_zero: !!props.filters.show_zero,
})

function apply() {
    router.get(
        '/accountings/accounting-reports/trial-balance',
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
    return (n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const diff = computed(() => {
    const d = (props.totals.ending_debit || 0) - (props.totals.ending_credit || 0)
    return Math.round(d * 100) / 100
})
</script>

<template>

    <Head title="Accounting Reports" />

    <AppLayout>
        <div class="px-6 py-4">
            <ReportHeader title="Accounting Reports"
                subtitle="Financial statements generated from posted journal entries" :tabs="[
                    { label: 'Trial Balance', href: '/accountings/accounting-reports/trial-balance', activePrefix: '/accountings/accounting-reports/trial-balance' },
                    { label: 'Profit & Loss', href: '/accountings/accounting-reports/profit-loss', activePrefix: '/accountings/accounting-reports/profit-loss' },
                    { label: 'Balance Sheet', href: '/accountings/accounting-reports/balance-sheet', activePrefix: '/accountings/accounting-reports/balance-sheet' },
                    { label: 'General Ledger', href: '/accountings/accounting-reports/general-ledger', activePrefix: '/accountings/accounting-reports/general-ledger' },
                ]" :from="props.filters.from" :to="props.filters.to" :status="props.filters.status || 'posted'"
                :showZero="form.show_zero" :isLoading="isLoading"
                :exportPdfHref="`/accountings/accounting-reports/trial-balance/export/pdf?from=${form.from || ''}&to=${form.to || ''}&status=${form.status || ''}&show_zero=${form.show_zero ? 1 : 0}`"
                :exportExcelHref="`/accountings/accounting-reports/trial-balance/export/excel?from=${form.from || ''}&to=${form.to || ''}&status=${form.status || ''}&show_zero=${form.show_zero ? 1 : 0}`" />

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

            <!-- Table -->
            <div class="mt-6 overflow-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Code</th>
                            <th class="px-3 py-2 text-left">Account</th>
                            <th class="px-3 py-2 text-right">Ending Debit (RM)</th>
                            <th class="px-3 py-2 text-right">Ending Credit (RM)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="r in props.rows" :key="r.account_id" class="border-t">
                            <td class="px-3 py-2 font-mono">{{ r.account_code }}</td>
                            <td class="px-3 py-2">
                                {{ r.name }}
                                <span v-if="!r.is_active" class="ml-2 text-xs text-slate-500">(inactive)</span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ money(r.ending_debit) }}</td>
                            <td class="px-3 py-2 text-right">{{ money(r.ending_credit) }}</td>
                        </tr>

                        <tr v-if="props.rows.length === 0">
                            <td colspan="4" class="px-3 py-6 text-center text-slate-500">
                                No data found.
                            </td>
                        </tr>
                    </tbody>

                    <tfoot class="bg-slate-50 border-t">
                        <tr>
                            <td colspan="2" class="px-3 py-2 font-semibold">Total</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ money(props.totals.ending_debit) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ money(props.totals.ending_credit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div v-if="diff !== 0" class="mt-6 text-sm text-red-600">
                Not balanced — Diff: {{ money(Math.abs(diff)) }}
            </div>
        </div>
    </AppLayout>
</template>
