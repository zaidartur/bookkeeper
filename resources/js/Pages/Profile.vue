<script setup>
import { usePage, useForm } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import View from '@/Pages/Profile/View.vue';

const user = usePage().props.auth.user;
const init = defineProps({
    activity: Object,
    lists: Object,
})
</script>

<template>
    <app-layout>
        <View :user="user" :activity="init.activity" :lists="init.lists" />
    </app-layout>
</template>