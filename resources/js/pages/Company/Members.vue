<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'

type Member = {
    id: number
    name: string
    email: string
    status: string
    is_default: boolean
    joined_at: string | null
}

const props = defineProps<{
    company: { id: number; name: string }
    members: Member[]
}>()
</script>

<template>
    <AppLayout title="Company Members">
        <div class="max-w-4xl mx-auto p-4">
            <h1 class="text-xl font-semibold">Members - {{ props.company.name }}</h1>

            <div class="mt-4 border rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-2">Name</th>
                            <th class="text-left p-2">Email</th>
                            <th class="text-left p-2">Status</th>
                            <th class="text-left p-2">Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in props.members" :key="m.id" class="border-t">
                            <td class="p-2">{{ m.name }}</td>
                            <td class="p-2">{{ m.email }}</td>
                            <td class="p-2">{{ m.status }}</td>
                            <td class="p-2">{{ m.is_default ? 'Yes' : '-' }}</td>
                        </tr>
                        <tr v-if="!props.members.length">
                            <td colspan="4" class="p-3 text-gray-500">No members</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
