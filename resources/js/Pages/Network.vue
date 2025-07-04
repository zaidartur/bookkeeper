<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import View from '@/Pages/Network/View.vue';

const user  = usePage().props.auth.user;
const datas = defineProps({
    router: Object,
})

</script>

<template>
    <app-layout>
        <View :user="user" :routers="datas.router" />
    </app-layout>
</template>
