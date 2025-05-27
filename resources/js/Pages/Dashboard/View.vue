<script setup>
import { defineProps } from "vue";

import BestSellingWidget from '@/Pages/Dashboard/component/BestSellingWidget.vue';
import NotificationsWidget from '@/Pages/Dashboard/component/NotificationsWidget.vue';
import RevenueStreamWidget from '@/Pages/Dashboard/component/RevenueStreamWidget.vue';
import StatsWidget from '@/Pages/Dashboard/component/StatsWidget.vue';

import AppLayout from "@/Layouts/AppLayout.vue";

</script>

<template>
    <Head title="Dashboard" />

    <div class="grid grid-cols-12 gap-8">
        <StatsWidget />

        <div class="col-span-12 xl:col-span-6">
            <BestSellingWidget />
        </div>
        <div class="col-span-12 xl:col-span-6">
            <RevenueStreamWidget />
            <NotificationsWidget />
        </div>
    </div>
</template>
