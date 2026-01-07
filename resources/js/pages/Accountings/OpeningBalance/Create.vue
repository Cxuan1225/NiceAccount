<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

type Account = { id: number; account_code: string; name: string; type: string }

const props = defineProps<{
    accounts: Account[]
    openingBalanceEquity: { id: number; account_code: string; name: string; type: string } | null
}>()

const breadcrumbs = [
    { title: 'Accountings', href: '#' },
    { title: 'Opening Balance', href: '/accountings/opening-balance' },
]

const form = useForm({
    entry_date: new Date().toISOString().slice(0, 10),
    memo: 'Opening Balance',
    lines: props.accounts.map(a => ({
        account_id: a.id,
        amount: 0,
    })),
})

const byId = computed(() => {
    const m: Record<number, Account> = {}
    props.accounts.forEach(a => (m[a.id] = a))
    return m
})

const debitTotal = computed(() => {
    let total = 0
    for (const l of form.lines) {
        const a = byId.value[l.account_id]
        const amt = Number(l.amount || 0)
        if (!a || amt <= 0) continue
        if (a.type === 'ASSET' || a.type === 'EXPENSE') total += amt
    }
    return Number(total.toFixed(2))
})

const creditTotal = computed(() => {
    let total = 0
    for (const l of form.lines) {
        const a = byId.value[l.account_id]
        const amt = Number(l.amount || 0)
        if (!a || amt <= 0) continue
        if (!(a.type === 'ASSET' || a.type === 'EXPENSE')) total += amt
    }
    return Number(total.toFixed(2))
})

const diff = computed(() => Number((debitTotal.value - creditTotal.value).toFixed(2)))

function submit() {
    form.post('/accountings/opening-balance')
}
</script>

<template>

    <Head title="Opening Balance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4 max-w-5xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Opening Balance</h1>
                <Link href="/accountings/chart-of-accounts" class="text-sm underline">Back to COA</Link>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm mb-1">Opening Date *</label>
                    <input v-model="form.entry_date" type="date" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.entry_date" class="text-red-600 text-sm mt-1">{{ form.errors.entry_date }}
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Memo</label>
                    <input v-model="form.memo" type="text" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.memo" class="text-red-600 text-sm mt-1">{{ form.errors.memo }}</div>
                </div>
            </div>

            <div class="mt-6 border rounded overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Account</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(a, idx) in props.accounts" :key="a.id" class="border-t">
                            <td class="px-3 py-2 whitespace-nowrap">{{ a.account_code }}</td>
                            <td class="px-3 py-2">{{ a.name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ a.type }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <input v-model.number="form.lines[idx].amount" type="number" min="0" step="0.01"
                                    class="w-32 border rounded px-2 py-1 text-sm text-right" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="form.errors.lines" class="text-red-600 text-sm mt-2">{{ form.errors.lines }}</div>
            <div v-if="form.errors.opening_balance_equity" class="text-red-600 text-sm mt-2">{{
                form.errors.opening_balance_equity }}</div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="border rounded p-3">
                    <div class="text-xs text-slate-500">Debit Total (Assets/Expenses)</div>
                    <div class="text-lg font-semibold">{{ debitTotal.toFixed(2) }}</div>
                </div>
                <div class="border rounded p-3">
                    <div class="text-xs text-slate-500">Credit Total (Liab/Equity/Income)</div>
                    <div class="text-lg font-semibold">{{ creditTotal.toFixed(2) }}</div>
                </div>
                <div class="border rounded p-3">
                    <div class="text-xs text-slate-500">Difference (Debit - Credit)</div>
                    <div class="text-lg font-semibold">
                        {{ diff.toFixed(2) }}
                    </div>
                    <div class="text-xs text-slate-600 mt-1">
                        Auto-balanced into:
                        <span class="font-medium">
                            {{ props.openingBalanceEquity ? `${props.openingBalanceEquity.account_code}
                            ${props.openingBalanceEquity.name}` : '3200/3000 (fallback)' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button class="border rounded px-4 py-2 text-sm" :disabled="form.processing" @click="submit">
                    {{ form.processing ? 'Posting...' : 'Post Opening Balance' }}
                </button>
                <Link href="/accountings/chart-of-accounts" class="border rounded px-4 py-2 text-sm">Cancel</Link>
            </div>

            <div class="mt-3 text-xs text-slate-600">
                Note: Positive amounts are recorded as Debits for ASSET/EXPENSE and Credits for LIABILITY/EQUITY/INCOME.
                The system auto-balances using Opening Balance Equity.
            </div>
        </div>
    </AppLayout>
</template>
