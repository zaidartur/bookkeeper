<script setup>
import { usePage } from '@inertiajs/vue3';
import View from '@/Pages/Landing/View.vue';

const user = usePage().props.auth.user;
const init = defineProps({
    myapi: Object,
    agent: Object,
    cards: {
        type: Array,
        default: () => []
    },
    galleries: {
        type: Array,
        default: () => []
    },
    services: {
        type: Array,
        default: () => []
    },
});
</script>

<template>
    <View
        :user="user"
        :api="init.myapi"
        :agent="init.agent"
        :cards="init.cards"
        :galleries="init.galleries"
        :services="init.services"
    />
</template>
