<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import id from 'moment/dist/locale/id';
import { FilterMatchMode } from '@primevue/core/api';
import { Form } from '@primevue/forms';
import Panel from 'primevue/panel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import FileUpload from 'primevue/fileupload';


const datas = defineProps({
    lists: Object,
    dates: Object,
})

moment.locale('id')
const toast  = useToast()
const loading = ref(true)
const guestList = ref([])
const startDate = ref(Array())
const filters = ref()
const now = new Date()
const dateFilter = ref(null)

const detailDialog = ref(false)
const importDialog = ref(false)
const submitted = ref(false)
const form = useForm({
    uuid: null,
    nama: null,
    instansi: null,
    keperluan: null,
    tanggal: null,
    jam_mulai: null,
    jam_selesai: null,
})
const formUpload = useForm({
    file: null
})

const initData = () => {
    guestList.value = []
    startDate.value = []
    if (datas.lists && datas.lists.length > 0) {
        datas.lists.map((dl) => {
            guestList.value.push(dl)
        })
    }
    if (datas.dates && datas.dates.length > 0) {
        datas.dates.map((dt) => {
            startDate.value.push({
                name: moment(dt.tanggal).format("DD MMM YYYY"),
                value: dt.tanggal
            })
        })
    }

    loading.value = false
}

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        tanggal: { value: null, matchMode: FilterMatchMode.BETWEEN },
        nama: { value: null, matchMode: FilterMatchMode.CONTAINS },
        instansi: { value: null, matchMode: FilterMatchMode.CONTAINS },
        keperluan: { value: null, matchMode: FilterMatchMode.CONTAINS },
    }
}
initFilters()

const clearFilter = () => {
    initFilters()
    dateFilter.value = null
    initData()
}

const filterDate = (event) => {
    const [start, end] = event

    if (!start && !end) {
        guestList.value = datas.lists
    } else if (start && !end) {
        console.log('start', moment(start).format('YYYY-MM-DD'))
        if (datas.lists.length > 0) {
            guestList.value = datas.lists.filter((dl) => {
                const itemStart = moment(start).format('YYYY-MM-DD')
                const itemDate = moment(dl.tanggal).format('YYYY-MM-DD')
                return itemStart === itemDate
            })
        } else {
            guestList.value = []
        }
    } else if (start && end) {
        if (datas.lists.length > 0) {
            guestList.value = datas.lists.filter((dl) => {
                const itemStart = moment(start).format('YYYY-MM-DD')
                const itemEnd = moment(end).format('YYYY-MM-DD')
                const itemDate = moment(dl.tanggal).format('YYYY-MM-DD')
                
                return itemDate >= itemStart && itemDate <= itemEnd
            })
        } else {
            guestList.value = []
        }
    }
}

const resetDate = () => {
    dateFilter.value = null
}

const src = ref(null)
const clearing = ref(false)
const fileName = ref(null)
const rules = ref({
    file: false
})

function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}

function onFileSelect(event) {
    const file = event.files[0];
    const reader = new FileReader();
    console.log(file)
    if (file.size <= 5242880 && (file.name.split('.').pop() === 'xlsx')) {
        fileName.value = file.name + ' (' + formatBytes(file.size) + ')'
        reader.onload = async (e) => {
            src.value = e.target.result;
            formUpload.file = e.target.result
            clearing.value = true
        };

        rules.value.file = false
        reader.readAsDataURL(file);
    } else {
        rules.value.file = true
        toast.add({ severity: 'error', summary: 'Error', detail: 'Ukuran file melebihi batas yang ditentukan', life: 3000 });
    }
}
function onClearFile() {
    src.value = null
    formUpload.file = null
    clearing.value = false
}

const isFileExist = () => {
    formUpload.file ? rules.value.file = false : rules.value.file = true
}

const importData = () => {
    formUpload.reset()
    src.value = null
    importDialog.value = true
}

const onFormSubmit = () => {
    isFileExist()
    if (formUpload.file) {
        console.log(formUpload)
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Mohon untuk memilih file terlebih dahulu', life: 3000 });
    }
}

const detailData = (data) => {
    if (data) {
        form.uuid = data.uuid
        form.nama = data.nama
        form.instansi  = data.instansi
        form.keperluan = data.keperluan
        form.tanggal   = moment(data.tanggal).format("DD MMMM YYYY")
        form.jam_mulai = data.jam_masuk
        form.jam_selesai = data.jam_keluar

        detailDialog.value = true
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Data tidak ditemukan atau tidak terdaftar', life: 3000 });
    }
}

onMounted(() => {
    initData()
    form.reset()
})

</script>

<template>
    <Toast />
    <ConfirmDialog></ConfirmDialog>
    <Head title="Laporan Buku Tamu" />

    <Card class="w-full">
        <template #title><i class="pi pi-address-book"></i> Laporan Buku Tamu</template>
        <template #content>
            <div class="mt-5">
                <DataTable v-model:filters="filters" :value="guestList" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading" :globalFilterFields="['tanggal', 'nama', 'instansi', 'keperluan']">
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
                                <!-- <Button type="button" icon="pi pi-file-import" label="Import" outlined @click="importData()" /> -->
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

                    <Column field="tanggal" header="Tanggal Kedatangan" style="width: 25%">
                        <template #body="{ data }">
                            <label v-tooltip="data.jam_masuk + ' - ' + data.jam_keluar">
                                {{ moment(data.tanggal).format("DD MMM YYYY") }}
                                <!-- {{ formatDate(data.tanggal) }} -->
                            </label>
                        </template>
                    </Column>
                    <Column field="nama" header="Nama" style="width: 20%">
                        <template #body="{ data }">
                            {{ data.nama }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari Nama" />
                        </template>
                    </Column>
                    <Column field="instansi" header="Instansi" style="width: 20%" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            {{ data.instansi }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari instansi" />
                        </template>
                    </Column>
                    <Column field="keperluan" header="Keperluan" style="width: 20%" filterField="keperluan" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }">
                        <template #body="slotProps">
                            <label v-tooltip.bottom="slotProps.data.keperluan.length > 28 ? slotProps.data.keperluan : ''">
                                {{ slotProps.data.keperluan.length > 28 ? (slotProps.data.keperluan.substring(0, 27) + '...') : slotProps.data.keperluan }}
                            </label>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari keperluan" />
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

    <Dialog v-model:visible="importDialog" modal header="Import Data" :style="{width: '30rem'}">
        <div class="w-full">
            <Form @submit="onFormSubmit" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4 mb-8">
                    <div class="flex-auto justify-items-center text-center">
                        <label for="" class="font-bold block"> File excel (.xlsx, max 5 Mb) </label>
                        <FileUpload mode="basic" @select="onFileSelect" customUpload auto severity="secondary" class="p-button-outlined mb-3" accept=".xlsx" @blur="isFileExist" />
                        <Message v-if="rules.file" severity="error" size="small" variant="simple">File wajib diisi</Message>
                        <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" raised v-if="clearing" @click="onClearFile" :disabled="submitted" />
                        <div v-if="src" class="w-full text-center mt-5">
                            <Panel>
                                <i class="pi pi-file-excel" style="font-size: 2rem"></i>
                                <br>
                                <label class="mb-3">{{ fileName }}</label>
                            </Panel>
                        </div>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 justify-items-center">
                    <div class="flex-auto gap-4 text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="col-3 btn-block mr-5" icon="pi pi-times" raised @click="importDialog = false" :disabled="submitted" />
                        <Button type="submit" :label="submitted ? 'Mengunggah' : 'Konfirmasi'" severity="success" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>

    <Dialog v-model:visible="detailDialog" maximizable modal header="Detail Tamu" :style="{width: '70rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="w-full">
            <div class="card flex flex-wrap gap-4 w-45">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Tanggal Kedatangan </label>
                    <InputText v-model="form.tanggal" placeholder="Tanggal Kedatangan" class="w-full" readonly />
                </div>
                <div class="flex-auto">
                    <label for="jam_mulai" class="font-bold block"> Jam Mulai </label>
                    <InputText v-model="form.jam_mulai" placeholder="Jam Mulai" class="w-full" readonly />
                </div>
                <div class="flex-auto">
                    <label for="jam_selesai" class="font-bold block"> Jam Selesai </label>
                    <InputText v-model="form.jam_selesai" placeholder="Jam Selesai" class="w-full" readonly />
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="nama" class="font-bold block"> Nama Tamu </label>
                    <InputText v-model="form.nama" placeholder="Nama Tamu" class="w-full" readonly />
                </div>
                <div class="flex-auto">
                    <label for="instansi" class="font-bold block"> Instansi </label>
                    <InputText v-model="form.instansi" placeholder="Instansi" class="w-full" readonly />
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Keperluan </label>
                    <Textarea v-model="form.keperluan" rows="5" style="resize: none;" class="w-full" readonly />
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-10">
                <div class="flex-auto">
                    <Button type="button" label="Tutup" severity="danger" class="flex btn-block" icon="pi pi-times" raised @click="detailDialog = false" />
                </div>
            </div>
        </div>
    </Dialog>

</template> 