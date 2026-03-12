<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import { z } from 'zod';
import moment from 'moment';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Fieldset from 'primevue/fieldset';

moment.locale('id')
const toast = useToast();
const datas = defineProps({
    user: Object,
    cat: Object,
    brand: Object,
    loc: Object,
})

const dataCategory = ref(Array())
const dataBrand = ref(Array())
const dataLocation = ref(Array())

const loading = ref(true)
const formDialog = ref(false)
const formMode = ref(null)
const labelName = ref('Nama')
const showParent = ref(false)
const submitted = ref(false)
const form = useForm({
    uid: '',
    name: '',
    parent: null,
    mode: null,
});

const initialValues = ref({
    name: '',
    parent: null,
    mode: null,
});

const resolver = ref(zodResolver(
    z.object({
        name: z.string().min(3, { message: 'Field wajib diisi.' }).max(100),
    })
));

const initData = () => {
    dataCategory.value = []
    if (Array.isArray(datas.cat) && datas.cat.length > 0) {
        datas.cat.map((ct) => {
            dataCategory.value.push(ct)
        })
    }

    dataBrand.value = []
    if (Array.isArray(datas.brand) && datas.brand.length > 0) {
        datas.brand.map((bd) => {
            dataBrand.value.push(bd)
        })
    }

    dataLocation.value = []
    if (Array.isArray(datas.loc) && datas.loc.length > 0) {
        datas.loc.map((lc) => {
            dataLocation.value.push(lc)
        })
    }

    loading.value = false;
}

onMounted(() => {
    initData()
})

const add = (type) => {
    form.reset()
    formMode.value = 'new'
    showParent.value = false

    if (type === 'cat') {
        labelName.value = 'Nama Kategori *'
        form.mode = 'category'
    } else if (type === 'brand') {
        labelName.value = 'Nama Merek *'
        form.mode = 'brand'
    } else if (type === 'loc') {
        labelName.value = 'Nama Lokasi *'
        form.mode = 'location'
        showParent.value = true
    }

    formDialog.value = true
}

const editData = (type, datas) => {
    form.reset()
    formMode.value = 'edit'
    showParent.value = false

    form.uid = datas.uid

    if (type === 'cat') {
        labelName.value = 'Nama Kategori *'
        form.name = datas.name
        form.mode = 'category'
    } else if (type === 'brand') {
        labelName.value = 'Nama Merek *'
        form.name = datas.brand
        form.mode = 'brand'
    } else if (type === 'loc') {
        labelName.value = 'Nama Lokasi *'
        form.name = datas.location
        form.parent = datas.parent
        form.mode = 'location'
        showParent.value = true
    }

    formDialog.value = true
}

const onFormSubmit = ({ valid }) => {
    if (valid && form.name) {
        submitted.value = true
        form.post('/inventory/save-master', {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)
                form.reset()

                initData()
                submitted.value = false
                formDialog.value = false
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
    <ConfirmDialog></ConfirmDialog>
    <Head title="Master Data Inventory" />

    <Card class="w-full mb-5">
        <template #title><i class="pi pi-shopping-bag"></i> Master Kategori</template>
        <template #content>
            <div class="mt-5">
                <DataTable :value="dataCategory" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading">
                    <template #header>
                        <div class="flex justify-between">
                            <Button type="button" icon="pi pi-plus" label="Tambah Data" outlined @click="add('cat')" />
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column header="#" style="width: 5%">
                        <template #body="{ data, index }">
                            <label>{{ index + 1 }}</label>
                        </template>
                    </Column>
                    <Column field="name" header="Nama Kategori" style="width: 60%" />
                    <Column field="created_at" header="Tanggal Buat" style="width: 20%">
                        <template #body="{ data }">
                            <label>{{ moment(data.created_at).format("DD MMM YYYY") }}</label>
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 15%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-pencil" @click="editData('cat', slotProps.data)" v-tooltip.bottom="'Edit Kategori'" severity="warn" rounded />
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

    <Card class="w-full mb-5">
        <template #title><i class="pi pi-verified"></i> Master Merek Barang</template>
        <template #content>
            <div class="mt-5">
                <DataTable :value="dataBrand" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading">
                    <template #header>
                        <div class="flex justify-between">
                            <Button type="button" icon="pi pi-plus" label="Tambah Data" outlined @click="add('brand')" />
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column header="#" style="width: 5%">
                        <template #body="{ data, index }">
                            <label>{{ index + 1 }}</label>
                        </template>
                    </Column>
                    <Column field="brand" header="Merek Barang" style="width: 60%" />
                    <Column field="created_at" header="Tanggal Buat" style="width: 20%">
                        <template #body="{ data }">
                            <label>{{ moment(data.created_at).format("DD MMM YYYY") }}</label>
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 15%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-pencil" @click="editData('brand', slotProps.data)" v-tooltip.bottom="'Edit Merek'" severity="warn" rounded />
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

    <Card class="w-full">
        <template #title><i class="pi pi-map-marker"></i> Master Lokasi Barang</template>
        <template #content>
            <div class="mt-5">
                <DataTable :value="dataLocation" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading">
                    <template #header>
                        <div class="flex justify-between">
                            <Button type="button" icon="pi pi-plus" label="Tambah Data" outlined @click="add('loc')" />
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column header="#" style="width: 5%">
                        <template #body="{ data, index }">
                            <label>{{ index + 1 }}</label>
                        </template>
                    </Column>
                    <Column field="location" header="Nama Lokasi" style="width: 40%" />
                    <Column field="parent" header="Parent" style="width: 20%" />
                    <Column field="created_at" header="Tanggal Buat" style="width: 20%">
                        <template #body="{ data }">
                            <label>{{ moment(data.created_at).format("DD MMM YYYY") }}</label>
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 15%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-pencil" @click="editData('loc', slotProps.data)" v-tooltip.bottom="'Edit Lokasi'" severity="warn" rounded />
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

    <Dialog v-model:visible="formDialog" modal :header="(formMode === 'new' ? 'Buat Data Baru' : 'Edit Data')" :style="{width: '60rem'}">
        <Form v-slot="$form" :resolver="resolver" :initialValues="initialValues" @submit="onFormSubmit" class="flex flex-col gap-4">
            <div class="flex flex-col md:flex-row">
                <div class="w-full flex flex-col gap-4">
                    <div class="card flex flex-wrap gap-4 w-full">
                        <div class="flex-auto">
                            <label for="" class="font-bold block"> {{ labelName }} </label>
                            <InputText v-model="form.name" :placeholder="labelName" maxlength="100" class="w-full" />
                            <Message v-if="$form.name?.invalid" severity="error" size="small" variant="simple">{{ $form.name.error?.message }}</Message>
                        </div>
                    </div>
                </div>
                <div class="w-full flex flex-col gap-4" v-if="showParent">
                    <div class="card flex flex-wrap gap-4 w-full">
                        <div class="flex-auto">
                            <label for="" class="font-bold block"> Parent </label>
                            <InputText v-model="form.parent" placeholder="Parent" class="w-full" maxlength="100" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col md:flex-row mt-20">
                <div class="w-full flex flex-col">
                    <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                        <div class="flex-auto text-right">
                            <Button type="button" label="Tutup" severity="secondary" class="flex md:w-6/12 sm:w-12/12 btn-block" icon="pi pi-times" raised @click="formDialog = false" :disabled="submitted" />
                        </div>
                        <div class="flex-auto text-left">
                            <Button type="submit" :label="submitted ? 'Menyimpan' : 'Simpan'" severity="success" class="flex md:w-6/12 sm:w-12/12 btn-block" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                        </div>
                    </div>
                </div>
            </div>
        </Form>
    </Dialog>
</template>