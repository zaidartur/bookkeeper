<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import View from '@/Pages/Landing/FormTamu.vue';

const user = usePage().props.auth.user;

</script>

<template>
    <app-layout>
        <View :user="user" />
    </app-layout>
</template>
