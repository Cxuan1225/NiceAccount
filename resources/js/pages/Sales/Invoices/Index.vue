<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as salesInvoicesIndex } from '@/routes/sales/invoices';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sales (Invoices)',
        href: salesInvoicesIndex().url,
    },
];
</script>
<template>

    <Head title="Sales (Invoices)" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <h1 class="text-xl font-semibold">Sales (Invoices)</h1>
    </AppLayout>
</template>
