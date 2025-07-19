<script setup>
import { usePage } from '@inertiajs/vue3';
import View from '@/Pages/Landing/View.vue';

const user = usePage().props.auth.user;
const init = defineProps({
    myapi: Object,
    agent: Object,
})
</script>

<template>
    <View :user="user" :api="init.myapi" :agent="init.agent" />
</template>
