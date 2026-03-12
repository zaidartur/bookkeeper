<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';
import moment from 'moment';
import { FilterMatchMode } from '@primevue/core/api';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Fieldset from 'primevue/fieldset';

moment.locale('id')
const toast = useToast();
const datas = defineProps({
    lists: Object,
    cat: Object,
    brand: Object,
    loc: Object,
})

const addDialog = ref(false)
const detailDialog = ref(false)
const mode = ref('new')
const dataCategory = ref()
const dataBrand = ref()
const dataLocation = ref()
const maxDate = ref(new Date())
const condition = ref()

const loading = ref(true)
const dataInvent = ref(Array())
const isFocus = ref(false)
const submitted = ref(false)
const filters = ref()
const form = useForm({
    uid: '',
    cat: '',
    brand: '',
    type: '',
    serial: '',
    date_in: null,
    method: '',
    status: '',
    loc: '',
    condition: '',
    notes: '',
    user: '',
    mode: '',
});

const rules = ref({
    cat: false,
    brand: false,
    type: false,
    date_in: false,
    method: false,
    status: false,
    loc: false,
    condition: false,
});

const resetValidation = () => {
    rules.value.cat         = false
    rules.value.brand       = false
    rules.value.type        = false
    rules.value.date_in     = false
    rules.value.method      = false
    rules.value.status      = false
    rules.value.loc         = false
    rules.value.condition   = false
}

const isCat = () => {
    form.cat ? rules.value.cat = false : rules.value.cat = true
}
const isBrand = () => {
    form.brand ? rules.value.brand = false : rules.value.brand = true
}
const isType = () => {
    form.type ? rules.value.type = false : rules.value.type = true
}
const isDate = () => {
    form.date_in ? rules.value.date_in = false : rules.value.date_in = true
    if (form.date_in) {
        calculateCondition(form.date_in)
    } else {
        condition.value = ''
    }
}
const isMethod = () => {
    form.method ? rules.value.method = false : rules.value.method = true
}
const isStatus = () => {
    form.status ? rules.value.status = false : rules.value.status = true
}
const isLoc = () => {
    form.loc ? rules.value.loc = false : rules.value.loc = true
}
const isCondition = () => {
    form.condition ? rules.value.condition = false : rules.value.condition = true
}

const calculateCondition = (dt) => {
    const _def  = 5
    const _now  = moment().format('YYYY')
    const _date = moment(dt).format('YYYY')
    const ages  = parseInt(_now) - parseInt(_date)
    const _diff = _def - ages

    let text = `<h2>Usia Barang <b>\u00B1 ${ages} tahun</b></h2>`
    text += `<h2>Sisa Usia Barang <b>${_diff} tahun</b></h2>`
    text += `<h2>Kesimpulan: `
    if (ages < 5) {
        text += `<span style="color: #4ade80"><u>Masih Baik</u></span>`
    } else if (ages === 5) {
        text += `<span style="color: #fb923c"><u>Siap Rencana Pengadaan</u></span>`
    } else if (ages > 5) {
        text += '<span style="color: #f87171"><u>Disarankan Ganti</u></span>'
    }
    text += '</h2>'
    condition.value = text
}

const tableAge = (dt) => {
    const _def  = 5
    const _now  = moment().format('YYYY')
    const _date = moment(dt).format('YYYY')
    const ages  = parseInt(_now) - parseInt(_date)
    const _diff = _def - ages

    if (ages < 5) {
    return 'success'
    } else if (ages === 5) {
    return 'warn'
    } else if (ages > 5) {
    return 'danger'
    }
}

const labelAge = (dt) => {
    const _def  = 5
    const _now  = moment().format('YYYY')
    const _date = moment(dt).format('YYYY')
    const ages  = parseInt(_now) - parseInt(_date)
    const _diff = _def - ages

    if (ages < 5) {
        return 'Masih Baik'
    } else if (ages === 5) {
        return 'Siap Rencana Pengadaan'
    } else if (ages > 5) {
        return 'Disarankan Ganti'
    }
    text += '</h2>'
}

const initData = () => {
    dataInvent.value = []
    dataBrand.value = []
    dataCategory.value = []
    dataLocation.value = []

    if (Array.isArray(datas.lists) && datas.lists.length > 0) {
        datas.lists.map((dt) => {
            dataInvent.value.push(dt)
        })
    }
    if (Array.isArray(datas.cat) && datas.cat.length > 0) {
        datas.cat.map((dt) => {
            dataCategory.value.push({name: dt.name, value: dt.uid})
        })
    }
    if (Array.isArray(datas.brand) && datas.brand.length > 0) {
        datas.brand.map((dt) => {
            dataBrand.value.push({name: dt.brand, value: dt.uid})
        })
    }
    if (Array.isArray(datas.loc) && datas.loc.length > 0) {
        datas.loc.map((dt) => {
            dataLocation.value.push({name: dt.location, value: dt.uid})
        })
    }

    loading.value = false;
}

const method = ref([
    {name: 'Pengadaan', value: 'pengadaan'},
    {name: 'Pemeliharaan', value: 'pemeliharaan'},
])

const status = ref([
    {name: 'Idle', value: 'idle'},
    {name: 'Terpasang', value: 'terpasang'},
    {name: 'Backup', value: 'backup'}
]);

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        lokasi: { value: null, matchMode: FilterMatchMode.CONTAINS },
        status: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
        kategori: { value: null, matchMode: FilterMatchMode.IN },
        // kategori: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
};
initFilters()

const checkValidationNew = () => {
    isCat()
    isBrand()
    isType()
    isDate()
    isMethod()
    isStatus()
    isLoc()
    isCondition()
}

// const checkValidationConfirm = () => {
//     isTglSelesai()
//     isJamSelesai()
//     isSolusi()
// }

const clearFilter = () => {
    initFilters();
};

const newData = () => {
    form.reset()
    mode.value = 'new'
    form.mode  = 'new'
    addDialog.value = true
}

const editData = (res) => {
    form.reset()
    mode.value  = 'edit'
    form.mode   = 'edit'

    form.uid    = res.uid
    dataCategory.value.some((dc) => {
        if (dc.value === res.uid_category) {
            form.cat = dc
            return true
        }
    })
    dataBrand.value.some((db) => {
        if (db.value === res.uid_brand) {
            form.brand = db
            return true
        }
    })
    form.type   = res.type
    form.serial = res.serial ?? ''
    form.date_in = res.date_in
    method.value.some((mt) => {
        if (mt.value === res.method) {
            form.method = mt
            return true
        }
    })
    status.value.some((stat) => {
        if (stat.value === res.status) {
            form.status = stat
            return true
        }
    })
    dataLocation.value.some((dl) => {
        if (dl.value === res.uid_location) {
            form.loc = dl
            return true
        }
    })
        
    form.condition = res.condition
    form.notes  = res.notes ?? ''

    addDialog.value = true
}

const onFormSubmit = () => {
    checkValidationNew()
    if (form.cat && form.brand && form.type && form.date_in && form.method && form.status && form.loc) {
        const _date = moment(form.date_in).format('YYYY-MM-DD')
        form.date_in = _date
        form.brand = form.brand.value
        form.cat = form.cat.value
        form.loc = form.loc.value
        form.method = form.method.value
        form.status = form.status.value

        submitted.value = true
        form.post('/inventory/save-data', {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)

                initData()
                submitted.value = false
                addDialog.value = false
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
                submitted.value = false
            }
        })
    } else {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Mohon isi semua data yang diwajibkan!', life: 3000 });
        submitted.value = false
    }
}

const detailData = (datas) => {
    form.reset()

    form.cat        = datas.category.name
    form.brand      = datas.brand.brand
    form.type       = datas.type
    form.serial     = datas.serial
    form.date_in    = datas.date_in
    method.value.some((mt) => {
        if (mt.value === datas.method) {
            form.method = mt.name
            return true
        }
    })
    status.value.some((stat) => {
        if (stat.value === datas.status) {
            form.status = stat.name
            return true
        }
    })
    form.loc        = datas.location.location
    form.condition  = datas.condition
    form.notes      = datas.notes

    detailDialog.value = true
}

const alert_response = (rsp) => {
    if (rsp.status === 'failed') {
        toast.add({ severity: 'error', summary: 'Error', detail: rsp.msg, life: 3000 });
    } else if (rsp.status === 'success') {
        toast.add({ severity: 'success', summary: 'Berhasil', detail: rsp.msg, life: 3000 });
    }
}

onMounted(() => {
    initData()
})
</script>

<template>
    <Toast />
    <Head title="Data Inventaris" />

    <Card class="w-full">
        <template #title><i class="pi pi-sitemap"></i> Data Inventaris</template>
        <template #content>
            <div class="mt-5">
                <DataTable v-model:filters="filters" :value="dataInvent" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading" :globalFilterFields="['kategori', 'lokasi', 'status']">
                    <template #header>
                        <div class="flex justify-between">
                            <div class="flex flex-wrap gap-4">
                                <Button type="button" icon="pi pi-filter-slash" label="Bersihkan Filter" outlined @click="clearFilter()" />
                                <Button type="button" severity="info" icon="pi pi-plus" label="Tambah Data" @click="newData()" />
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

                    <Column field="" header="#" style="width: 3%">
                        <template #body="{ slotProps, index }">
                            <label>{{ index + 1 }}</label>
                        </template>
                    </Column>
                    <Column field="kategori" header="Kategori Barang" filterField="kategori" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="width: 20%">
                        <template #body="{ data }">
                            {{ data.category.name }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari Kategori" />
                        </template>
                    </Column>
                    <Column field="" header="Merek" style="width: 20%">
                        <template #body="{ data }">
                            {{ data.brand.brand }}
                        </template>
                    </Column>
                    <Column field="" header="Tipe" style="width: 10%">
                        <template #body="slotProps">
                            {{ slotProps.data.type }}
                        </template>
                    </Column>
                    <Column header="Tanggal Masuk" style="width: 10%">
                        <template #body="slotProps">
                            <label>{{ moment(slotProps.data.date_in).format("DD MMM YYYY") }}</label>
                        </template>
                    </Column>
                    <Column header="Status" filterField="status" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="width: 10%">
                        <template #body="slotProps">
                            <label>{{ slotProps.data.status }}</label>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari Status" />
                        </template>
                    </Column>
                    <Column header="Lokasi" filterField="lokasi" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="width: 10%">
                        <template #body="slotProps">
                            <label>{{ slotProps.data.location.location }}</label>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" placeholder="Cari Lokasi" />
                        </template>
                    </Column>
                    <Column header="Usia" style="width: 10%">
                        <template #body="slotProps">
                            <Tag v-tooltip="labelAge(slotProps.data.date_in)" :severity="tableAge(slotProps.data.date_in)">
                                {{ parseInt(moment().format('YYYY')) - parseInt(moment(slotProps.data.date_in).format('YYYY')) }} tahun
                            </Tag>
                        </template>
                    </Column>

                    <Column header="Opsi" style="width: 15%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-search" @click="detailData(slotProps.data)" v-tooltip.bottom="'Lihat Detail'" severity="secondary" rounded />
                                <Button icon="pi pi-pencil" @click="editData(slotProps.data)" v-tooltip.bottom="'Edit Data'" severity="warn" rounded />
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

    <Dialog v-model:visible="addDialog" maximizable modal :header="type === 'new' ? 'Buat Data Inventory' : 'Edit Data Inventory'" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div>
            <Form @submit="onFormSubmit" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Kategori </label>
                        <Select v-model="form.cat" :options="dataCategory" optionLabel="name" name="cat" placeholder="Pilih Kategori" class="w-full" :invalid="rules.cat" @blur="isCat" @change="isCat" />
                        <Message v-if="rules.cat" severity="error" size="small" variant="simple">Kategori wajib dipilih</Message>
                    </div>
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Merek Barang </label>
                        <Select v-model="form.brand" :options="dataBrand" optionLabel="name" name="brand" placeholder="Pilih Merek" class="w-full" :invalid="rules.brand" @blur="isBrand" @change="isBrand" />
                        <Message v-if="rules.brand" severity="error" size="small" variant="simple">Merek wajib dipilih</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Tipe (Seri) Barang </label>
                        <InputText v-model="form.type" placeholder="Tulis disini..." class="w-full" name="type" :invalid="rules.type" @blur="isType" @change="isType" />
                        <Message v-if="rules.type" severity="error" size="small" variant="simple">Tipe/Seri wajib diisi</Message>
                    </div>
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Lokasi Barang </label>
                        <Select v-model="form.loc" :options="dataLocation" optionLabel="name" name="loc" placeholder="Pilih Lokasi" class="w-full" :invalid="rules.loc" @blur="isLoc" @change="isLoc" />
                        <Message v-if="rules.loc" severity="error" size="small" variant="simple">Lokasi wajib dipilih</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Serial Number Device</label>
                        <InputText v-model="form.serial" placeholder="opsional..." class="w-full" />
                    </div>
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Tanggal Masuk </label>
                        <DatePicker v-model="form.date_in" showIcon fluid iconDisplay="input" name="date_in" :maxDate="maxDate" inputId="date_in" :invalid="rules.date_in" @blur="isDate" @change="isDate" />
                        <Message v-if="rules.date_in" severity="error" size="small" variant="simple">Tanggal masuk wajib diisi</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Metode Pengadaan</label>
                        <Select v-model="form.method" :options="method" optionLabel="name" name="method" placeholder="Pilih Metode" class="w-full" :invalid="rules.method" @blur="isMethod" @change="isMethod" />
                        <Message v-if="rules.method" severity="error" size="small" variant="simple">Metode wajib dipilih</Message>
                    </div>
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Status Barang </label>
                        <Select v-model="form.status" :options="status" optionLabel="name" name="status" placeholder="Pilih Status" class="w-full" :invalid="rules.status" @blur="isStatus" @change="isStatus" />
                        <Message v-if="rules.status" severity="error" size="small" variant="simple">Status wajib dipilih</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <!-- <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Kondisi Barang </label>
                        <Textarea v-model="form.condition" rows="6" style="resize: none;" class="w-full" :invalid="rules.condition" @blur="isCondition" @change="isCondition" />
                        <Message v-if="rules.condition" severity="error" size="small" variant="simple">Kondisi wajib diisi</Message>
                    </div> -->
                    <div class="w-[calc(50%-0.5rem)]">
                        <label for="" class="font-bold block"> Keterangan (opsional) </label>
                        <Textarea v-model="form.notes" rows="6" style="resize: none;" class="w-full"  />
                    </div>
                    <div class="w-[calc(50%-0.5rem)]">
                        Kondisi Barang
                        <!-- <Textarea v-model="form.condition" rows="6" style="resize: none;" class="w-full" readonly="" /> -->
                         <div class="pre-formatted text-2xl" v-html="condition"></div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row mt-10">
                    <div class="w-full flex flex-col gap-4">
                        <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                            <div class="flex-auto text-right">
                                <Button type="button" label="Tutup" severity="secondary" class="flex md:w-6/12 sm:w-12/12 btn-block" icon="pi pi-times" raised @click="addDialog = false" :disabled="submitted" />
                            </div>
                            <div class="flex-auto text-left">
                                <Button type="submit" :label="submitted ? 'Menyimpan' : 'Simpan'" severity="success" class="flex md:w-6/12 sm:w-12/12 btn-block" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                            </div>
                        </div>
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>

    <Dialog v-model:visible="detailDialog" maximizable modal header="Detail Data" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="card flex flex-wrap gap-4">
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Kategori </label>
                <InputText v-model="form.cat" class="w-full" />
            </div>
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Merek Barang </label>
                <InputText v-model="form.brand" class="w-full" />
            </div>
        </div>
        <div class="card flex flex-wrap gap-4 -mt-20">
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Tipe (Seri) Barang </label>
                <InputText v-model="form.type" class="w-full" />
            </div>
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Lokasi Barang </label>
                <InputText v-model="form.loc" class="w-full" />
            </div>
        </div>
        <div class="card flex flex-wrap gap-4 -mt-20">
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Serial Number Device</label>
                <InputText v-model="form.serial" class="w-full" />
            </div>
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Tanggal Masuk </label>
                <InputText v-model="form.date_in" class="w-full" />
            </div>
        </div>
        <div class="card flex flex-wrap gap-4 -mt-20">
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Metode Pengadaan</label>
                <InputText v-model="form.method" class="w-full" />
            </div>
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Status Barang </label>
                <InputText v-model="form.status" class="w-full" />
            </div>
        </div>
        <div class="card flex flex-wrap gap-4 -mt-20">
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Kondisi Barang </label>
                <Textarea v-model="form.condition" rows="6" style="resize: none;" class="w-full" />
            </div>
            <div class="w-[calc(50%-0.5rem)]">
                <label for="" class="font-bold block"> Keterangan (opsional) </label>
                <Textarea v-model="form.notes" rows="6" style="resize: none;" class="w-full"  />
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


<style scoped>
.pre-formatted {
    white-space: pre-wrap; /* This preserves newlines and wraps long lines */
}
</style>