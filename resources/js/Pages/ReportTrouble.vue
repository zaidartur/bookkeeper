<script setup>
import { defineProps } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import View from '@/Pages/Trouble/Report.vue';
const user = usePage().props.auth.user
const datas = defineProps({
    lists: Object,
    dates: Object,
    metrics: Object,
})

</script>

<template>
    <app-layout>
        <View :user="user" :lists="datas.lists" :dates="datas.dates" :metrics="datas.metrics" />
    </app-layout>
</template>
