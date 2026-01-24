<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as customersCreate, index as customersIndex, store as customersStore } from '@/routes/customers'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: customersIndex().url },
    { title: 'Create', href: customersCreate().url },
]

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
})

function submit() {
    form.post(customersStore().url)
}
</script>

<template>

    <Head title="Create Customer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4 max-w-xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">New Customer</h1>
                <Link :href="customersIndex().url" class="text-sm underline">Back</Link>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm mb-1">Name <span class="text-red-600">*</span></label>
                    <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" autofocus />
                    <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                        {{ form.errors.name }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2" />
                    <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Phone</label>
                    <input v-model="form.phone" type="text" class="w-full border rounded px-3 py-2" />
                    <div v-if="form.errors.phone" class="text-sm text-red-600 mt-1">
                        {{ form.errors.phone }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Address</label>
                    <textarea v-model="form.address" rows="3" class="w-full border rounded px-3 py-2"></textarea>
                    <div v-if="form.errors.address" class="text-sm text-red-600 mt-1">
                        {{ form.errors.address }}
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 border rounded" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>

                    <Link :href="customersIndex().url" class="px-4 py-2 border rounded">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
