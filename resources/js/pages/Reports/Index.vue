<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as reportsIndex } from '@/routes/reports';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: reportsIndex().url,
    },
];
</script>
<template>

    <Head title="Reports" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <h1 class="text-xl font-semibold">Reports</h1>
    </AppLayout>
</template>
