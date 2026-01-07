<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

type Tab = {
    label: string
    href: string
    activePrefix: string
}

const page = usePage()

const props = defineProps<{
    title: string
    subtitle?: string
    tabs: Tab[]

    // meta
    from?: string | null
    to?: string | null
    asAt?: string | null
    status?: string | null
    showZero?: boolean

    // actions (right side)
    exportPdfHref?: string
    exportExcelHref?: string
    isLoading?: boolean
}>()

const currentUrl = computed(() => (page.url || '').split('?')[0])

const statusLabel = computed(() => {
    const s = (props.status || '').toString().trim()
    if (!s) return ''
    return s.charAt(0).toUpperCase() + s.slice(1).toLowerCase()
})

const loading = computed(() => !!props.isLoading)

function isTabActive(prefix: string) {
    return currentUrl.value.startsWith(prefix)
}

const tabBase =
    'h-10 px-4 text-sm border-b-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2'
const tabActive = 'border-slate-900 font-semibold text-slate-900'
const tabInactive = 'border-transparent text-slate-500 hover:text-slate-700'
</script>

<template>
    <div class="mb-6 border-b pb-4">
        <div class="flex flex-col gap-3">
            <!-- Title -->
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">{{ title }}</h1>
                    <p v-if="subtitle" class="text-sm text-slate-500">{{ subtitle }}</p>
                </div>

                <!-- Right actions (slot + export) -->
                <div class="flex flex-wrap items-center gap-2 justify-end">
                    <!-- ✅ Always render slot (empty if not provided) -->
                    <div class="flex flex-wrap gap-2" :class="loading ? 'pointer-events-none opacity-50' : ''">
                        <slot name="actions" />
                    </div>

                    <!-- Exports -->
                    <div v-if="exportPdfHref || exportExcelHref" class="flex gap-2">
                        <a v-if="exportPdfHref"
                            class="h-9 px-4 inline-flex items-center border rounded text-sm hover:bg-slate-100"
                            :class="loading ? 'pointer-events-none opacity-50' : ''" :href="exportPdfHref">
                            Export PDF
                        </a>

                        <a v-if="exportExcelHref"
                            class="h-9 px-4 inline-flex items-center border rounded text-sm hover:bg-slate-100"
                            :class="loading ? 'pointer-events-none opacity-50' : ''" :href="exportExcelHref">
                            Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b">
                <button v-for="t in tabs" :key="t.href" type="button"
                    :class="[tabBase, isTabActive(t.activePrefix) ? tabActive : tabInactive]"
                    @click="router.get(t.href)">
                    {{ t.label }}
                </button>
            </div>

            <!-- Meta -->
            <div class="flex flex-wrap gap-2 text-xs min-h-[28px]">
                <!-- ✅ Balance Sheet -->
                <span v-if="asAt" class="rounded bg-slate-100 px-2 py-1 text-slate-700">
                    As at: {{ asAt }}
                </span>

                <!-- ✅ Existing Period (Trial Balance / P&L) -->
                <span v-else class="rounded bg-slate-100 px-2 py-1 text-slate-700">
                    Period: {{ from && to ? from + ' → ' + to : 'All time' }}
                </span>

                <span v-if="statusLabel" class="rounded bg-slate-100 px-2 py-1 text-slate-700">
                    Status: {{ statusLabel }}
                </span>

                <span v-if="showZero" class="rounded bg-slate-100 px-2 py-1 text-slate-700">
                    Showing zero accounts
                </span>
            </div>
        </div>
    </div>
</template>
