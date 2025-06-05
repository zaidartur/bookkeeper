<script setup lang="ts">
import { defineProps, ref, onMounted  } from 'vue';

import FeaturesWidget from './component/FeaturesWidget.vue';
import FooterWidget from './component/FooterWidget.vue';
import HeroWidget from './component/HeroWidget.vue';
import HighlightsWidget from './component/HighlightsWidget.vue';
import PricingWidget from './component/PricingWidget.vue';
import TopbarWidget from './component/TopbarWidget.vue';
import LayoutWidget from './component/LayoutWidget.vue';

const datas = defineProps({
    user: Object
})

// console.log('ini', datas.user)
</script>

<template>
    <Head :title="'Landing'" />

    <layout-widget :user="datas.user">
        <HeroWidget />
        <FeaturesWidget />
        <HighlightsWidget />
        <PricingWidget />
        <FooterWidget />
    </layout-widget>
</template>
