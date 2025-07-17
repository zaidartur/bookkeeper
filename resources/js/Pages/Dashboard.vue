<script setup>
import { defineProps } from "vue";
import View from '@/Pages/Dashboard/View.vue';
import AppLayout from "@/Layouts/AppLayout.vue";


const init = defineProps({
    troubles: Object,
    guest: Object,
})
</script>

<template>
    <app-layout>
        <View :troubles="init.troubles" :guest="init.guest" />
    </app-layout>
</template>
