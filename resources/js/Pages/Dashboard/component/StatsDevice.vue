<script setup>
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import id from 'moment/dist/locale/id';
import { computed } from 'vue';

const toast = useToast();
moment.locale('id');
const _month = moment().format('MMMM');
const _year  = new Date().getFullYear();

const datas = defineProps({
    invent: [Array, Object]
});

const items = computed(() => {
    if (Array.isArray(datas.invent)) return datas.invent;
    if (datas.invent && Array.isArray(datas.invent.lists)) return datas.invent.lists;
    if (datas.invent && typeof datas.invent === 'object') return Object.values(datas.invent);
    return [];
});

const countCctv = computed(() => {
    return items.value.filter(item => {
        const cat = (item.category?.name || '').toLowerCase();
        const brand = (item.brand?.brand || '').toLowerCase();
        const type = (item.type || '').toLowerCase();
        return cat.includes('cctv') || cat.includes('kamera') || cat.includes('camera') || type.includes('cctv');
    }).length;
});

const countWifi = computed(() => {
    return items.value.filter(item => {
        const cat = (item.category?.name || '').toLowerCase();
        const brand = (item.brand?.brand || '').toLowerCase();
        const type = (item.type || '').toLowerCase();
        return cat.includes('wifi') || cat.includes('wi-fi') || cat.includes('access point') || cat.includes('ap') || type.includes('wifi') || type.includes('access point');
    }).length;
});

const countRouter = computed(() => {
    return items.value.filter(item => {
        const cat = (item.category?.name || '').toLowerCase();
        const brand = (item.brand?.brand || '').toLowerCase();
        const type = (item.type || '').toLowerCase();
        return cat.includes('router') || cat.includes('switch') || cat.includes('hub') || type.includes('router') || type.includes('switch');
    }).length;
});

const countTotal = computed(() => {
    return items.value.length;
});

const mouseOnCard = (e) => {
    let cardId = e.toString();
    const dialogElement = document.getElementById(cardId);
    if (dialogElement && !dialogElement.classList.contains('is-hover')) {
        dialogElement.classList.add('is-hover');
    }
};

const mouseOutCard = (e) => { 
    const cardId = e.toString();
    const dialogElement = document.getElementById(cardId);
    if (dialogElement) {
        dialogElement.classList.remove('is-hover');
    }
};

const _detail = (type, count) => {
    toast.add({ severity: 'info', summary: `Perangkat ${type}`, detail: `Total terdaftar: ${count} unit`, life: 3000 });
};
</script>

<template>
    <div class="col-span-12 -mb-5">
        <div class="font-semibold text-xl"><i class="pi pi-desktop"></i> Data Perangkat</div>
    </div>
    <div id="cctv" class="col-span-12 lg:col-span-6 xl:col-span-3 cursor-pointer" @mouseover="mouseOnCard('cctv')" @mouseout="mouseOutCard('cctv')" @click="_detail('CCTV', countCctv)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">CCTV</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ countCctv > 0 ? `${countCctv} Unit` : '0' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-camera text-blue-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div id="wifi" class="col-span-12 lg:col-span-6 xl:col-span-3 cursor-pointer" @mouseover="mouseOnCard('wifi')" @mouseout="mouseOutCard('wifi')" @click="_detail('Wi-Fi', countWifi)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Wi-Fi</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ countWifi > 0 ? `${countWifi} Unit` : '0' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-wifi text-orange-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div id="router" class="col-span-12 lg:col-span-6 xl:col-span-3 cursor-pointer" @mouseover="mouseOnCard('router')" @mouseout="mouseOutCard('router')" @click="_detail('Router-Switch', countRouter)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Router-Switch</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ countRouter > 0 ? `${countRouter} Unit` : '0' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-sitemap text-cyan-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div id="invent" class="col-span-12 lg:col-span-6 xl:col-span-3 cursor-pointer" @mouseover="mouseOnCard('invent')" @mouseout="mouseOutCard('invent')" @click="_detail('Inventaris', countTotal)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Inventaris</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ countTotal > 0 ? `${countTotal} Barang` : '0' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-box text-purple-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
    .is-hover {
        box-shadow: 0 0 10px rgba(180, 180, 180, 0.5);
    }
</style>