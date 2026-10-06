<script setup>
import { computed } from 'vue';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    }
});

const defaultServices = [
    {
        title: 'Pengelolaan Pusat Data',
        image: '/assets/images/landing/free.svg',
        points: ['Keamanan Fisik & Logis', 'Stabilitas Operasional & Colocation', 'Manajemen Kapasitas Server & Backup']
    },
    {
        title: 'Infrastruktur Jaringan Pemda',
        image: '/assets/images/landing/startup.svg',
        points: ['Jaringan Intra-Pemerintah OPD', 'Konektivitas Internet & Metro-E', 'Manajemen Alokasi IP & Bandwidth']
    },
    {
        title: 'Helpdesk & Mitigasi Gangguan',
        image: '/assets/images/landing/enterprise.svg',
        points: ['Layanan Tiket Gangguan Cepat (SLA 4 Jam)', 'Pemeliharaan Rutin Perangkat Jaringan', 'Konsultasi & Pendampingan Teknis IT']
    }
];

const serviceList = computed(() => {
    if (props.services && props.services.length > 0) {
        return props.services.map(s => ({
            id: s.id,
            title: s.title,
            image: s.image || '/assets/images/landing/free.svg',
            points: s.content ? s.content.split('\n').map(p => p.trim()).filter(p => p.length > 0) : []
        }));
    }
    return defaultServices;
});
</script>

<template>
    <div id="pricing" class="py-6 px-6 lg:px-20 my-2 md:my-6">
        <div class="text-center mb-6">
            <div class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl">Layanan IT Terpadu</div>
            <span class="text-muted-color text-2xl">&nbsp;</span>
        </div>

        <div class="grid grid-cols-12 gap-6 justify-between mt-10 md:mt-0">
            <div
                v-for="(item, idx) in serviceList"
                :key="item.id || idx"
                class="col-span-12 lg:col-span-4 p-0 md:p-2"
                v-animateonscroll="{ enterClass: 'animate-enter fade-in-10 zoom-in-50 animate-duration-1000' }"
            >
                <div class="p-6 flex flex-col justify-between h-full bg-surface-0 dark:bg-surface-900 border-surface-200 dark:border-surface-700 pricing-card cursor-pointer border-2 hover:border-primary duration-300 transition-all rounded-2xl shadow-sm">
                    <div>
                        <div class="text-surface-900 dark:text-surface-0 text-center my-4 text-2xl font-bold min-h-[4rem] flex items-center justify-center">
                            {{ item.title }}
                        </div>
                        <div class="h-32 flex items-center justify-center my-4">
                            <img :src="item.image" class="max-h-full max-w-[200px] object-contain mx-auto" :alt="item.title" />
                        </div>
                        
                        <Divider class="w-full bg-surface-200 my-4"></Divider>
                        
                        <ul class="my-4 list-none p-0 flex text-surface-900 dark:text-surface-0 flex-col px-4 space-y-2">
                            <li
                                v-for="(pt, pIdx) in item.points"
                                :key="pIdx"
                                class="py-1 flex items-start"
                            >
                                <i class="pi pi-fw pi-check text-lg text-cyan-500 mr-2 mt-0.5 flex-shrink-0"></i>
                                <span class="text-sm leading-normal text-surface-700 dark:text-surface-200">{{ pt }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
