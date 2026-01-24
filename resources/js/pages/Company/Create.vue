<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { store } from '@/routes/companies'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
    defaults: {
        base_currency: string
        timezone: string
        date_format: string
        fy_start_month: number
        country: string
    }
}>()

const form = useForm({
    name: '',
    base_currency: props.defaults.base_currency,
    timezone: props.defaults.timezone,
    date_format: props.defaults.date_format,
    fy_start_month: props.defaults.fy_start_month,
})

function submit() {
    form.post(store().url)
}
</script>

<template>
    <AppLayout title="Create Company">
        <div class="max-w-3xl mx-auto p-4">
            <h1 class="text-xl font-semibold">Create Company</h1>

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
                        Create
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
