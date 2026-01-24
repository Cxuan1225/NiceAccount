<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { close as financialYearClose, store as financialYearStore } from '@/routes/accountings/financial-years'
import { bulkLock as postingPeriodsBulkLock, bulkUnlock as postingPeriodsBulkUnlock, index as postingPeriodsIndex, lock as postingPeriodLock, unlock as postingPeriodUnlock } from '@/routes/accountings/posting-periods'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Swal from 'sweetalert2'
import { notyf } from '@/utils/alerts'

type Period = {
    id: number
    period_start: string
    period_end: string
    is_locked: boolean
    locked_at: string | null
    locked_by: number | null
}

type FY = {
    id: number
    name: string
    start_date: string
    end_date: string
    is_closed: boolean
    periods: Period[]
}

const props = defineProps<{ years: FY[] }>()

const breadcrumbs = [
    { title: 'Accountings', href: '#' },
    { title: 'Posting Periods', href: postingPeriodsIndex().url },
]

const busyId = ref<number | null>(null)

function labelForPeriod(p: Period) {
    return `${p.period_start} → ${p.period_end}`
}

async function confirmLock(p: Period) {
    return Swal.fire({
        title: 'Lock posting period?',
        text: `You will NOT be able to post / reverse entries dated in this period.\n\n${labelForPeriod(p)}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, lock it',
        cancelButtonText: 'Cancel',
    })
}

async function confirmUnlock(p: Period) {
    return Swal.fire({
        title: 'Unlock posting period?',
        text: `This will allow posting / reversing entries again.\n\n${labelForPeriod(p)}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, unlock it',
        cancelButtonText: 'Cancel',
    })
}

async function lockPeriod(p: Period) {
    const r = await confirmLock(p)
    if (!r.isConfirmed) return

    busyId.value = p.id

    router.post(postingPeriodLock(p.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            p.is_locked = true
            notyf.success('Posting period locked')
        },
        onError: () => notyf.error('Failed to lock period'),
        onFinish: () => (busyId.value = null),
    })
}

async function unlockPeriod(p: Period) {
    const r = await confirmUnlock(p)
    if (!r.isConfirmed) return

    busyId.value = p.id

    router.post(postingPeriodUnlock(p.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            p.is_locked = false
            p.locked_at = null
            p.locked_by = null
            notyf.success('Posting period unlocked')
        },
        onError: () => notyf.error('Failed to unlock period'),
        onFinish: () => (busyId.value = null),
    })
}

/** Create FY modal */
const showCreateFY = ref(false)

const fyForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
})

function openCreateFY() {
    fyForm.clearErrors()
    showCreateFY.value = true
}

function closeCreateFY() {
    showCreateFY.value = false
}

function submitFY() {
    fyForm.post(financialYearStore().url, {
        preserveScroll: true,
        onSuccess: () => {
            notyf.success('Financial year created')
            showCreateFY.value = false
            fyForm.reset()
        },
        onError: () => notyf.error('Please check the form'),
    })
}

/** Collapse */
const expandedFyId = ref<number | null>(null)

function toggleFY(fy: FY) {
    expandedFyId.value = expandedFyId.value === fy.id ? null : fy.id
}

function isExpanded(fy: FY): boolean {
    return expandedFyId.value === fy.id
}

/** Selection + Mode (per FY) */
type SelectMode = 'lock' | 'unlock'
const modeByFY = ref<Record<number, SelectMode>>({})

function modeOf(fy: FY): SelectMode {
    return modeByFY.value[fy.id] ?? 'lock'
}

function setMode(fy: FY, mode: SelectMode) {
    // switching mode should clear selection to prevent mixed state
    modeByFY.value[fy.id] = mode
    selectedPeriods.value[fy.id] = new Set()
}

/** selected: fy.id -> set(period.id) */
const selectedPeriods = ref<Record<number, Set<number>>>({})

function ensureSet(fyId: number) {
    if (!selectedPeriods.value[fyId]) selectedPeriods.value[fyId] = new Set()
    return selectedPeriods.value[fyId]
}

function isSelected(fy: FY, p: Period) {
    return selectedPeriods.value[fy.id]?.has(p.id) ?? false
}

function canSelect(fy: FY, p: Period) {
    if (fy.is_closed) return false
    const mode = modeOf(fy)
    if (mode === 'lock') return !p.is_locked      // select open only
    return p.is_locked                             // select locked only
}

function togglePeriod(fy: FY, p: Period) {
    if (!canSelect(fy, p)) return
    const set = ensureSet(fy.id)
    if (set.has(p.id)) set.delete(p.id)
    else set.add(p.id)
}

function deselectAllPeriods(fy: FY) {
    selectedPeriods.value[fy.id] = new Set()
}

function selectedCount(fy: FY) {
    return selectedPeriods.value[fy.id]?.size ?? 0
}

function hasSelection(fy: FY) {
    return selectedCount(fy) > 0
}

function eligibleIdsByMode(fy: FY) {
    const mode = modeOf(fy)
    if (fy.is_closed) return []
    return fy.periods
        .filter(p => (mode === 'lock' ? !p.is_locked : p.is_locked))
        .map(p => p.id)
}

function selectAllPeriods(fy: FY) {
    selectedPeriods.value[fy.id] = new Set(eligibleIdsByMode(fy))
}

function selectedEligibleIds(fy: FY) {
    const wanted = new Set(eligibleIdsByMode(fy))
    const current = selectedPeriods.value[fy.id] ?? new Set()
    const ids: number[] = []
    current.forEach(id => {
        if (wanted.has(id)) ids.push(id)
    })
    return ids
}

function canBulkAction(fy: FY) {
    return !fy.is_closed && selectedEligibleIds(fy).length > 0
}

async function confirmBulkLockCount(count: number) {
    return Swal.fire({
        title: 'Bulk lock periods?',
        text: `Lock ${count} selected open period(s). You will NOT be able to post/reverse entries dated in these periods.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, lock selected',
        cancelButtonText: 'Cancel',
    })
}

async function confirmBulkUnlockCount(count: number) {
    return Swal.fire({
        title: 'Bulk unlock periods?',
        text: `Unlock ${count} selected locked period(s). This will allow posting/reversing again.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, unlock selected',
        cancelButtonText: 'Cancel',
    })
}

async function bulkApply(fy: FY) {
    if (!canBulkAction(fy)) return

    const ids = selectedEligibleIds(fy)
    const mode = modeOf(fy)

    const confirmResult =
        mode === 'lock'
            ? await confirmBulkLockCount(ids.length)
            : await confirmBulkUnlockCount(ids.length)

    if (!confirmResult.isConfirmed) return

    const url = mode === 'lock'
        ? postingPeriodsBulkLock().url
        : postingPeriodsBulkUnlock().url

    router.post(url, { ids }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optimistic update
            if (mode === 'lock') {
                fy.periods.forEach(p => {
                    if (ids.includes(p.id)) p.is_locked = true
                })
                notyf.success('Selected periods locked')
            } else {
                fy.periods.forEach(p => {
                    if (ids.includes(p.id)) {
                        p.is_locked = false
                        p.locked_at = null
                        p.locked_by = null
                    }
                })
                notyf.success('Selected periods unlocked')
            }

            // clear selection after apply
            deselectAllPeriods(fy)
        },
        onError: () => {
            notyf.error(mode === 'lock' ? 'Failed to bulk lock' : 'Failed to bulk unlock')
        },
    })
}

function selectableCount(fy: FY) {
    return eligibleIdsByMode(fy).length
}

async function confirmCloseFY() {
    return Swal.fire({
        title: 'Close financial year?',
        text: 'This will permanently close the financial year and hide its periods. You will not be able to unlock/lock periods anymore.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, close FY',
        cancelButtonText: 'Cancel',
    })
}

async function closeFY(fy: FY) {
    const r = await confirmCloseFY()
    if (!r.isConfirmed) return

    router.post(financialYearClose(fy.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            fy.is_closed = true

            // if it was expanded, collapse it
            if (expandedFyId.value === fy.id) expandedFyId.value = null

            notyf.success('Financial year closed')
        },
        onError: () => notyf.error('Failed to close financial year'),
    })
}

function canCloseFY(fy: FY) {
    return fy.periods.length > 0 && !fy.periods.some(p => !p.is_locked)
}

</script>

<template>

    <Head title="Posting Periods" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Posting Periods</h1>
                <button class="border rounded px-3 py-2 text-sm" @click="openCreateFY">
                    Create Financial Year
                </button>
            </div>

            <div class="mt-4 space-y-6">
                <div v-if="!props.years.length" class="text-sm text-slate-500">
                    No financial years found. Create a financial year first.
                </div>

                <div v-for="fy in props.years" :key="fy.id" class="border rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 flex items-center justify-between select-none transition"
                        :class="fy.is_closed ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:bg-slate-100'"
                        @click="fy.is_closed ? null : toggleFY(fy)">

                        <div>
                            <div class="font-semibold flex items-center gap-2">
                                <span>{{ fy.name }}</span>

                                <!-- FY badge (always show) -->
                                <span v-if="fy.is_closed" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
               bg-red-100 text-red-700 border border-red-200">
                                    Closed
                                </span>
                            </div>

                            <div class="text-sm text-slate-500">
                                {{ fy.start_date }} → {{ fy.end_date }}
                            </div>
                        </div>

                        <!-- Right side actions -->
                        <div class="flex items-center gap-2">
                            <!-- If FY is open -->
                            <template v-if="!fy.is_closed">
                                <!-- Expand / Collapse -->
                                <button type="button" @click.stop="toggleFY(fy)"
                                    class="flex items-center gap-1 text-xs font-medium text-slate-600 px-2 py-1 rounded border hover:bg-slate-100 transition">
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isExpanded(fy) }"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span>{{ isExpanded(fy) ? 'Collapse' : 'Expand' }}</span>
                                </button>

                                <!-- Close FY -->
                                <div class="relative group">
                                    <button type="button" @click.stop="canCloseFY(fy) && closeFY(fy)"
                                        :disabled="!canCloseFY(fy)" class="text-xs px-2 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50 transition
               disabled:opacity-50 disabled:cursor-not-allowed">
                                        Close FY
                                    </button>

                                    <!-- Tooltip -->
                                    <div v-if="!canCloseFY(fy)" class="absolute right-0 mt-1 hidden group-hover:block
               bg-slate-900 text-white text-[11px] px-2 py-1 rounded shadow-lg whitespace-nowrap">
                                        Lock all posting periods to close
                                    </div>
                                </div>




                            </template>

                            <!-- If FY is closed (no actions) -->
                            <template v-else>
                                <span class="text-xs text-slate-500">Locked & closed</span>
                            </template>
                        </div>


                    </div>

                    <!-- Body -->
                    <div v-if="!fy.is_closed && isExpanded(fy)">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-white">
                                    <tr class="text-left border-t">
                                        <th class="px-4 py-2 w-10"></th>
                                        <th class="px-4 py-2">Period</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Locked At</th>
                                        <th class="px-4 py-2 text-right">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="p in fy.periods" :key="p.id" class="border-t" :class="{
                                        'bg-slate-50': isSelected(fy, p),
                                        'opacity-60': fy.is_closed,
                                    }">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" :checked="isSelected(fy, p)"
                                                :disabled="!canSelect(fy, p)" @change="togglePeriod(fy, p)" />
                                        </td>

                                        <td class="px-4 py-2 whitespace-nowrap">
                                            {{ p.period_start }} → {{ p.period_end }}
                                        </td>

                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span v-if="p.is_locked"
                                                class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                                                Locked
                                            </span>
                                            <span v-else
                                                class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">
                                                Open
                                            </span>
                                        </td>

                                        <td class="px-4 py-2 whitespace-nowrap text-slate-600">
                                            {{ p.locked_at || '-' }}
                                        </td>

                                        <td class="px-4 py-2 text-right whitespace-nowrap">
                                            <button v-if="!p.is_locked"
                                                class="border rounded px-3 py-1 text-sm hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                                :disabled="busyId === p.id || fy.is_closed" @click="lockPeriod(p)">
                                                {{ busyId === p.id ? 'Working...' : 'Lock' }}
                                            </button>

                                            <button v-else
                                                class="border rounded px-3 py-1 text-sm text-blue-700 border-blue-300 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                                :disabled="busyId === p.id || fy.is_closed" @click="unlockPeriod(p)">
                                                {{ busyId === p.id ? 'Working...' : 'Unlock' }}
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!fy.periods.length">
                                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                            No posting periods generated for this financial year.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ✅ Bottom action bar -->
                        <div class="px-4 py-3 bg-white border-t flex items-center justify-between gap-3">
                            <div class="text-xs text-slate-500">
                                Tip: Lock a period after month-end closing to prevent backdated changes.
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Mode Switch -->
                                <div class="inline-flex rounded border overflow-hidden">
                                    <button class="text-xs px-2 py-1"
                                        :class="modeOf(fy) === 'lock' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'"
                                        @click.stop="setMode(fy, 'lock')" :disabled="fy.is_closed">
                                        Lock mode
                                    </button>
                                    <button class="text-xs px-2 py-1"
                                        :class="modeOf(fy) === 'unlock' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'"
                                        @click.stop="setMode(fy, 'unlock')" :disabled="fy.is_closed">
                                        Unlock mode
                                    </button>
                                </div>

                                <span class="text-xs text-slate-500">
                                    {{ hasSelection(fy) ? `${selectedCount(fy)} selected` : '' }}
                                </span>

                                <button v-if="!hasSelection(fy)" @click.stop="selectAllPeriods(fy)"
                                    :disabled="fy.is_closed || selectableCount(fy) === 0"
                                    class="text-xs px-2 py-1 rounded border text-slate-600 hover:bg-slate-100 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    Select all
                                </button>

                                <button v-else @click.stop="deselectAllPeriods(fy)"
                                    class="text-xs px-2 py-1 rounded border text-slate-600 hover:bg-slate-100 transition">
                                    Deselect all
                                </button>

                                <button @click.stop="bulkApply(fy)" :disabled="!canBulkAction(fy)"
                                    class="text-xs px-2 py-1 rounded border text-slate-700 hover:bg-slate-100 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ modeOf(fy) === 'lock' ? 'Bulk Lock' : 'Bulk Unlock' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Financial Year Modal -->
        <div v-if="showCreateFY" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/40" @click="closeCreateFY"></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-lg bg-white rounded-lg shadow-lg overflow-hidden" @click.stop>
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="font-semibold">Create Financial Year</div>
                        <button class="text-sm underline" @click="closeCreateFY">Close</button>
                    </div>

                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Name</label>
                            <input v-model="fyForm.name" class="w-full border rounded px-3 py-2 text-sm"
                                placeholder="FY2026" />
                            <div v-if="fyForm.errors.name" class="text-xs text-red-600 mt-1">{{ fyForm.errors.name }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm mb-1">Start Date</label>
                                <input type="date" v-model="fyForm.start_date"
                                    class="w-full border rounded px-3 py-2 text-sm" />
                                <div v-if="fyForm.errors.start_date" class="text-xs text-red-600 mt-1">
                                    {{ fyForm.errors.start_date }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm mb-1">End Date</label>
                                <input type="date" v-model="fyForm.end_date"
                                    class="w-full border rounded px-3 py-2 text-sm" />
                                <div v-if="fyForm.errors.end_date" class="text-xs text-red-600 mt-1">{{
                                    fyForm.errors.end_date }}</div>
                            </div>
                        </div>

                        <div class="text-xs text-slate-500">
                            This will auto-generate monthly posting periods between start and end date.
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t flex justify-end gap-2">
                        <button class="border rounded px-3 py-2 text-sm" @click="closeCreateFY">Cancel</button>
                        <button class="border rounded px-3 py-2 text-sm bg-slate-900 text-white disabled:opacity-50"
                            :disabled="fyForm.processing" @click="submitFY">
                            {{ fyForm.processing ? 'Creating...' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
