<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import Report from '@/Pages/Maintenance/Report.vue';

const user = usePage().props.auth.user;
const init = defineProps({
    lists: Object,
})

</script>

<template>
    <app-layout>
        <Report :user="user" :lists="init.lists" />
    </app-layout>
</template>
