<script setup>
import { onMounted, ref } from 'vue';
import Carousel from 'primevue/carousel';


const gallery = ref([
    {url: 'https://images.pexels.com/photos/17323801/pexels-photo-17323801.jpeg'},
    {url: 'https://images.pexels.com/photos/13963756/pexels-photo-13963756.jpeg'},
    {url: 'https://images.pexels.com/photos/1148820/pexels-photo-1148820.jpeg'},
    {url: 'https://images.pexels.com/photos/159304/network-cable-ethernet-computer-159304.jpeg'},
    {url: 'https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg'},
    {url: 'https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg'},
    {url: 'https://images.pexels.com/photos/4339335/pexels-photo-4339335.jpeg'},
    {url: 'https://images.pexels.com/photos/2881232/pexels-photo-2881232.jpeg'},
    {url: 'https://images.pexels.com/photos/430208/pexels-photo-430208.jpeg'},
    {url: 'https://images.pexels.com/photos/6466143/pexels-photo-6466143.jpeg'},
])

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
])
</script>

<template>
    <div id="highlights" class="py-6 px-6 lg:px-20 mx-0 my-12 lg:mx-20">
        <div class="text-center">
            <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">Galeri</div>
            <!-- <span class="text-muted-color text-2xl">&nbsp;</span> -->
        </div>
 
        <div class="card gap-4 mt-15 pb-2 md:pb-20 w-full" v-animateonscroll="{ enterClass: 'animate-enter fade-in-10 slide-in-from-b-20 animate-duration-1000' }">
            <Carousel :value="gallery" :numVisible="2" :numScroll="1" :responsiveOptions="responsiveOptions" circular :autoplayInterval="3000">
                <template #item="slotProps">
                    <div class="border border-surface-200 dark:border-surface-700 rounded m-2 p-4">
                        <div class="mb-4">
                            <div class="relative mx-auto">
                                <img :src="slotProps.data.url" class="w-full rounded" />
                            </div>
                        </div>
                    </div>
                </template>
            </Carousel>
        </div>
    </div>
</template>
