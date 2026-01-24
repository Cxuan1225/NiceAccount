<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as rolesCreate, index as rolesIndex, store as rolesStore } from '@/routes/security/roles'
import { Head, Link, useForm } from '@inertiajs/vue3'

type PermissionOption = {
    id: number
    name: string
    label: string
    category: string
    description: string | null
}

const props = defineProps<{
    permissions: PermissionOption[]
}>()

const form = useForm({
    name: '',
    permissions: [] as string[],
})

function submit() {
    form.post(rolesStore().url)
}
</script>

<template>
    <Head title="Create Role" />

    <AppLayout>
        <div class="px-6 py-4 max-w-4xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Create Role</h1>
                <Link :href="rolesIndex().url" class="text-sm underline">Back</Link>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="block text-sm mb-1">Name</label>
                    <input v-model="form.name" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-sm mb-2">Permissions</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label v-for="p in props.permissions" :key="p.id" class="flex items-start gap-2 text-sm">
                            <input type="checkbox" :value="p.name" v-model="form.permissions" class="mt-1" />
                            <span>
                                <div class="font-medium">{{ p.label || p.name }}</div>
                                <div class="text-xs text-slate-500">{{ p.name }}</div>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button class="border rounded px-4 py-2 text-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                    <Link :href="rolesIndex().url" class="border rounded px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
