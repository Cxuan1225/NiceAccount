<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Expenses (Bills)',
        href: '/expenses/bills',
    },
];
</script>
<template>

    <Head title="Expenses (Bills)" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <h1 class="text-xl font-semibold">Expenses (Bills)</h1>
    </AppLayout>
</template>
