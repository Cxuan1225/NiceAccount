<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { create as usersCreate, index as usersIndex, store as usersStore } from '@/routes/security/users'
import { Head, Link, useForm } from '@inertiajs/vue3'

type RoleOption = { id: number; name: string }
type CompanyOption = { id: number; name: string }

const props = defineProps<{
    roles: RoleOption[]
    companies: CompanyOption[]
}>()

const form = useForm({
    name: '',
    email: '',
    password: '',
    company_id: props.companies.length ? props.companies[0].id : null,
    roles: [] as string[],
})

function submit() {
    form.post(usersStore().url)
}
</script>

<template>
    <Head title="Create User" />

    <AppLayout>
        <div class="px-6 py-4 max-w-3xl">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Create User</h1>
                <Link :href="usersIndex().url" class="text-sm underline">Back</Link>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Name</label>
                        <input v-model="form.name" class="w-full border rounded px-3 py-2 text-sm" />
                        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Email</label>
                        <input v-model="form.email" class="w-full border rounded px-3 py-2 text-sm" />
                        <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Password</label>
                    <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2 text-sm" />
                    <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
                </div>

                <div v-if="props.companies.length">
                    <label class="block text-sm mb-1">Company</label>
                    <select v-model="form.company_id" class="w-full border rounded px-3 py-2 text-sm">
                        <option v-for="c in props.companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <div v-if="form.errors.company_id" class="text-red-600 text-sm mt-1">{{ form.errors.company_id }}</div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Roles</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label v-for="r in props.roles" :key="r.id" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" :value="r.name" v-model="form.roles" />
                            <span>{{ r.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button class="border rounded px-4 py-2 text-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </button>
                    <Link :href="usersIndex().url" class="border rounded px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
