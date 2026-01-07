<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{ company: any }>()

const form = useForm({
    name: props.company.name ?? '',
    base_currency: props.company.base_currency ?? 'MYR',
    timezone: props.company.timezone ?? 'Asia/Kuala_Lumpur',
    date_format: props.company.date_format ?? 'd/m/Y',
    fy_start_month: props.company.fy_start_month ?? 1,

    email: props.company.email ?? '',
    phone: props.company.phone ?? '',
    address_line1: props.company.address_line1 ?? '',
    address_line2: props.company.address_line2 ?? '',
    address_line3: props.company.address_line3 ?? '',
    city: props.company.city ?? '',
    state: props.company.state ?? '',
    postcode: props.company.postcode ?? '',
    country: props.company.country ?? 'MY',
})

function submit() {
    form.put(route('company.settings.update'))
}
</script>

<template>
    <AppLayout title="Company Settings">
        <div class="max-w-3xl mx-auto p-4">
            <h1 class="text-xl font-semibold">Company Settings</h1>

            <form class="mt-4 space-y-3" @submit.prevent="submit">
                <div>
                    <label class="block text-sm">Name</label>
                    <input v-model="form.name" class="border rounded w-full p-2" />
                    <div v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm">Base Currency</label>
                        <input v-model="form.base_currency" class="border rounded w-full p-2" />
                    </div>
                    <div>
                        <label class="block text-sm">Timezone</label>
                        <input v-model="form.timezone" class="border rounded w-full p-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm">Date Format</label>
                        <input v-model="form.date_format" class="border rounded w-full p-2" />
                    </div>
                    <div>
                        <label class="block text-sm">FY Start Month (1-12)</label>
                        <input v-model.number="form.fy_start_month" type="number" min="1" max="12"
                            class="border rounded w-full p-2" />
                    </div>
                </div>

                <div class="pt-2">
                    <button class="border rounded px-4 py-2" :disabled="form.processing">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
