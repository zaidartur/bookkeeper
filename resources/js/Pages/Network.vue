<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import View from '@/Pages/Network/View.vue';

const user  = usePage().props.auth.user;
const datas = defineProps({
    subnets: Object,
    networks: Object,
    lists: Object,
})

</script>

<template>
    <app-layout>
        <View :user="user" :subnets="datas.subnets" :networks="datas.networks" :lists="datas.lists"/>
    </app-layout>
</template>
