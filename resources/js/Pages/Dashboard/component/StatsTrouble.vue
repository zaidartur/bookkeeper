<script setup>
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import id from 'moment/dist/locale/id';
import { onMounted, ref } from 'vue';
import Popover from 'primevue/popover';

const toast = useToast();
moment.locale('id')

const datas = defineProps({
    troubles: Object
})
const lists = ref(new Array())
const detailTrouble = ref()
const category = ref(null)

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

const getSeverity = (tr) => {
    switch (tr) {
        case 'finish':
            return 'success';

        case 'progress':
            return 'warn';

        default:
            return null;
    }
};

const _detail = (val, title, event) => {
    if (val.length > 0) {
        lists.value = []
        val.map((v) => {
            lists.value.push(v)
        })

        console.log(lists.value)
        category.value = title
        detailTrouble.value.toggle(event)
    } else {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'No data on this category', life: 3000 });
    }
}
</script>

<template>
    <Toast />

    <div class="col-span-12 -mb-5">
        <div class="font-semibold text-xl"><i class="pi pi-wrench"></i> Troubleshoot</div>
    </div>
    <div id="lokal" class="col-span-12 lg:col-span-6 xl:col-span-3" style="cursor: pointer" @mouseover="mouseOnCard('lokal')" @mouseout="mouseOutCard('lokal')" @click="_detail(datas.troubles?.lokal, 'Lokal-Kominfo', $event)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Lokal-Kominfo</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.troubles?.lokal.length > 0 ? (datas.troubles?.lokal.length + ' Trouble') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-sitemap text-blue-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Klik </span>
            <span class="text-muted-color">untuk melihat detail</span>
        </div>
    </div>
    <div id="intra" class="col-span-12 lg:col-span-6 xl:col-span-3" style="cursor: pointer" @mouseover="mouseOnCard('intra')" @mouseout="mouseOutCard('intra')" @click="_detail(datas.troubles?.intra, 'Intra OPD', $event)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Intra OPD</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.troubles?.intra.length > 0 ? (datas.troubles?.intra.length + ' Trouble') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-sitemap text-orange-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Klik </span>
            <span class="text-muted-color">untuk melihat detail</span>
        </div>
    </div>
    <div id="metro" class="col-span-12 lg:col-span-6 xl:col-span-3" style="cursor: pointer" @mouseover="mouseOnCard('metro')" @mouseout="mouseOutCard('metro')" @click="_detail(datas.troubles?.metro, 'Metro Kecamatan', $event)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Metro Kecamatan</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.troubles?.metro.length > 0 ? (datas.troubles?.metro.length + ' Trouble') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-sitemap text-cyan-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Klik </span>
            <span class="text-muted-color">untuk melihat detail</span>
        </div>
    </div>
    <div id="internet" class="col-span-12 lg:col-span-6 xl:col-span-3" style="cursor: pointer" @mouseover="mouseOnCard('internet')" @mouseout="mouseOutCard('internet')" @click="_detail(datas.troubles?.internet, 'Internet', $event)">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Internet</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                        {{ datas.troubles?.internet.length > 0 ? (datas.troubles?.internet.length + ' Trouble') : 'Nihil' }}
                    </div>
                </div>
                <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-sitemap text-purple-500 !text-xl"></i>
                </div>
            </div>
            <span class="text-primary font-medium">Klik </span>
            <span class="text-muted-color">untuk melihat detail</span>
        </div>
    </div>

    <Popover ref="detailTrouble">
        <div class="flex flex-col gap-4 w-[50rem]">
            <DataTable :value="lists" paginator selectionMode="single" :rows="10" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="max-width: 50rem">
                <template #header>
                    <div class="flex justify-between">
                        <h4 class="font-bold text-xl text-center w-full">Daftar Trouble <span class="text-primary">{{ category }}</span></h4>
                    </div>
                </template>
                <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                <Column field="tgl_trouble" header="Waktu" class="text-center" style="width: 20%">
                    <template #body="{ data }">
                        <label>{{ data.tgl_trouble  }} {{ data.jam_trouble }}</label>
                    </template>
                </Column>
                <Column field="problem" header="Deskripsi" class="text-center" style="width: 25%"></Column>
                <Column field="lokasi" header="Lokasi" class="text-center" style="width: 25%"></Column>
                <Column field="petugas" header="Petugas" class="text-center" style="width: 20%"></Column>
                <Column field="status" header="Status" class="text-center" style="width: 10%">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status.toLowerCase()" :severity="getSeverity(slotProps.data.status)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </Popover>
</template>

<style lang="scss">
    .is-hover {
        box-shadow: 0 0 10px rgba(180, 180, 180, 0.5);
    }
</style>