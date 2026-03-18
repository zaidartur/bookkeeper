<script setup>
import { defineProps } from "vue";
import moment from 'moment';
import id from 'moment/dist/locale/id';

import Server from '@/Pages/Monitoring/View.vue';
// import NotificationsWidget from '@/Pages/Dashboard/component/NotificationsWidget.vue';
// import RevenueStreamWidget from '@/Pages/Dashboard/component/RevenueStreamWidget.vue';
// import StatsTrouble from '@/Pages/Dashboard/component/StatsTrouble.vue';
// import StatsGuest from '@/Pages/Dashboard/component/StatsGuest.vue';
// import StatsDevice from '@/Pages/Dashboard/component/StatsDevice.vue';

import AppLayout from "@/Layouts/AppLayout.vue";

const datas = defineProps({
    devices: Object,
})
// const _today = moment().format('dddd, DD MMMM YYYY')
console.log('res', datas.devices)
</script>

<template>
    <Head title="Monitoring" />

    <app-layout>
        <Server :lists="datas.devices" />
    </app-layout>
</template>
