<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';

const toast = useToast();
const icondisplay = ref();
const templatedisplay = ref();
const form = useForm({
    email: '',
    password: '',
    remember: false,
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

const onFormSubmit = ({ valid }) => {
    console.log('form', valid)
    if (valid) {
        toast.add({ severity: 'success', summary: 'Form is submitted.', life: 3000 });
    }
};

</script>

<template>
    <Toast />
    <div id="features" class="py-6 px-6 lg:px-20 mt-0 mx-0 lg:mx-40">
        <div>
            <h1 class="text-surface-900 dark:text-surface-0 font-normal mb-2 text-4xl text-center">Form Maintenance</h1>
        </div>
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
</template>