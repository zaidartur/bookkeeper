<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import FormMtc from '@/Pages/Landing/FormMaintenance.vue';

const user = usePage().props.auth.user;

</script>

<template>
    <FormMtc :user="user" />
</template>
