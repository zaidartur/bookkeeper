<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import { FilterMatchMode } from '@primevue/core/api';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Fieldset from 'primevue/fieldset';

const toast = useToast();
const datas = defineProps({
    lists: Object,
    dates: Object,
})
moment.locale('id')

const loading = ref(true)
const dataTrouble = ref(Array())
const startDate = ref(Array())
const initData = () => {
    // dataTrouble.value = datas.lists
    dataTrouble.value = []
    startDate.value = []
    if (datas.lists.length > 0) {
        datas.lists.map((_list) => {
            dataTrouble.value.push(_list)
        })
    }
    if (datas.dates.length > 0) {
        datas.dates.map((dt) => {
            // startDate.value.push(dt.tgl_trouble)
            startDate.value.push({
                name: moment(dt.tgl_trouble).format("DD MMM YYYY"),
                value: dt.tgl_trouble
            })
        })
    }
    loading.value = false;
}

onMounted(() => {
    initData()
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

const formAdd = useForm({
    uuid: '',
    mulai: null,
    jam: null,
    lokasi: '',
    kategori: null,
    petugas: '',
    deskripsi: '',
    foto: null,
});
const formConfirm = useForm({
    selesai: '',
    jam: '',
    solusi: '',
    foto: null,
});

const detailDialog = ref(false)
const src = ref(false)
const srcFinish = ref(false)
const filters = ref()
const filterCategories = ref(null)
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
        tgl_trouble: { value: null, matchMode: FilterMatchMode.EQUALS },
        lokasi: { value: null, matchMode: FilterMatchMode.CONTAINS },
        problem: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        kategori: { value: null, matchMode: FilterMatchMode.IN },
        // kategori: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
};

initFilters()

const clearFilter = () => {
    initFilters();
};

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
        formAdd.mulai       = moment(slot.tgl_trouble).format('DD MMMM YYYY')
        formAdd.jam         = slot.jam_trouble
        formAdd.lokasi      = slot.lokasi
        formAdd.kategori    = slot.kategori
        formAdd.petugas     = slot.petugas
        formAdd.deskripsi   = slot.problem
        formAdd.kategori    = setCategories(slot.kategori)
        if (slot.foto_awal) {
            src.value       = slot.foto_awal
        }

        formConfirm.selesai = slot.tgl_selesai
        formConfirm.jam     = slot.jam_selesai
        formConfirm.solusi  = slot.solusi
        if (slot.foto_akhir) {
            srcFinish.value = slot.foto_akhir
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
    <Head title="Report Trouble" />

    <Card class="w-full">
        <template #title><i class="pi pi-sitemap"></i> Report of Network Trouble</template>
        <template #content>
            <div class="mt-5">
                <DataTable v-model:filters="filters" :value="dataTrouble" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading" :globalFilterFields="['kategori', 'lokasi', 'problem']">
                    <template #header>
                        <div class="flex justify-between">
                            <Button type="button" icon="pi pi-filter-slash" label="Bersihkan Filter" outlined @click="clearFilter()" />
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Pencarian Umum" />
                            </IconField>
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column field="tgl_trouble" header="Waktu Kejadian" style="width: 15%">
                        <template #body="slotProps">
                            <label>{{ moment(slotProps.data.tgl_trouble).format("DD MMM YYYY") +' '+ slotProps.data.jam_trouble }}</label>
                             <!-- <span>{{ slotProps.data.tgl_trouble }}</span> -->
                        </template>
                        <template #filter="{ filterModel }">
                            <Select v-model="filterModel.value" :options="startDate" option-value="value" option-label="name" placeholder="Pilih Tanggal" showClear>
                                <template #option="slotProps">
                                    <span>{{ slotProps.option.name }}</span>
                                </template>
                            </Select>
                        </template>
                    </Column>
                    <Column field="lokasi" header="Lokasi" style="width: 20%">
                        <template #body="{ data }">
                            {{ data.lokasi }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari lokasi" />
                        </template>
                    </Column>
                    <Column field="problem" header="Trouble/Masalah" style="width: 25%">
                        <template #body="{ data }">
                            {{ data.problem }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari permasalahan" />
                        </template>
                    </Column>
                    <Column field="kategori" header="Kategori" style="width: 15%" filterField="kategori" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }">
                        <template #body="slotProps">
                            {{ setCategories(slotProps.data.kategori) }}
                              <!-- {{ slotProps.data.kategori }} -->
                        </template>
                        <template #filter="{ filterModel }">
                            <MultiSelect v-model="filterModel.value" :options="categories" optionLabel="name" optionValue="value" placeholder="Semua Kategori">
                                <template #option="slots">
                                    <div class="flex items-center gap-2">
                                        <span>{{ slots.option.name }}</span>
                                    </div>
                                </template>
                            </MultiSelect>
                            <!-- <Select v-model="filterModel.value" :options="categories" option-value="value" option-label="name" placeholder="Select One" showClear>
                                <template #option="slotProps">
                                    <span>{{ slotProps.option.name }}</span>
                                </template>
                            </Select> -->
                        </template>
                    </Column>
                    <Column header="Status" style="width: 10%">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status.toLowerCase()" :severity="getSeverity(slotProps.data.status)" />
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

    <Dialog v-model:visible="detailDialog" maximizable modal header="Detail Masalah/Trouble" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="flex flex-col md:flex-row">
            <div class="w-full md:w-5/12 flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4 w-45">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Tanggal Permasalahan </label>
                        <InputText v-model="formAdd.mulai" placeholder="Tanggal Mulai" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="petugas" class="font-bold block"> Jam Mulai </label>
                        <InputText v-model="formAdd.jam" placeholder="Jam Mulai" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Site/Lokasi </label>
                        <Textarea v-model="formAdd.lokasi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="formAdd.deskripsi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Kategori </label>
                        <InputText :value="setCategories(formAdd.kategori)" placeholder="Kategori" class="w-full" disabled />
                    </div>
                    <div class="flex-auto"> 
                        <label for="" class="font-bold block"> Petugas </label>
                        <InputText v-model="formAdd.petugas" placeholder="Petugas" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20" v-if="src">
                    <div class="flex-auto justify-items-center">
                        <label for="icondisplay" class="font-bold block"> Foto Sebelum </label>
                        <img v-if="src" :src="src" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" @click="showImage(src)" />
                    </div>
                </div>
            </div>
            <div class="w-full md:w-2/12">
                <Divider layout="vertical" class="!hidden md:!flex" />
                <Divider layout="horizontal" class="!flex md:!hidden" align="center" />
            </div>
            <div class="w-full md:w-5/12 flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4   ">
                    <div class="flex-auto">
                        <label for="tglSelesai" class="font-bold block"> Tanggal Selesai </label>
                        <InputText v-model="formConfirm.selesai" placeholder="Tanggal Selesai" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="jamSelesai" class="font-bold block">Jam Selesai </label>
                        <InputText v-model="formConfirm.jam" placeholder="Jam Selesai" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Action/Solusi </label>
                        <Textarea v-model="formConfirm.solusi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 mb-8" v-if="srcFinish">
                    <div class="flex-auto justify-items-center">
                        <label for="icondisplay" class="font-bold block"> Foto Sesudah </label>
                        <img v-if="srcFinish" :src="srcFinish" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" @click="showImage(srcFinish)" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 mb-8">
                    <Fieldset legend="Status Masalah" class="w-full justify-items-center">
                        <p class="m-0 text-4xl w-full justify-between font-bold"><i class="pi pi-verified" style="font-size: 2.0rem; color: green;"></i> S E L E S A I</p>
                    </Fieldset>
                </div>
            </div>
        </div>
        <div class="flex flex-col md:flex-row m-10">
            <div class="w-full flex flex-col justify-items-center text-center">
                <Button type="button" label="Tutup" severity="secondary" class="btn-block" icon="pi pi-times" raised @click="detailDialog = false" />
            </div>
        </div>
    </Dialog>
</template>