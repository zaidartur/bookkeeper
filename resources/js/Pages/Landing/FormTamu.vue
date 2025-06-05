<script setup lang="ts">
import { defineProps, ref, onMounted  } from 'vue';

import FooterWidget from './component/FooterWidget.vue';
import HeroWidget from './component/HeroWidget.vue';
import LayoutWidget from './component/LayoutWidget.vue';
import GuestForm from './component/GuestForm.vue'

const datas = defineProps({
    user: Object,
    total: Number,
    today: Number,
})

// console.log('ini', datas.user)
</script>

<template>
    <Head title="Buku Tamu" />

    <layout-widget :user="datas.user">
        <HeroWidget />
        <GuestForm :total="datas.total" :today="datas.today" />
        <FooterWidget />
    </layout-widget>
</template>
