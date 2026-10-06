<script setup lang="ts">
import { defineProps } from 'vue';

import FeaturesWidget from './component/FeaturesWidget.vue';
import FooterWidget from './component/FooterWidget.vue';
import HeroWidget from './component/HeroWidget.vue';
import HighlightsWidget from './component/HighlightsWidget.vue';
import PricingWidget from './component/PricingWidget.vue';
import LayoutWidget from './component/LayoutWidget.vue';

const datas = defineProps({
    user: Object,
    api: Object,
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
    <Head :title="'Landing'" />

    <layout-widget :user="datas.user">
        <HeroWidget />
        <FeaturesWidget :cards="datas.cards" />
        <HighlightsWidget :galleries="datas.galleries" />
        <PricingWidget :services="datas.services" />
        <FooterWidget :api="datas.api" :agent="datas.agent" />
    </layout-widget>
</template>
