<script setup>
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import id from 'moment/dist/locale/id';
import { onMounted, ref } from 'vue';

const toast = useToast();
moment.locale('id')
const _month = moment().format('MMMM')
const _year  = new Date().getFullYear()

const datas = defineProps({
    guest: Object
})
const lists = ref(new Array())

onMounted(() => {
    //
})

const mouseOnCard = (e) => {
    let cardId = e.toString()
    const dialogElement = document.getElementById(cardId)
    if (!dialogElement.classList.contains('is-hover')) {
        dialogElement.classList.add('is-hover') 
    } 
}

const mouseOutCard = (e) => { 
    const cardId = e.toString()
    const dialogElement = document.getElementById(cardId)
    dialogElement.classList.remove('is-hover') 
}

const _detail = (val) => {
    toast.add({ severity: 'info', summary: val, detail: 'Lorem ipsum dolor sit amet', life: 3000 });
}
</script>

<template>
    <div class="col-span-12 -mb-5">
        <div class="font-semibold text-xl"><i class="pi pi-address-book"></i> Data Tamu</div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Total Semua Tamu</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.guest?.total ? (datas.guest?.total + ' Orang') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-users text-blue-500 !text-xl"></i>
                </div>
            </div>
            <!-- <span class="text-primary font-medium">Klik di sini </span>
            <span class="text-muted-color">untuk melihat detail</span> -->
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Tamu Bulan Ini</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.guest?.months ? (datas.guest?.months + ' Orang') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-calendar text-orange-500 !text-xl"></i>
                </div>
            </div>
            <!-- <span class="text-primary font-medium">Klik di sini </span>
            <span class="text-muted-color">untuk melihat detail</span> -->
        </div>
    </div>
    <div id="intra" class="col-span-12 lg:col-span-6 xl:col-span-3" style="cursor: pointer" @mouseover="mouseOnCard('intra')" @mouseout="mouseOutCard('intra')" @click="_detail('intra')">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Tamu Hari Ini</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.guest?.today ? (datas.guest?.today + ' Orang') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-calendar-clock text-cyan-500 !text-xl"></i>
                </div>
            </div>
            <!-- <span class="text-primary font-medium">Klik di sini </span>
            <span class="text-muted-color">untuk melihat detail</span> -->
        </div>
    </div>
    <!-- <div id="metro" class="col-span-12 lg:col-span-6 xl:col-span-3" style="cursor: pointer" @mouseover="mouseOnCard('metro')" @mouseout="mouseOutCard('metro')" @click="_detail('metro')">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Internet</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.troubles?.internet ? (datas.troubles?.internet + ' Trouble') : 'No Trouble' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-sitemap text-purple-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Klik di sini </span>
            <span class="text-muted-color">untuk melihat detail</span>
        </div>
    </div> -->
</template>

<style lang="scss">
    .is-hover {
        box-shadow: 0 0 10px rgba(180, 180, 180, 0.5);
    }
</style>