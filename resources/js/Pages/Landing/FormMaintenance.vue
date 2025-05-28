<script setup lang="ts">
import { defineProps, ref, onMounted  } from 'vue';

import FooterWidget from './component/FooterWidget.vue';
import HeroWidget from './component/HeroWidget.vue';
import LayoutWidget from './component/LayoutWidget.vue';
import InMaintenance from './component/InMaintenance.vue'

const datas = defineProps({
    user: Object
})

// console.log('ini', datas.user)
</script>

<template>
    <Head title="Form Maintenance" />

    <layout-widget :user="datas.user">
        <HeroWidget />
        <InMaintenance />
        <FooterWidget />
    </layout-widget>
</template>
