<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import View from '@/Pages/Petugas/View.vue';

const user = usePage().props.auth.user;
const props = defineProps({
    petugas: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({
            total: 0,
            jaringan: 0,
            datacenter: 0,
            helpdesk: 0
        })
    }
});
</script>

<template>
    <app-layout>
        <View :user="user" :petugas="props.petugas" :summary="props.summary" />
    </app-layout>
</template>
