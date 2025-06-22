<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import Report from '@/Pages/Maintenance/Report.vue';

const user = usePage().props.auth.user;

</script>

<template>
    <app-layout>
        <Report :user="user" />
    </app-layout>
</template>
