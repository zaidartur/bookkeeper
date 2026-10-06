<script setup>
import { computed } from 'vue';

const props = defineProps({
    cards: {
        type: Array,
        default: () => []
    }
});

const defaultCards = [
    {
        title: 'Keandalan',
        subtitle: 'Menjamin ketersediaan data dan jaringan untuk mendukung operasional pemerintah tanpa henti.',
        icon: 'pi pi-fw pi-lock'
    },
    {
        title: 'Keamanan',
        subtitle: 'Melindungi data sensitif pemerintah dan masyarakat dengan standar keamanan tertinggi.',
        icon: 'pi pi-fw pi-shield'
    },
    {
        title: 'Efisiensi',
        subtitle: 'Mengoptimalkan penggunaan sumber daya IT untuk pelayanan yang lebih cepat dan efektif.',
        icon: 'pi pi-fw pi-hourglass'
    },
    {
        title: 'Transparansi',
        subtitle: 'Mendukung keterbukaan informasi publik melalui pengelolaan data yang terpusat dan terstandar.',
        icon: 'pi pi-fw pi-eye'
    },
    {
        title: 'Inovasi',
        subtitle: 'Mendorong pemanfaatan teknologi informasi untuk inovasi dalam pelayanan publik.',
        icon: 'pi pi-fw pi-star'
    },
    {
        title: 'Bantuan Teknis 24/7',
        subtitle: 'Tim responsif untuk mengatasi masalah teknis yang mungkin timbul.',
        icon: 'pi pi-fw pi-question-circle'
    }
];

const gradients = [
    'linear-gradient(90deg, rgba(253, 228, 165, 0.2), rgba(187, 199, 205, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(187, 199, 205, 0.2))',
    'linear-gradient(90deg, rgba(145, 226, 237, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(172, 180, 223, 0.2))',
    'linear-gradient(90deg, rgba(145, 226, 237, 0.2), rgba(172, 180, 223, 0.2)), linear-gradient(180deg, rgba(172, 180, 223, 0.2), rgba(246, 158, 188, 0.2))',
    'linear-gradient(90deg, rgba(187, 199, 205, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(145, 210, 204, 0.2))',
    'linear-gradient(90deg, rgba(187, 199, 205, 0.2), rgba(246, 158, 188, 0.2)), linear-gradient(180deg, rgba(145, 226, 237, 0.2), rgba(160, 210, 250, 0.2))',
    'linear-gradient(90deg, rgba(251, 199, 145, 0.2), rgba(246, 158, 188, 0.2)), linear-gradient(180deg, rgba(172, 180, 223, 0.2), rgba(212, 162, 221, 0.2))'
];

const iconColors = [
    { bg: 'bg-yellow-200', text: 'text-yellow-700' },
    { bg: 'bg-cyan-200', text: 'text-cyan-700' },
    { bg: 'bg-indigo-200', text: 'text-indigo-700' },
    { bg: 'bg-slate-200', text: 'text-slate-700' },
    { bg: 'bg-orange-200', text: 'text-orange-700' },
    { bg: 'bg-pink-200', text: 'text-pink-700' },
];

const displayCards = computed(() => {
    if (props.cards && props.cards.length > 0) {
        return props.cards;
    }
    return defaultCards;
});
</script>

<template>
    <div id="features" class="py-6 px-6 lg:px-20 mt-8 mx-0 lg:mx-20">
        <div class="grid grid-cols-12 gap-4 justify-center">
            <div class="col-span-12 text-center mt-20 mb-6">
                <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">Komitmen Kami</div>
                <span class="text-muted-color text-2xl">&nbsp;</span>
            </div>

            <div
                v-for="(card, index) in displayCards"
                :key="card.id || index"
                class="col-span-12 md:col-span-6 lg:col-span-4 p-0 lg:pr-6 lg:pb-6 mt-6 lg:mt-0"
                v-animateonscroll="{ enterClass: 'animate-enter fade-in-10 animate-duration-1000', leaveClass: 'animate-leave fade-out-0' }"
            >
                <div :style="{ height: '180px', padding: '2px', borderRadius: '10px', background: gradients[index % gradients.length] }">
                    <div class="p-4 bg-surface-0 dark:bg-surface-900 h-full flex flex-col justify-between" style="border-radius: 8px">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center justify-center" :class="[iconColors[index % iconColors.length].bg]" style="width: 3.2rem; height: 3.2rem; border-radius: 10px">
                                    <i :class="[card.icon, iconColors[index % iconColors.length].text, '!text-2xl']"></i>
                                </div>
                            </div>
                            <h5 class="mb-1 text-surface-900 dark:text-surface-0 text-xl font-semibold">{{ card.title }}</h5>
                            <span class="text-surface-600 dark:text-surface-200 text-sm line-clamp-2">{{ card.subtitle }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
