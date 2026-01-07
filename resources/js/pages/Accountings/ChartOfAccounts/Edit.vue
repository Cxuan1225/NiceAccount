<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps<{
    account: { id: number; account_code: string; name: string; type: string; parent_id: number | null; is_active: boolean }
    types: string[]
    parents: { id: number; account_code: string; name: string }[]
}>()

const breadcrumbs = [
    { title: 'Accountings', href: '#' },
    { title: 'Chart of Accounts', href: '/accountings/chart-of-accounts' },
    { title: 'Edit', href: `/accountings/chart-of-accounts/${props.account.id}/edit` },
]

const form = useForm({
    account_code: props.account.account_code,
    name: props.account.name,
    type: props.account.type,
    parent_id: props.account.parent_id ?? '',
    is_active: props.account.is_active,
})

function submit() {
    form.put(`/accountings/chart-of-accounts/${props.account.id}`)
}
</script>

<template>

    <Head title="Edit Account" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4 max-w-xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Edit Account</h1>
                <Link href="/accountings/chart-of-accounts" class="text-sm underline">Back</Link>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm mb-1">Account Code *</label>
                    <input v-model="form.account_code" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.account_code" class="text-red-600 text-sm mt-1">{{ form.errors.account_code
                    }}</div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Name *</label>
                    <input v-model="form.name" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Type *</label>
                    <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm">
                        <option v-for="t in props.types" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <div v-if="form.errors.type" class="text-red-600 text-sm mt-1">{{ form.errors.type }}</div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Parent Account (optional)</label>
                    <select v-model="form.parent_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">None</option>
                        <option v-for="p in props.parents" :key="p.id" :value="p.id">
                            {{ p.account_code }} - {{ p.name }}
                        </option>
                    </select>
                    <div v-if="form.errors.parent_id" class="text-red-600 text-sm mt-1">{{ form.errors.parent_id }}
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" type="checkbox" v-model="form.is_active" />
                    <label for="is_active" class="text-sm">Active</label>
                </div>

                <div class="pt-2 flex gap-2">
                    <button class="border rounded px-4 py-2 text-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link href="/accountings/chart-of-accounts" class="border rounded px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
