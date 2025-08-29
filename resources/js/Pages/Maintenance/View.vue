<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';
import moment from 'moment';
import FileUpload from 'primevue/fileupload';

moment.locale('id')
const toast = useToast();
const isFocus = ref(false)
const submitted = ref(false)
const form = useForm({
    uuid: '',
    judul: '',
    deskripsi: '',
    tanggal: null,
    jam: null,
    lokasi: '',
    alur: '',
    problem: '',
    petugas: '',
    foto_awal: null,
    foto_akhir: null,
});

onMounted(() => {
    initData()
})


const initialValues = ref({
    judul: '',
    tanggal: null,
    jam: null,
    lokasi: '',
    alur: '',
    petugas: '',
});

const resolver = ref(zodResolver(
    z.object({
        judul: z.string().min(1, { message: 'Judul wajib diisi.' }),
        tanggal: z.preprocess((val) => {
                    if (val === '' || val === null) {
                        return null
                    }
                    return new Date(val)
                }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Tanggal Mulai wajib diisi'})])),
        jam: z.preprocess((val) => {
                    if (val === '' || val === null) {
                        return null
                    }
                    return new Date(val)
                }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Jam Mulai wajib diisi'})])),
        lokasi: z.string().min(1, { message: 'Lokasi wajib diisi.' }),
        alur: z.string().min(1, { message: 'Alur wajib diisi.' }),
        petugas: z.string().min(1, { message: 'Petugas wajib diisi.' }),
    })
));

// selecting image on before
const src = ref(null)
const clearing = ref(false)
function onFileSelect(event) {
    const file = event.files[0];
    const reader = new FileReader();

    reader.onload = async (e) => {
        src.value = e.target.result;
        form.foto_awal = e.target.result
        clearing.value = true
    };

    reader.readAsDataURL(file);
}
function onClearFile() {
    src.value = null
    form.foto_awal = null
    clearing.value = false
}

// selecting image on after
const srcFinish = ref(null)
const fClearing = ref(false)
function onFileConfirm(event) {
    const file = event.files[0];
    const reader = new FileReader();

    reader.onload = async (e) => {
        srcFinish.value = e.target.result;
        form.foto_akhir = e.target.result
        fClearing.value = true
    };

    reader.readAsDataURL(file);
}
function onClearFileConfirm() {
    srcFinish.value = null
    form.foto_akhir = null
    fClearing.value = false
}

const initData = () => {
    form.reset()
    isFocus.value = true
    src.value = null
    clearing.value = false
    srcFinish.value = null
    fClearing.value = false
}

const onFormSubmit = ({ valid }) => {
    if (valid) {
        const _date = moment(form.tanggal).format('YYYY-MM-DD')
        const _time = moment(form.jam).format('HH:mm')
        form.tanggal = _date
        form.jam = _time

        submitted.value = true
        form.post('/maintenance/save', {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)

                initData()
                submitted.value = false
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
            }
        })
    } else {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Mohon isi semua data yang diwajibkan!', life: 3000 });
    }
};

const alert_response = (rsp) => {
    if (rsp.status === 'failed') {
        toast.add({ severity: 'error', summary: 'Error', detail: rsp.msg, life: 3000 });
    } else if (rsp.status === 'success') {
        toast.add({ severity: 'success', summary: 'Berhasil', detail: rsp.msg, life: 3000 });
    }
}
</script>

<template>
    <Toast />
    <Head title="Maintenance" />

    <div id="features" class="py-6 px-6 lg:px-20 mt-0 mx-0 lg:mx-40">
        <div>
            <h1 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl text-center">Form Maintenance</h1>
        </div>
        <Form v-slot="$form" :resolver="resolver" :initialValues="initialValues" @submit="onFormSubmit" class="flex flex-col gap-4">
            <div class="card flex flex-wrap gap-4">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Judul </label>
                    <InputText v-model="form.judul" maxlength="100" placeholder="Judul Kegiatan" class="w-full" name="judul" :autofocus="isFocus" />
                    <Message v-if="$form.judul?.invalid" severity="error" size="small" variant="simple">{{ $form.judul.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Deskripsi (opsional) </label>
                    <InputText v-model="form.deskripsi" maxlength="100" placeholder="Deskripsi" class="w-full" name="deskripsi" />
                    <!-- <Message v-if="$form.deskripsi?.invalid" severity="error" size="small" variant="simple">{{ $form.deskripsi.error?.message }}</Message> -->
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="mulai" class="font-bold block"> Tanggal Mulai </label>
                    <DatePicker v-model="form.tanggal" showIcon fluid iconDisplay="input" inputId="mulai" name="tanggal" />
                    <Message v-if="$form.tanggal?.invalid" severity="error" size="small" variant="simple">{{ $form.tanggal.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="jam" class="font-bold block">Jam Mulai </label>
                    <DatePicker v-model="form.jam" showIcon fluid iconDisplay="input" timeOnly inputId="jam" name="jam">
                        <template #inputicon="slotProps">
                            <i class="pi pi-clock" @click="slotProps.clickCallback" />
                        </template>
                    </DatePicker>
                    <Message v-if="$form.jam?.invalid" severity="error" size="small" variant="simple">{{ $form.jam.error?.message }}</Message>
                </div>
            </div>
            <!-- <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="icondisplay" class="font-bold block"> Tanggal Selesai </label>
                    <DatePicker v-model="icondisplay" showIcon fluid iconDisplay="input" inputId="icondisplay" />
                </div>
                <div class="flex-auto">
                    <label for="icondisplay" class="font-bold block">Jam Selesai </label>
                    <DatePicker v-model="templatedisplay" showIcon fluid iconDisplay="input" timeOnly inputId="templatedisplay">
                        <template #inputicon="slotProps">
                            <i class="pi pi-clock" @click="slotProps.clickCallback" />
                        </template>
                    </DatePicker>
                </div>
            </div> -->
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Lokasi </label>
                    <InputText v-model="form.lokasi" placeholder="Lokasi" maxlength="100" class="w-full" name="lokasi" />
                    <Message v-if="$form.lokasi?.invalid" severity="error" size="small" variant="simple">{{ $form.lokasi.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Petugas </label>
                    <InputText v-model="form.petugas" placeholder="Nama Petugas" maxlength="50" class="w-full" name="petugas" />
                    <Message v-if="$form.petugas?.invalid" severity="error" size="small" variant="simple">{{ $form.petugas.error?.message }}</Message>
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Alur Perawatan </label>
                    <Textarea v-model="form.alur" rows="6" style="resize: none;" class="w-full" name="alur" />
                    <Message v-if="$form.alur?.invalid" severity="error" size="small" variant="simple">{{ $form.alur.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Trouble/Masalah (opsional) </label>
                    <Textarea v-model="form.problem" rows="6" style="resize: none;" class="w-full" name="problem" />
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto justify-items-center">
                    <label for="" class="font-bold block"> Foto Sebelum (opsional) </label>
                    <FileUpload mode="basic" @select="onFileSelect" customUpload auto severity="secondary" class="p-button-outlined mb-3" accept=".png,.jpg,.jpeg" />
                    <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" class="mb-5" raised v-if="clearing" @click="onClearFile" :disabled="submitted" />
                    <img v-if="src" :src="src" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" />
                </div>
                <div class="flex-auto justify-items-center">
                    <label for="" class="font-bold block"> Foto Sesudah (opsional) </label>
                    <FileUpload mode="basic" @select="onFileConfirm" customUpload auto severity="secondary" class="p-button-outlined mb-3" accept=".png,.jpg,.jpeg" />
                    <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" class="mb-5" raised v-if="fClearing" @click="onClearFileConfirm" />
                    <img v-if="srcFinish" :src="srcFinish" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" />
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-16 justify-items-center">
                <Divider />
            </div>
            <div class="card flex flex-wrap gap-4 -mt-12 justify-items-center">
                <div class="flex-auto gap-4 text-center">
                    <Button type="button" label="Reset" severity="secondary" class="flex w-3/12 btn-block mr-5" icon="pi pi-times" raised @click="initData" :disabled="submitted" />
                    <Button type="submit" :label="submitted ? 'Menyimpan' : 'Simpan'" severity="success" class="flex w-3/12 btn-block" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                </div>
            </div>
        </Form>
    </div>
</template>