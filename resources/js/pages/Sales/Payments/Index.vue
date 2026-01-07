<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sales (Payments)',
        href: '/sales/payments',
    },
];
</script>
<template>

    <Head title="Sales (Payments)" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <h1 class="text-xl font-semibold">Sales (Payments)</h1>
    </AppLayout>
</template>
