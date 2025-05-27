<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import View from '@/Pages/Landing/View.vue';

const user = usePage().props.auth.user;

</script>

<template>
    <View :user="user" />
</template>
