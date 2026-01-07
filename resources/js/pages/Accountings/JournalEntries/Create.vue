<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

type Account = { id: number; account_code: string; name: string; type: string }

const props = defineProps<{ accounts: Account[] }>()

const breadcrumbs = [
    { title: 'Accountings', href: '#' },
    { title: 'Journal Entries', href: '/accountings/journal-entries' },
    { title: 'Create', href: '/accountings/journal-entries/create' },
]

const form = useForm({
    entry_date: new Date().toISOString().slice(0, 10),
    reference_no: '',
    memo: '',
    lines: [
        { account_id: '', description: '', debit: '', credit: '' },
        { account_id: '', description: '', debit: '', credit: '' },
    ],
})

function addLine() {
    form.lines.push({ account_id: '', description: '', debit: '', credit: '' })
}

function removeLine(i: number) {
    if (form.lines.length <= 2) return
    form.lines.splice(i, 1)
}

function normalizeMoney(v: any) {
    const n = Number(v || 0)
    return isFinite(n) ? n : 0
}

const debitTotal = computed(() => {
    let t = 0
    for (const l of form.lines) t += normalizeMoney(l.debit)
    return Number(t.toFixed(2))
})

const creditTotal = computed(() => {
    let t = 0
    for (const l of form.lines) t += normalizeMoney(l.credit)
    return Number(t.toFixed(2))
})

const balanced = computed(() => debitTotal.value === creditTotal.value && debitTotal.value > 0)

function submit() {
    form.post('/accountings/journal-entries')
}
</script>

<template>

    <Head title="New Journal Entry" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4 max-w-6xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">New Journal Entry</h1>
                <Link href="/accountings/journal-entries" class="text-sm underline">Back</Link>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm mb-1">Date *</label>
                    <input v-model="form.entry_date" type="date" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.entry_date" class="text-red-600 text-sm mt-1">{{ form.errors.entry_date }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm mb-1">Reference</label>
                    <input v-model="form.reference_no" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.reference_no" class="text-red-600 text-sm mt-1">{{ form.errors.reference_no
                    }}</div>
                </div>
                <div>
                    <label class="block text-sm mb-1">Memo</label>
                    <input v-model="form.memo" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.memo" class="text-red-600 text-sm mt-1">{{ form.errors.memo }}</div>
                </div>
            </div>

            <div class="mt-6 border rounded overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-3 py-2 w-80">Account *</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2 text-right w-40">Debit</th>
                            <th class="px-3 py-2 text-right w-40">Credit</th>
                            <th class="px-3 py-2 text-right w-20"> </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(l, i) in form.lines" :key="i" class="border-t">
                            <td class="px-3 py-2">
                                <select v-model="l.account_id" class="w-full border rounded px-2 py-2 text-sm">
                                    <option value="">Select account</option>
                                    <option v-for="a in props.accounts" :key="a.id" :value="a.id">
                                        {{ a.account_code }} - {{ a.name }}
                                    </option>
                                </select>
                                <div v-if="(form.errors as any)[`lines.${i}.account_id`]"
                                    class="text-red-600 text-sm mt-1">
                                    {{ (form.errors as any)[`lines.${i}.account_id`] }}
                                </div>
                            </td>

                            <td class="px-3 py-2">
                                <input v-model="l.description" class="w-full border rounded px-2 py-2 text-sm"
                                    placeholder="optional" />
                                <div v-if="(form.errors as any)[`lines.${i}.description`]"
                                    class="text-red-600 text-sm mt-1">
                                    {{ (form.errors as any)[`lines.${i}.description`] }}
                                </div>
                            </td>

                            <td class="px-3 py-2 text-right">
                                <input v-model="l.debit" type="number" min="0" step="0.01"
                                    class="w-full border rounded px-2 py-2 text-sm text-right" placeholder="0.00" />
                                <div v-if="(form.errors as any)[`lines.${i}.debit`]" class="text-red-600 text-sm mt-1">
                                    {{ (form.errors as any)[`lines.${i}.debit`] }}
                                </div>
                            </td>

                            <td class="px-3 py-2 text-right">
                                <input v-model="l.credit" type="number" min="0" step="0.01"
                                    class="w-full border rounded px-2 py-2 text-sm text-right" placeholder="0.00" />
                                <div v-if="(form.errors as any)[`lines.${i}.credit`]" class="text-red-600 text-sm mt-1">
                                    {{ (form.errors as any)[`lines.${i}.credit`] }}
                                </div>
                            </td>

                            <td class="px-3 py-2 text-right">
                                <button class="text-red-600 hover:underline" @click="removeLine(i)"
                                    :disabled="form.lines.length <= 2">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>

                    <tfoot class="bg-slate-50 border-t">
                        <tr>
                            <td class="px-3 py-2" colspan="2">
                                <button class="border rounded px-3 py-2 text-sm" type="button" @click="addLine">Add
                                    line</button>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold">{{ debitTotal.toFixed(2) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ creditTotal.toFixed(2) }}</td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div v-if="form.errors.lines" class="text-red-600 text-sm mt-2">{{ form.errors.lines }}</div>

            <div class="mt-3 text-sm" :class="balanced ? 'text-green-700' : 'text-slate-700'">
                Status:
                <span class="font-medium">
                    {{ balanced ? 'Balanced ✅' : 'Not balanced (Debit must equal Credit)' }}
                </span>
            </div>

            <div class="mt-6 flex gap-2">
                <button class="border rounded px-4 py-2 text-sm" :disabled="form.processing || !balanced"
                    @click="submit">
                    {{ form.processing ? 'Saving...' : 'Post Journal Entry' }}
                </button>
                <Link href="/accountings/journal-entries" class="border rounded px-4 py-2 text-sm">Cancel</Link>
            </div>

            <div class="mt-3 text-xs text-slate-600">
                Tip: Each row should have either Debit or Credit (not both). Totals must match.
            </div>
        </div>
    </AppLayout>
</template>
