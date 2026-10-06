<script setup>
import { defineProps } from "vue";
import View from '@/Pages/Dashboard/View.vue';
import AppLayout from "@/Layouts/AppLayout.vue";


const init = defineProps({
    troubles: Object,
    guest: Object,
    grafik: Object,
    inventory: Object,
    network_monitors: Object,
    noc_stats: Object,
})
</script>

<template>
    <app-layout>
        <View 
            :troubles="init.troubles" 
            :guest="init.guest" 
            :grafik="init.grafik" 
            :invent="init.inventory" 
            :networkMonitors="init.network_monitors"
            :nocStats="init.noc_stats"
        />
    </app-layout>
</template>
