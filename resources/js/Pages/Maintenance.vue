<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import View from '@/Pages/Maintenance/View.vue';

const user = usePage().props.auth.user;

</script>

<template>
    <app-layout>
        <View :user="user" />
    </app-layout>
</template>
