<script setup>
import { defineProps } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import View from '@/Pages/Inventory/Master.vue';
const user = usePage().props.auth.user
const datas = defineProps({
    lists: Object,
    category: Object,
    brand: Object,
    location: Object
})

</script>

<template>
    <app-layout>
        <View :user="user" :cat="datas.category" :brand="datas.brand" :loc="datas.location" />
    </app-layout>
</template>
