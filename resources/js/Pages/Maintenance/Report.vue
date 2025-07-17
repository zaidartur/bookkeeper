<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import id from 'moment/dist/locale/id';
import { FilterMatchMode } from '@primevue/core/api';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Fieldset from 'primevue/fieldset';

const toast = useToast();
const datas = defineProps({
    lists: Object,
})
moment.locale('id')

const loading = ref(true)
const dataMaintenance = ref(Array())
const initData = () => {
    dataMaintenance.value = []
    if (datas.lists.length > 0) {
        datas.lists.map((_list) => {
            dataMaintenance.value.push(_list)
        })
    }
    
    loading.value = false;
}

onMounted(() => {
    initData()
    form.reset()
})

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
initData()
const setCategories = (cat) => {
    let _name = ''
    categories.value.some((_cat) => {
        if (_cat.value === cat) {
            _name = _cat.name
            return _cat.name
        }
    })

    return _name
}

const form = useForm({
    uuid: '',
    mulai: null,
    jam: null,
    judul: null,
    deskripsi: '',
    lokasi: '',
    alur: null,
    problem: null,
    petugas: '',
    foto_awal: null,
    foto_akhir: null,
});

const detailDialog = ref(false)
const src = ref(false)
const srcFinish = ref(false)
const filters = ref()
const dateFilter = ref(null)
const now = new Date()
const selectedCategory = ref()
const categories = ref([
    { name: 'Lokal Kominfo-Setda', value: 'lokal' },
    { name: 'Intra OPD', value: 'opd' },
    { name: 'Metro Kecamatan', value: 'metro' },
    { name: 'Internet', value: 'internet' },
    { name: 'Petugas', value: 'petugas' }
]);

const selectingCat = () => {
    if (selectedCategory.value) {
        console.log(selectedCategory.value.value)
    } else {
        console.log('no selected')
    }
}

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        tgl_mulai: { value: null, matchMode: FilterMatchMode.EQUALS },
        judul: { value: null, matchMode: FilterMatchMode.CONTAINS },
        lokasi: { value: null, matchMode: FilterMatchMode.CONTAINS },
        petugas: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
};

initFilters()

const clearFilter = () => {
    initFilters();
    dateFilter.value = null
    initData()
};

const filterDate = (event) => {
    const [start, end] = event

    if (!start && !end) {
        dataMaintenance.value = datas.lists
    } else if (start && !end) {
        console.log('start', moment(start).format('YYYY-MM-DD'))
        if (datas.lists.length > 0) {
            dataMaintenance.value = datas.lists.filter((dl) => {
                const itemStart = moment(start).format('YYYY-MM-DD')
                const itemDate = moment(dl.tanggal_mulai).format('YYYY-MM-DD')
                return itemStart === itemDate
            })
        } else {
            dataMaintenance.value = []
        }
    } else if (start && end) {
        if (datas.lists.length > 0) {
            dataMaintenance.value = datas.lists.filter((dl) => {
                const itemStart = moment(start).format('YYYY-MM-DD')
                const itemEnd = moment(end).format('YYYY-MM-DD')
                const itemDate = moment(dl.tanggal_mulai).format('YYYY-MM-DD')
                
                return itemDate >= itemStart && itemDate <= itemEnd
            })
        } else {
            dataMaintenance.value = []
        }
    }
}

const resetDate = () => {
    dateFilter.value = null
}

const alert_response = (rsp) => {
    if (rsp.status === 'failed') {
        toast.add({ severity: 'error', summary: 'Error', detail: rsp.msg, life: 3000 });
    } else if (rsp.status === 'success') {
        toast.add({ severity: 'success', summary: 'Berhasil', detail: rsp.msg, life: 3000 });
    }
}

const detailData = (slot) => {
    if (slot) {
        src.value           = false
        srcFinish.value     = false

        form.mulai       = moment(slot.tanggal_mulai).format('DD MMMM YYYY')
        form.jam         = slot.jam_mlai
        form.judul       = slot.judul
        form.deskripsi   = slot.deskripsi
        form.lokasi      = slot.lokasi
        form.alur        = slot.alur_perawatan
        form.problem     = slot.problem
        form.petugas     = slot.petugas
        if (slot.foto_sebelum) {
            src.value       = slot.foto_sebelum
        }
        if (slot.foto_setelah) {
            srcFinish.value = slot.foto_setelah
        }

        detailDialog.value = true
    }
}

const showImage = (img) => {
    // console.log(img)
    // window.open(img, '_blank')
    return false
}

</script>

<template>
    <Toast />
    <ConfirmDialog></ConfirmDialog>
    <Head title="Laporan Maintenance" />

    <Card class="w-full">
        <template #title><i class="pi pi-book"></i> Laporan Maintenance</template>
        <template #content>
            <div class="mt-5">
                <DataTable v-model:filters="filters" :value="dataMaintenance" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading" :globalFilterFields="['tgl_mulai', 'judul', 'lokasi', 'petugas']">
                    <template #header>
                        <div class="flex justify-between">
                            <div class="flex w-6/12 gap-4">
                                <Button type="button" icon="pi pi-filter-slash" label="Bersihkan Filter" outlined @click="clearFilter()" />
                                <DatePicker 
                                    v-model="dateFilter"
                                    selectionMode="range"
                                    :manualInput="false" 
                                    dateFormat="dd/mm/yy"
                                    placeholder="Filter Tanggal"
                                    :maxDate="now"
                                    showButtonBar 
                                    @value-change="filterDate($event)"
                                    @clear-click="resetDate"
                                    class="w-4/12"
                                />
                            </div>
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Pencarian" />
                            </IconField>
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column field="tanggal_mulai" header="Tanggal Mulai" style="width: 15%">
                        <template #body="slotProps">
                            <label>{{ moment(slotProps.data.tanggal_mulai).format("DD MMM YYYY") +' '+ slotProps.data.jam_mulai }}</label>
                        </template>
                    </Column>
                    <Column field="judul" header="Judul" style="width: 20%" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            {{ data.judul }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari judul" />
                        </template>
                    </Column>
                    <Column field="lokasi" header="Lokasi" style="width: 20%" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            {{ data.lokasi }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari lokasi" />
                        </template>
                    </Column>
                    <Column field="petugas" header="Petugas" style="width: 25%" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            {{ data.petugas }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari petugas" />
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 15%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-search" @click="detailData(slotProps.data)" v-tooltip.bottom="'Lihat Detail'" severity="secondary" rounded />
                            </div>
                        </template>
                    </Column>

                    <template #paginatorcontainer="{ first, last, page, pageCount, prevPageCallback, nextPageCallback, totalRecords }">
                        <div class="flex items-center gap-4 border border-primary bg-transparent rounded-full w-full py-1 px-2 justify-between">
                            <Button icon="pi pi-chevron-left" rounded text @click="prevPageCallback" :disabled="page === 0" />
                            <div class="text-color font-medium">
                                <span class="hidden sm:block">Showing {{ first }} to {{ last }} of {{ totalRecords }}</span>
                                <span class="block sm:hidden">Page {{ page + 1 }} of {{ pageCount }}</span>
                            </div>
                            <Button icon="pi pi-chevron-right" rounded text @click="nextPageCallback" :disabled="page === pageCount - 1" />
                        </div>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Dialog v-model:visible="detailDialog" maximizable modal header="Detail Maintenance" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="flex flex-col md:flex-row">
            <div class="w-full md:w-5/12 flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4 w-45">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Tanggal Mulai </label>
                        <InputText v-model="form.mulai" placeholder="Tanggal Mulai" class="w-full" readonly />
                    </div>
                    <div class="flex-auto">
                        <label for="petugas" class="font-bold block"> Jam Mulai </label>
                        <InputText v-model="form.jam" placeholder="Jam Mulai" class="w-full" readonly />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Judul </label>
                        <InputText :value="form.judul" placeholder="Judul" class="w-full" readonly />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto"> 
                        <label for="" class="font-bold block"> Deskripsi </label>
                        <InputText v-model="form.deskripsi" placeholder="Deskripsi" class="w-full" readonly />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Lokasi </label>
                        <InputText :value="form.lokasi" placeholder="Lokasi" class="w-full" readonly />
                    </div>
                    <div class="flex-auto"> 
                        <label for="" class="font-bold block"> Petugas </label>
                        <InputText v-model="form.petugas" placeholder="Petugas" class="w-full" readonly />
                    </div>
                </div>
            </div>
            <div class="w-full md:w-2/12">
                <Divider layout="vertical" class="!hidden md:!flex" />
                <Divider layout="horizontal" class="!flex md:!hidden" align="center" />
            </div>
            <div class="w-full md:w-5/12 flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Alur Perawatan </label>
                        <Textarea v-model="form.alur" rows="5" style="resize: none;" class="w-full" readonly />
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Trouble/Permasalahan </label>
                        <Textarea v-model="form.problem" rows="5" style="resize: none;" class="w-full" readonly />
                    </div>
                </div>
                <div class="card flex flex-row gap-4 -mt-20" v-if="src || srcFinish">
                    <div class="basis-1/2 justify-items-center">
                        <label for="icondisplay" class="font-bold block"> Foto Sebelum </label>
                        <img v-if="src" :src="src" alt="Image" class="shadow-md rounded-xl w-full" @click="showImage(src)" />
                    </div>
                    <div class="basis-1/2 justify-items-center">
                        <label for="icondisplay" class="font-bold block"> Foto Sesudah </label>
                        <img v-if="srcFinish" :src="srcFinish" alt="Image" class="shadow-md rounded-xl w-full" @click="showImage(srcFinish)" />
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col md:flex-row mt-20">
            <div class="w-full flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="flex md:w-6/12 sm:w-12/12 btn-block" icon="pi pi-times" raised @click="detailDialog = false" />
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>