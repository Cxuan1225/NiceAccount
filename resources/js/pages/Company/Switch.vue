<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

type Company = {
    id: number
    code: string
    name: string
    base_currency: string
}

const props = defineProps<{
    companies: Company[]
    activeCompanyId: number
}>()

const search = ref('')
const switchingId = ref<number | null>(null)

const activeCompany = computed(() => {
    return props.companies.find(c => c.id === props.activeCompanyId) || null
})

const filteredCompanies = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.companies

    return props.companies.filter((c) => {
        return (
            (c.name || '').toLowerCase().includes(q) ||
            (c.code || '').toLowerCase().includes(q) ||
            (c.base_currency || '').toLowerCase().includes(q)
        )
    })
})

function switchCompany(companyId: number) {
    if (companyId === props.activeCompanyId) return
    if (switchingId.value) return

    switchingId.value = companyId

    router.post(
        '/companies/switch',
        { company_id: companyId },
        {
            preserveScroll: true,
            onFinish: () => (switchingId.value = null),
        }
    )
}
</script>

<template>

    <Head title="Switch Company" />

    <AppLayout>
        <!-- MATCH admin spacing -->
        <div class="px-6 py-4">

            <!-- Header (same scale as COA) -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Switch Company</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Select the company workspace you want to operate in.
                    </p>
                </div>
            </div>

            <!-- Search (admin-sized input) -->
            <div class="mt-4 max-w-sm">
                <label class="block text-sm mb-1">Search</label>
                <div class="relative">
                    <input v-model="search" type="text" placeholder="company name, code, currency"
                        class="w-full border rounded px-3 py-2 text-sm" />
                    <button v-if="search" type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-500 hover:underline"
                        @click="search = ''">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Active company (still card, but toned down) -->
            <div v-if="activeCompany" class="mt-6 border rounded bg-white px-4 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">
                            Current company
                        </div>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="font-medium">
                                {{ activeCompany.name }}
                            </div>
                            <span
                                class="inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                Active
                            </span>
                        </div>
                        <div class="mt-0.5 text-sm text-slate-600">
                            {{ activeCompany.code }} · {{ activeCompany.base_currency }}
                        </div>
                    </div>

                    <div class="text-xs text-slate-500">
                        Context will update after switch
                    </div>
                </div>
            </div>

            <!-- Cards grid (same UI, admin density) -->
            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-slate-700">
                        Companies
                        <span class="ml-1 text-slate-500">
                            ({{ filteredCompanies.length }})
                        </span>
                    </div>

                    <div v-if="switchingId" class="text-xs text-slate-500">
                        Switching...
                    </div>
                </div>

                <div v-if="!filteredCompanies.length"
                    class="mt-4 border rounded px-6 py-8 text-center text-sm text-slate-500">
                    No companies found.
                </div>

                <div v-else class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="c in filteredCompanies" :key="c.id"
                        class="rounded border bg-white px-4 py-3 transition hover:bg-slate-50" :class="c.id === activeCompanyId
                            ? 'border-emerald-300'
                            : 'border-slate-200'">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="truncate font-medium">
                                        {{ c.name }}
                                    </div>
                                    <span v-if="c.id === activeCompanyId"
                                        class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">
                                        Active
                                    </span>
                                </div>

                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ c.code }} · {{ c.base_currency }}
                                </div>
                            </div>

                            <button v-if="c.id !== activeCompanyId"
                                class="text-sm text-blue-600 hover:underline disabled:opacity-60"
                                :disabled="!!switchingId" @click="switchCompany(c.id)">
                                <span v-if="switchingId === c.id">Switching…</span>
                                <span v-else>Switch</span>
                            </button>

                            <span v-else class="text-xs text-slate-500">
                                Selected
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
