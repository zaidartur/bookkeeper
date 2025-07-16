<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import Report from '@/Pages/Guest/Report.vue';

const user = usePage().props.auth.user;

const datas = defineProps({
    lists: Object,
    dates: Object,
})

</script>

<template>
    <app-layout>
        <Report :user="user" :lists="datas.lists" :dates="datas.dates" />
    </app-layout>
</template>
