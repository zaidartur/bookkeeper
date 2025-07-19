<script setup lang="ts">
import { defineProps, ref, onMounted  } from 'vue';
import ScrollTop from 'primevue/scrolltop';

import TopbarWidget from '@/Pages/Landing/component/TopbarWidget.vue';

const datas = defineProps({
    user: Object
})

// console.log('ini', datas.user)
</script>

<template>
    <!-- <Head :title="datas.user ? 'Landing' : 'Unsign'" /> -->

    <div class="bg-surface-0 dark:bg-surface-900">
        <!-- <div id="home" class="landing-wrapper overflow-hidden"> -->
        <div id="home" class="landing overflow-hidden">
            <div class="py-6 px-6 mx-0 md:mx-12 lg:mx-20 lg:px-20 flex items-center justify-between relative lg:static">
                <TopbarWidget :auth="datas.user" />
            </div>
            
            <slot></slot>
            <ScrollTop />
        </div>
    </div>
</template>
