<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { type BreadcrumbItem } from '@/types'
import { confirmDelete, notyf } from '@/utils/alerts'


const props = defineProps<{
    customers?: any // allow paginator or array
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Customers', href: '/customers' },
]

// Normalize to a clean array and remove null items
const customerRows = computed(() => {
    const raw = props.customers

    // paginator style: { data: [...] }
    const list = Array.isArray(raw) ? raw : (raw && Array.isArray(raw.data) ? raw.data : [])

    // remove null/undefined
    return list.filter((x: any) => x && x.id)
})

function destroyCustomer(id: number) {
    confirmDelete('The customer will be permanently deleted.')
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/customers/${id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        notyf.success('Customer deleted successfully')
                    },
                    onError: () => {
                        notyf.error('Failed to delete customer')
                    },
                })
            }
        })
}
</script>


<template>

    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-4">

            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold">Customers</h1>

                <Link href="/customers/create" class="px-3 py-2 border rounded text-sm">
                    New Customer
                </Link>
            </div>

            <div class="border rounded overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="text-left p-3">Name</th>
                            <th class="text-left p-3">Email</th>
                            <th class="text-left p-3">Phone</th>
                            <th class="text-right p-3">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="c in (customerRows || []).filter(Boolean)" :key="c.id" class="border-b">
                            <td class="p-3">{{ c.name }}</td>
                            <td class="p-3">{{ c.email || '-' }}</td>
                            <td class="p-3">{{ c.phone || '-' }}</td>
                            <td class="p-3 text-right space-x-2">
                                <Link :href="`/customers/${c.id}/edit`" class="underline">
                                    Edit
                                </Link>

                                <button type="button" class="underline text-red-600" @click="destroyCustomer(c.id)">
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <tr v-if="customers.length === 0">
                            <td colspan="4" class="p-4 text-center text-muted-foreground">
                                No customers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
