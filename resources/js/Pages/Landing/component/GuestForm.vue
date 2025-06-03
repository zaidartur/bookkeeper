<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';
import moment from 'moment';

const toast = useToast();
const confirm = useConfirm();

const maxDate = ref(new Date());
const tgl_datang = ref();
const jam_masuk  = ref();
const jam_keluar = ref();
const form = useForm({
    nama: '',
    instansi: '',
    tanggal: null,
    masuk: null,
    keluar: null,
    keperluan: '',
});
const selectedCategory = ref();
const categories = ref([
    { name: 'Lokal Kominfo-Setda', value: 'KS' },
    { name: 'Intra OPD', value: 'OPD' },
    { name: 'Metro Kecamatan', value: 'Metro' },
    { name: 'Internet', value: 'Int' },
    { name: 'Petugas', value: 'Ptg' }
]);

const selectedStatus = ref();
const status = ref([
    {name: 'On Progress', value: 'progress'},
    {name: 'On Monitoring', value: 'monitoring'},
    {name: 'Finish', value: 'finish'}
]);

// init form validation
const initialValues = ref({
    _nama: '',
    _instansi: '',
    _tanggal: null,
    _masuk: null,
    _keluar: null,
    _keperluan: ''
});

// form validation
const resolver = ref(zodResolver(
    z.object({
        _nama       : z.string().min(1, { message: 'Nama Tamu wajib diisi.' }),
        _instansi   : z.string().min(1, { message: 'Nama Instansi wajib diisi.' }),
        _tanggal    : z.preprocess((val) => {
            if (val === '' || val === null) {
                return null
            }
            return new Date(val)
        }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Tanggal wajib diisi'})])),
        // _masuk      : z.string().min(1, { message: 'Jam Masuk wajib diisi.' }),
        _masuk      : z.preprocess((val) => {
            if (val === '' || val === null) {
                return null
            }
            return new Date(val)
        }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Jam Masuk wajib diisi'})])),
        _keluar     : z.preprocess((val) => {
            if (val === '' || val === null) {
                return null
            }
            return new Date(val)
        }, z.union([z.date(), z.null().refine((val) => val !== null, {message: 'Jam Masuk wajib diisi'})])),
        _keperluan  : z.string().min(1, { message: 'Keperluan wajib diisi.' })
    })
));

const requireConfirmation = () => {
    confirm.require({
        group: 'headless',
        header: 'Simpan Sekarang?',
        message: 'Pilih tombol konfirmasi untuk melanjutkan.',
        accept: () => {
            console.log(form)
            toast.add({ severity: 'info', summary: 'Confirmed', detail: 'You have accepted', life: 3000 });
            // formSubmit()
        },
        reject: () => {
            toast.add({ severity: 'error', summary: 'Rejected', detail: 'You have rejected', life: 3000 });
        }
    });
};

const onFormBeforeSubmit = ({ valid }) => {
    if (tgl_datang.value) {
        form.tanggal = moment(tgl_datang.value).format('YYYY-MM-DD')
    } else {
        form.tanggal = null
    }
    jam_masuk.value ? form.masuk = moment(jam_masuk.value).format('HH:mm') : form.masuk = null
    jam_keluar.value ? form.keluar = moment(jam_keluar.value).format('HH:mm') : form.keluar = null

    console.log(form.tanggal, valid)
    if (valid) {
        // toast.add({ severity: 'success', summary: 'Form is submitted.', life: 3000 });
        requireConfirmation()
    }
};

const formSubmit = () => {
    const _url = '/buku-tamu/save-form'
    form.post(_url, {
        resetOnSuccess: true,
        onSuccess: (res) => {
            if (res.status === 'success') {
                toast.add({ severity: 'info', summary: 'Confirmed', detail: 'Data berhasil disimpan', life: 3000 });
                form.reset()
                tgl_datang.value = null
                jam_masuk.value  = null
                jam_keluar.value = null
            } else {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data', life: 3000 });
            }
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Terjadi kegagalan pada sistem', life: 3000 });
        }
    })
}

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

</script>

<template>
    <Toast />
    <div id="features" class="py-6 px-6 lg:px-20 mt-0 mx-0 lg:mx-40">
        <div>
            <h1 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl text-center">Buku Tamu Data Center Diskominfo Karanganyar</h1>
        </div>

        <!-- <div>
            <h2 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-2xl text-center">Form Input Tamu</h2>
        </div> -->

        <Form v-slot="$form" :resolver="resolver" :initialValues="initialValues" @submit="onFormBeforeSubmit" class="flex flex-col gap-4">
            <div class="card flex flex-wrap gap-4">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Nama Lengkap </label>
                    <InputText v-model="form.nama" placeholder="Nama Lengkap" name="_nama" class="w-full" />
                    <Message v-if="$form._nama?.invalid" severity="error" size="small" variant="simple">{{ $form._nama.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Instansi </label>
                    <InputText v-model="form.instansi" placeholder="Nama Instansi" name="_instansi" class="w-full" />
                    <Message v-if="$form._instansi?.invalid" severity="error" size="small" variant="simple">{{ $form._instansi.error?.message }}</Message>
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="tanggal" class="font-bold block"> Tanggal Kedatangan </label>
                    <DatePicker v-model="tgl_datang" showIcon fluid iconDisplay="input" :maxDate="maxDate" dateFormat="dd/mm/yy" inputId="tanggal" name="_tanggal" />
                    <Message v-if="$form._tanggal?.invalid" severity="error" size="small" variant="simple">{{ $form._tanggal.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="masuk" class="font-bold block">Jam Masuk </label>
                    <DatePicker v-model="jam_masuk" showIcon fluid iconDisplay="input" timeOnly inputId="masuk" name="_masuk">
                        <template #inputicon="slotProps">
                            <i class="pi pi-clock" @click="slotProps.clickCallback" />
                        </template>
                    </DatePicker>
                    <Message v-if="$form._masuk?.invalid" severity="error" size="small" variant="simple">{{ $form._masuk.error?.message }}</Message>
                </div>
                <div class="flex-auto">
                    <label for="keluar" class="font-bold block">Jam Keluar </label>
                    <DatePicker v-model="jam_keluar" showIcon fluid iconDisplay="input" timeOnly inputId="keluar" name="_keluar">
                        <template #inputicon="slotProps">
                            <i class="pi pi-clock" @click="slotProps.clickCallback" />
                        </template>
                    </DatePicker>
                    <Message v-if="$form._keluar?.invalid" severity="error" size="small" variant="simple">{{ $form._keluar.error?.message }}</Message>
                </div>
            </div>
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <label for="" class="font-bold block"> Keperluan </label>
                    <Textarea v-model="form.keperluan" name="_keperluan" rows="6" style="resize: none;" class="w-full" />
                    <Message v-if="$form._keperluan?.invalid" severity="error" size="small" variant="simple">{{ $form._keperluan.error?.message }}</Message>
                </div>
            </div>
            
            <div class="card flex flex-wrap gap-4 -mt-20">
                <div class="flex-auto">
                    <Button type="submit" label="SIMPAN" icon="pi pi-save" severity="success" raised />
                </div>
            </div>
        </Form>

        <Divider type="solid" />

        <div class="grid grid-cols-12 gap-8 mt-5">
            <div class="col-span-12 lg:col-span-6 xl:col-span-6" style="cursor: pointer;" @mouseover="mouseOnCard('total')" @mouseout="mouseOutCard('total')" id="total">
                <div class="card mb-0">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-4">Total</span>
                            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                152
                            </div>
                        </div>
                        <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-users text-blue-500 !text-xl"></i>
                        </div>
                    </div>
                    <span class="text-primary font-medium">Total </span>
                    <span class="text-muted-color">dari pengunjung keseluruhan</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-6 xl:col-span-6" style="cursor: pointer" @mouseover="mouseOnCard('daily')" @mouseout="mouseOutCard('daily')" id="daily">
                <div class="card mb-0">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-4">Hari Ini</span>
                            <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                2.100
                            </div>
                        </div>
                        <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                            <i class="pi pi-user-plus text-orange-500 !text-xl"></i>
                        </div>
                    </div>
                    <span class="text-primary font-medium">Total </span>
                    <span class="text-muted-color">dari pengunjung hari ini</span>
                </div>
            </div>
        </div>
    </div>

    <ConfirmDialog group="headless">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-col items-center p-8 bg-surface-0 dark:bg-surface-900 rounded">
                <div class="rounded-full bg-primary text-primary-contrast inline-flex justify-center items-center h-24 w-24 -mt-20">
                    <i class="pi pi-question !text-4xl"></i>
                </div>
                <span class="font-bold text-2xl block mb-2 mt-6">{{ message.header }}</span>
                <p class="mb-0">{{ message.message }}</p>
                <div class="flex items-center gap-2 mt-6">
                    <Button label="Konfirmasi" @click="acceptCallback" class="w-32"></Button>
                    <Button label="Batalkan" outlined @click="rejectCallback" class="w-32"></Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>

</template>

<style lang="scss">
    .is-hover {
        box-shadow: 0 0 10px rgba(180, 180, 180, 0.5);
        // background-color: cadetblue;
    }
</style>