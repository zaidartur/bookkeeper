<script setup>
import { computed, ref } from 'vue';
import Carousel from 'primevue/carousel';

const props = defineProps({
    galleries: {
        type: Array,
        default: () => []
    }
});

const defaultGallery = [
    { url: 'https://images.pexels.com/photos/17323801/pexels-photo-17323801.jpeg', title: 'Ruang Server & Rack Data Center', show_title: true },
    { url: 'https://images.pexels.com/photos/13963756/pexels-photo-13963756.jpeg', title: 'Infrastruktur Jaringan Intranet', show_title: true },
    { url: 'https://images.pexels.com/photos/1148820/pexels-photo-1148820.jpeg', title: 'Monitoring NOC Sentral', show_title: true },
    { url: 'https://images.pexels.com/photos/159304/network-cable-ethernet-computer-159304.jpeg', title: 'Kabel Fiber Optik & Patch Panel', show_title: true },
    { url: 'https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg', title: 'Pemeliharaan Switch Core', show_title: true },
    { url: 'https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg', title: 'Keamanan Siber & Firewall', show_title: true },
];

const galleryList = computed(() => {
    if (props.galleries && props.galleries.length > 0) {
        return props.galleries.map(g => ({
            url: g.image,
            title: g.title,
            show_title: g.show_title === true || g.show_title === 1 || g.show_title === '1'
        }));
    }
    return defaultGallery;
});

const responsiveOptions = ref([
    {
        breakpoint: '1400px',
        numVisible: 2,
        numScroll: 1
    },
    {
        breakpoint: '1199px',
        numVisible: 3,
        numScroll: 1
    },
    {
        breakpoint: '767px',
        numVisible: 2,
        numScroll: 1
    },
    {
        breakpoint: '575px',
        numVisible: 1,
        numScroll: 1
    }
]);
</script>

<template>
    <div id="highlights" class="py-6 px-6 lg:px-20 mx-0 my-12 lg:mx-20">
        <div class="text-center">
            <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">Galeri Dokumentasi</div>
        </div>
 
        <div class="card gap-4 mt-8 pb-2 md:pb-20 w-full" v-animateonscroll="{ enterClass: 'animate-enter fade-in-10 slide-in-from-b-20 animate-duration-1000' }">
            <Carousel :value="galleryList" :numVisible="2" :numScroll="1" :responsiveOptions="responsiveOptions" circular :autoplayInterval="3500">
                <template #item="slotProps">
                    <div class="border border-surface-200 dark:border-surface-700 rounded-xl m-2 p-3 bg-surface-0 dark:bg-surface-800 shadow-sm flex flex-col justify-between">
                        <div class="mb-2">
                            <div class="relative mx-auto h-64 overflow-hidden rounded-lg">
                                <img :src="slotProps.data.url" :alt="slotProps.data.title" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                            </div>
                        </div>
                        <div v-if="slotProps.data.show_title && slotProps.data.title" class="text-xs font-semibold text-surface-700 dark:text-surface-200 text-center truncate py-1">
                            {{ slotProps.data.title }}
                        </div>
                    </div>
                </template>
            </Carousel>
        </div>
    </div>
</template>
