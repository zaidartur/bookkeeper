<script setup>
import { defineProps } from "vue";
import moment from 'moment';
import id from 'moment/dist/locale/id';

import WelcomeBanner from '@/Pages/Dashboard/component/WelcomeBanner.vue';
import BestSellingWidget from '@/Pages/Dashboard/component/BestSellingWidget.vue';
import NotificationsWidget from '@/Pages/Dashboard/component/NotificationsWidget.vue';
import RevenueStreamWidget from '@/Pages/Dashboard/component/RevenueStreamWidget.vue';
import StatsTrouble from '@/Pages/Dashboard/component/StatsTrouble.vue';
import StatsGuest from '@/Pages/Dashboard/component/StatsGuest.vue';
import StatsDevice from '@/Pages/Dashboard/component/StatsDevice.vue';

import AppLayout from "@/Layouts/AppLayout.vue";

const datas = defineProps({
    troubles: Object,
    guest: Object,
    grafik: Object,
    invent: Object,
    networkMonitors: Object,
    nocStats: Object,
})
const _today = moment().format('dddd, DD MMMM YYYY')
</script>

<template>
    <Head title="Dashboard" />

    <div class="grid grid-cols-12 gap-8">
        <div class="col-span-12">
            <WelcomeBanner :networkMonitors="datas.networkMonitors" :nocStats="datas.nocStats" />
        </div>
        <StatsTrouble :troubles="datas.troubles" :grafik="datas.grafik" :guest="datas.guest" />
        <!-- <StatsGuest :guest="datas.guest" /> -->
        <StatsDevice :invent="datas.invent" />

        <!-- <div class="col-span-12">
            <RevenueStreamWidget />
        </div> -->
        <!-- <div class="col-span-12 xl:col-span-6">
            <BestSellingWidget />
        </div>
        <div class="col-span-12 xl:col-span-6">
            <RevenueStreamWidget />
            <NotificationsWidget />
        </div> -->
    </div>
</template>
