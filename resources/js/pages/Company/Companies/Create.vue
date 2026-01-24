<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as companiesCreate, index as companiesIndex, store as companiesStore } from '@/routes/companies'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    base_currency: 'MYR',
    timezone: 'Asia/Kuala_Lumpur',
    date_format: 'd/m/Y',
    fy_start_month: 1,
    email: '',
    phone: '',
    address_line1: '',
    address_line2: '',
    address_line3: '',
    city: '',
    state: '',
    postcode: '',
    country: 'MY',
})

function submit() {
    form.post(companiesStore().url)
}
</script>

<template>
    <Head title="Create Company" />

    <AppLayout>
        <div class="px-6 py-4 max-w-3xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Create Company</h1>
                <Link :href="companiesIndex().url" class="text-sm underline">Back</Link>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm mb-1">Name</label>
                    <input v-model="form.name" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Base Currency</label>
                        <input v-model="form.base_currency" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Timezone</label>
                        <input v-model="form.timezone" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Date Format</label>
                        <input v-model="form.date_format" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">FY Start Month</label>
                        <input v-model.number="form.fy_start_month" type="number" min="1" max="12"
                            class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Email</label>
                        <input v-model="form.email" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Phone</label>
                        <input v-model="form.phone" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Address Line 1</label>
                    <input v-model="form.address_line1" class="w-full border rounded px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm mb-1">Address Line 2</label>
                    <input v-model="form.address_line2" class="w-full border rounded px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-sm mb-1">Address Line 3</label>
                    <input v-model="form.address_line3" class="w-full border rounded px-3 py-2 text-sm" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm mb-1">City</label>
                        <input v-model="form.city" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">State</label>
                        <input v-model="form.state" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Postcode</label>
                        <input v-model="form.postcode" class="w-full border rounded px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Country</label>
                    <input v-model="form.country" class="w-full border rounded px-3 py-2 text-sm" />
                </div>

                <div class="pt-2 flex gap-2">
                    <button class="border rounded px-4 py-2 text-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                    <Link :href="companiesIndex().url" class="border rounded px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
