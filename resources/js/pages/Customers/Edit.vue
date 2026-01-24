<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { edit as customersEdit, index as customersIndex, update as customersUpdate } from '@/routes/customers'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'

const props = defineProps<{
    customer: {
        id: number
        name: string
        email: string | null
        phone: string | null
        address: string | null
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: customersIndex().url },
    { title: 'Edit', href: customersEdit(props.customer.id).url },
]

const form = useForm({
    name: props.customer.name || '',
    email: props.customer.email || '',
    phone: props.customer.phone || '',
    address: props.customer.address || '',
})

function submit() {
    form.put(customersUpdate(props.customer.id).url)
}
</script>

<template>

    <Head title="Edit Customer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4 max-w-xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Edit Customer</h1>
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
                        {{ form.processing ? 'Updating...' : 'Update' }}
                    </button>

                    <Link :href="customersIndex().url" class="px-4 py-2 border rounded">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
