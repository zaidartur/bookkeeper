<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import View from '@/Pages/Network/View.vue';

const user  = usePage().props.auth.user;
const datas = defineProps({
    router: [Object, Array, String],
    routers_list: Array,
    subnets: Array,
    cidrs: Array,
    categories: Array,
})

</script>

<template>
    <app-layout>
        <View 
            :user="user" 
            :routers="datas.router" 
            :routers_list="datas.routers_list"
            :subnets="datas.subnets" 
            :cidrs="datas.cidrs" 
            :categories="datas.categories" 
        />
    </app-layout>
</template>
