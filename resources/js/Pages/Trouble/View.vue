<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';

const toast = useToast();
const datas = defineProps({
    lists: Object
})

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
const icondisplay = ref();
const templatedisplay = ref();
const form = useForm({
    email: '',
    password: '',
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

const initialValues = ref({
    address: ''
});

const resolver = ref(zodResolver(
    z.object({
        address: z.string().min(1, { message: 'Address is required.' })
    })
));

const showModal = () => {
    form.reset();
    addNew.value = true
}

const onFormSubmit = ({ valid }) => {
    console.log('form', valid)
    if (valid) {
        toast.add({ severity: 'success', summary: 'Form is submitted.', life: 3000 });
    }
};

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
                    <Column field="starttime" header="Waktu Kejadian" style="width: 15%"></Column>
                    <Column field="country" header="Lokasi" style="width: 20%"></Column>
                    <Column field="trouble" header="Trouble" style="width: 25%"></Column>
                    <Column field="category" header="Kategori" style="width: 15%"></Column>
                    <Column header="Status" style="width: 10%">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status.toLowerCase()" :severity="getSeverity(slotProps.data.status)" />
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 15%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <Button type="button" severity="success" icon="pi pi-wrench" variant="outlined" v-tooltip.bottom="'Ubah Status'" rounded raised v-if="slotProps.data.status === 'progress'" /> &nbsp;
                            <Button type="button" severity="info" icon="pi pi-info-circle" variant="outlined" v-tooltip.bottom="'Detail Trouble'" rounded raised /> &nbsp;
                            <Button type="button" severity="warn" icon="pi pi-pencil" v-tooltip.bottom="'Edit Trouble'" rounded raised /> &nbsp;
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


    <div id="features" class="py-6 px-6 lg:px-20 mt-0 mx-0 lg:mx-40">
        <div>
            <h1 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl text-center">Form Maintenance</h1>
        </div>
        
    </div>

    <Dialog v-model:visible="addNew" maximizable modal header="Input Trouble/Masalah Baru" :style="{width: '50rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div>
            <Form v-slot="$form" :resolver="resolver" :initialValues="initialValues" @submit="onFormSubmit" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Tanggal Kejadian </label>
                        <DatePicker v-model="icondisplay" showIcon fluid iconDisplay="input" inputId="icondisplay" />
                    </div>
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block">Jam Kejadian </label>
                        <DatePicker v-model="templatedisplay" showIcon fluid iconDisplay="input" timeOnly inputId="templatedisplay">
                            <template #inputicon="slotProps">
                                <i class="pi pi-clock" @click="slotProps.clickCallback" />
                            </template>
                        </DatePicker>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
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
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Site/Lokasi </label>
                        <Textarea v-model="value" rows="6" style="resize: none;" class="w-full" />
                    </div>
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="value" rows="6" style="resize: none;" class="w-full" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Kategori Permasalahan </label>
                        <Select v-model="selectedCategory" :options="categories" optionLabel="name" placeholder="Pilih Kategori" class="w-full" />
                    </div>
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Petugas </label>
                        <InputText v-model="text1" placeholder="Nama Petugas" class="w-full" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Action/Solusi </label>
                        <Textarea v-model="value" name="address" rows="6" style="resize: none;" class="w-full" />
                        <Message v-if="$form.address?.invalid" severity="error" size="small" variant="simple">{{ $form.address.error?.message }}</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Status </label>
                        <Select v-model="selectedStatus" :options="status" optionLabel="name" placeholder="Pilih Status" class="w-full" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <Button type="submit" label="Success" severity="success" raised />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>
</template>