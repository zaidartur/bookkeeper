<script setup>
import { defineProps } from "vue";
import { usePage } from '@inertiajs/vue3';
import View from '@/Pages/Landing/FormTamu.vue';

const user = usePage().props.auth.user;
const datas = defineProps({
    total: Number,
    today: Number,
})

</script>

<template>
    <app-layout>
        <View :user="user" :total="datas.total" :today="datas.today" />
    </app-layout>
</template>
