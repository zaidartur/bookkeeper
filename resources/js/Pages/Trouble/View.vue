<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';
import { useVuelidate } from '@vuelidate/core';
import { required, minLength } from '@vuelidate/validators'
import moment from 'moment';
import FileUpload from 'primevue/fileupload';

const toast = useToast();
const datas = defineProps({
    lists: Object
})
moment.locale('id')

const dataTrouble = datas.lists
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

const addNew = ref(false)
const confirmation = ref(false)
const headerNew = ref('')
const detailLokasi = ref('');
const detailDeskripsi = ref('');
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

const src = ref(null)
const clearing = ref(false)
function onFileSelect(event) {
    const file = event.files[0];
    const reader = new FileReader();

    reader.onload = async (e) => {
        src.value = e.target.result;
        formAdd.foto = e.target.result
        clearing.value = true
    };

    reader.readAsDataURL(file);
}
function onClearFile() {
    src.value = null
    formAdd.foto = null
    clearing.value = false
}

const srcFinish = ref(null)
const fClearing = ref(false)
function onFileConfirm(event) {
    const file = event.files[0];
    const reader = new FileReader();

    reader.onload = async (e) => {
        srcFinish.value = e.target.result;
        formConfirm.foto = e.target.result
        fClearing.value = true
    };

    reader.readAsDataURL(file);
}
function onClearFileConfirm() {
    srcFinish.value = null
    formConfirm.foto = null
    fClearing.value = false
}

const selectedCategory = ref();
const categories = ref([
    { name: 'Lokal Kominfo-Setda', value: 'lokal' },
    { name: 'Intra OPD', value: 'opd' },
    { name: 'Metro Kecamatan', value: 'metro' },
    { name: 'Internet', value: 'internet' },
    { name: 'Petugas', value: 'petugas' }
]);

const selectedStatus = ref();
const status = ref([
    {name: 'On Progress', value: 'progress'},
    {name: 'On Monitoring', value: 'monitoring'},
    {name: 'Finish', value: 'finish'}
]);

const rules = {
    tglMulai: { required },
    jamMulai: { required },
    _lokasi: { required, minLength: minLength(3) },
    _deskripsi: { required, minLength: minLength(3) },
    _kategori: { required, minLength: minLength(3) },
    _petugas: { required, minLength: minLength(3) },
}

const $v = useVuelidate(rules, { 
            tglMulai: formAdd.mulai,
            jamMulai: formAdd.jam,
            _lokasi: formAdd.lokasi,
            _deskripsi: formAdd.deskripsi,
            _kategori: formAdd.kategori,
            _petugas: formAdd.petugas
        })

const initialValues = ref({
    tglMulai: null,
    jamMulai: null,
    _lokasi: '',
    _deskripsi: '',
    _kategori: null,
    _petugas: '',
});

const resolver = ref(zodResolver(
    z.object({
        tglMulai: z.preprocess((val) => {
                    if (val === '' || val === null) {
                        return null
                    }
                    return new Date(val)
                }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Tanggal Kejadian wajib diisi'})])),
        jamMulai: z.preprocess((val) => {
                    if (val === '' || val === null) {
                        return null
                    }
                    return new Date(val)
                }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Jam Kejadian wajib diisi'})])),
        _lokasi: z.string().min(1, { message: 'Lokasi wajib diisi.' }),
        _deskripsi: z.string().min(1, { message: 'Deskripsi wajib diisi.' }),
        _kategori: z.preprocess((val) => {
                        if (val === '' || val === null) {
                            return null
                        }
                        return val.value
                    }, z.union([z.string(), z.null().refine((val) => val !== null, {message: 'Kategori harus dipilih!'})])),
        _petugas: z.string().min(1, { message: 'Petugas wajib diisi.' }),
    })
));

const initialConfirm = ref({
    tglSelesai: null,
    jamSelesai: null,
    _solusi: '',    
});
const resolverConfirm = ref(zodResolver(
    z.object({
        tglSelesai: z.preprocess((val) => {
                    if (val === '' || val === null) {
                        return null
                    }
                    return new Date(val)
                }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Tanggal Selesai wajib diisi'})])),
        jamSelesai: z.preprocess((val) => {
                    if (val === '' || val === null) {
                        return null
                    }
                    return new Date(val)
                }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Jam Selesai wajib diisi'})])),
        _solusi: z.string().min(1, { message: 'Solusi wajib diisi.' }),
    })
))

const showModal = () => {
    headerNew.value = 'Input Trouble/Masalah Baru'
    formAdd.reset();
    addNew.value = true
}

const onFormSubmit = ({ valid }) => {
    console.log(valid, initialValues)
    if (valid) {
        console.log(initialValues, formAdd)
        toast.add({ severity: 'success', summary: 'Form is submitted.', life: 3000 });
    }
}

const onFormConfirm = ({ valid }) => {
    console.log('form', valid)
    if (valid) {
        console.log('form valid', formConfirm)
        toast.add({ severity: 'success', summary: 'Form is submitted.', life: 3000 });
    }
}

const editForm = (_edit) => {
    formAdd.uuid        = _edit.uuid
    formAdd.mulai       = _edit.tgl_trouble
    formAdd.jam         = _edit.jam_trouble
    formAdd.lokasi      = _edit.lokasi
    formAdd.kategori    = _edit.kategori
    formAdd.petugas     = _edit.petugas
    formAdd.deskripsi   = _edit.problem
    formAdd.foto        = _edit.foto_awal

    headerNew.value = 'Edit Trouble/Masalah'
    addNew.value = true
}

const detailForm = (isDetail) => {
    //
}

const confirmDialog = (isDetail) => {
    if (isDetail) {
        detailLokasi.value = isDetail.lokasi
        detailDeskripsi.value = isDetail.problem

        formConfirm.reset()

        confirmation.value = true
    }
}

</script>

<template>
    <Toast />
    <Head title="Network Trouble" />

    <Card class="w-full">
        <template #title><i class="pi pi-sitemap"></i> Data Network Trouble</template>
        <template #content>
            <div class="mt-5 mb-5">
                <Button type="button" label="Buat Data Trouble" severity="info" icon="pi pi-plus-circle" raised @click="showModal" />
            </div>
            <div>
                <DataTable :value="dataTrouble" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                    <Column field="tgl_trouble" header="Waktu Kejadian" style="width: 15%">
                        <template #body="slotProps">
                            <label>{{ moment(slotProps.data.tgl_trouble).format("DD MMM YYYY") +' '+ slotProps.data.jam_trouble }}</label>
                        </template>
                    </Column>
                    <Column field="lokasi" header="Lokasi" style="width: 20%"></Column>
                    <Column field="problem" header="Trouble" style="width: 20%"></Column>
                    <Column field="kategori" header="Kategori" style="width: 15%"></Column>
                    <Column header="status" style="width: 10%">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status.toLowerCase()" :severity="getSeverity(slotProps.data.status)" />
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 20%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <Button type="button" severity="success" icon="pi pi-wrench" variant="outlined" v-tooltip.bottom="'Ubah Status'" rounded raised v-if="slotProps.data.status === 'progress'" @click="confirmDialog(slotProps.data)" /> &nbsp;
                            <Button type="button" severity="info" icon="pi pi-info-circle" variant="outlined" v-tooltip.bottom="'Detail Trouble'" rounded raised /> &nbsp;
                            <Button type="button" severity="warn" icon="pi pi-pencil" v-tooltip.bottom="'Edit Trouble'" rounded raised @click="editForm(slotProps.data)" /> &nbsp;
                            <Button type="button" severity="danger" icon="pi pi-trash" variant="outlined" v-tooltip.bottom="'Hapus Data'" rounded raised />
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


    <!-- <div id="features" class="py-6 px-6 lg:px-20 mt-0 mx-0 lg:mx-40">
        <div>
            <h1 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl text-center">Form Maintenance</h1>
        </div>
    </div> -->

    <Dialog v-model:visible="addNew" maximizable modal :header="headerNew" :style="{width: '50rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div>
            <Form v-slot="$form" :resolver="resolver" :initialValues="initialValues" @submit="onFormSubmit" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="tglMulai" class="font-bold block"> Tanggal Kejadian </label>
                        <DatePicker v-model="formAdd.mulai" showIcon fluid iconDisplay="input" name="tglMulai" inputId="tglMulai" @change="$form.onChange" />
                        <Message v-if="$form.tglMulai?.invalid" severity="error" size="small" variant="simple">{{ $form.tglMulai.error?.message }}</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="jamMulai" class="font-bold block">Jam Kejadian </label>
                        <DatePicker v-model="formAdd.jam" showIcon fluid iconDisplay="input" timeOnly inputId="jamMulai" name="jamMulai">
                            <template #inputicon="slotProps">
                                <i class="pi pi-clock" @click="slotProps.clickCallback" />
                            </template>
                        </DatePicker>
                        <Message v-if="$form.jamMulai?.invalid" severity="error" size="small" variant="simple">{{ $form.jamMulai.error?.message }}</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Site/Lokasi </label>
                        <Textarea v-model="formAdd.lokasi" rows="6" style="resize: none;" class="w-full" name="_lokasi" value="Apa" />
                        <Message v-if="$form._lokasi?.invalid" severity="error" size="small" variant="simple">{{ $form._lokasi.error?.message }}</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="formAdd.deskripsi" rows="6" style="resize: none;" class="w-full" name="_deskripsi" />
                        <Message v-if="$form._deskripsi?.invalid" severity="error" size="small" variant="simple">{{ $form._deskripsi.error?.message }}</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Kategori Permasalahan </label>
                        <Select v-model="formAdd.kategori" :options="categories" optionLabel="name" name="_kategori" placeholder="Pilih Kategori" class="w-full" />
                        <Message v-if="$form._kategori?.invalid" severity="error" size="small" variant="simple">{{ $form._kategori.error?.message }}</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="petugas" class="font-bold block"> Petugas </label>
                        <InputText v-model="formAdd.petugas" placeholder="Nama Petugas" class="w-full" name="_petugas" />
                        <Message v-if="$form._petugas?.invalid" severity="error" size="small" variant="simple">{{ $form._petugas.error?.message }}</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 mb-8">
                    <div class="flex-auto justify-items-center">
                        <label for="" class="font-bold block"> Foto Permasalahan (opsional) </label>
                        <FileUpload mode="basic" @select="onFileSelect" customUpload auto severity="secondary" class="p-button-outlined mb-3" />
                        <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" raised v-if="clearing" @click="onClearFile" />
                        <img v-if="src" :src="src" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" style="filter: grayscale(100%)" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto gap-4 text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="col-3 btn-block mr-5" icon="pi pi-times" raised @click="addNew = false" />
                        <Button type="submit" label="Simpan" severity="success" icon="pi pi-save" raised />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>

    <Dialog v-model:visible="confirmation" maximizable modal header="Konfirmasi Perbaikan Masalah/Trouble" :style="{width: '50rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div>
            <Form v-slot="$forms" :resolver="resolverConfirm" :initialValues="initialConfirm" @submit="onFormConfirm" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Site/Lokasi </label>
                        <Textarea v-model="detailLokasi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="detailDeskripsi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="tglSelesai" class="font-bold block"> Tanggal Selesai </label>
                        <DatePicker v-model="formConfirm.selesai" showIcon fluid iconDisplay="input" inputId="tglSelesai" name="tglSelesai" />
                        <Message v-if="$forms.tglSelesai?.invalid" severity="error" size="small" variant="simple">{{ $forms.tglSelesai.error?.message }}</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="jamSelesai" class="font-bold block">Jam Selesai </label>
                        <DatePicker v-model="formConfirm.jam" showIcon fluid iconDisplay="input" timeOnly inputId="jamSelesai" name="jamSelesai">
                            <template #inputicon="slotProps">
                                <i class="pi pi-clock" @click="slotProps.clickCallback" />
                            </template>
                        </DatePicker>
                        <Message v-if="$forms.jamSelesai?.invalid" severity="error" size="small" variant="simple">{{ $forms.jamSelesai.error?.message }}</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Action/Solusi </label>
                        <Textarea v-model="formConfirm.solusi" name="_solusi" rows="5" style="resize: none;" class="w-full" />
                        <Message v-if="$forms._solusi?.invalid" severity="error" size="small" variant="simple">{{ $forms._solusi.error?.message }}</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 mb-8">
                    <div class="flex-auto justify-items-center">
                        <label for="icondisplay" class="font-bold block"> Foto Selesai (opsional) </label>
                        <FileUpload mode="basic" @select="onFileConfirm" customUpload auto severity="secondary" class="p-button-outlined mb-3" />
                        <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" raised v-if="fClearing" @click="onClearFileConfirm" />
                        <img v-if="srcFinish" :src="srcFinish" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-28 justify-items-center">
                    <Divider />
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto gap-4 text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="col-3 btn-block mr-5" icon="pi pi-times" raised @click="confirmation = false" />
                        <Button type="submit" label="Simpan" severity="success" icon="pi pi-save" raised />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>
</template>