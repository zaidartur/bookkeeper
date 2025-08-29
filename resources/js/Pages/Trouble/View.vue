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
import { useConfirm } from "primevue/useconfirm";
import moment from 'moment';
import FileUpload from 'primevue/fileupload';

const toast = useToast();
const datas = defineProps({
    lists: Object
})
moment.locale('id')

const dataTrouble = ref(Array())
const initData = () => {
    // dataTrouble.value = datas.lists
    dataTrouble.value = []
    if (datas.lists.length > 0) {
        datas.lists.map((_list) => {
            dataTrouble.value.push(_list)
        })
    }
}
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

const statusForm = ref('new')
const addNew = ref(false)
const confirmation = ref(false)
const submitted = ref(false)
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
    old_foto: null,
});
const formConfirm = useForm({
    uuid: '',
    selesai: '',
    jam: '',
    solusi: '',
    foto: null,
});

// selecting image
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
    if (formAdd.old_foto) {
        formAdd.old_foto = null
    }
}

// selecting image on confirmation
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

const maxDate = ref(new Date())
const rules = ref({
    tglMulai: false,
    jamMulai: false,
    _lokasi: false,
    _deskripsi: false,
    _kategori: false,
    _petugas: false,
    tglSelesai: false,
    jamSelesai: false,
    _solusi: false,
})

const resetValidation = () => {
    rules.value.tglMulai    = false
    rules.value.jamMulai    = false
    rules.value._lokasi     = false
    rules.value._deskripsi  = false
    rules.value._kategori   = false
    rules.value._petugas    = false
    rules.value.tglSelesai  = false
    rules.value.jamSelesai  = false
    rules.value._solusi     = false
}

// console.log(moment().format('L'))
const isTglMulai = () => {
    formAdd.mulai ? rules.value.tglMulai = false : rules.value.tglMulai = true
}
const isJamMulai = () => {
    formAdd.jam ? rules.value.jamMulai = false : rules.value.jamMulai = true
}
const isLokasi = () => {
    formAdd.lokasi ? rules.value._lokasi = false : rules.value._lokasi = true
}
const isDeskripsi = () => {
    formAdd.deskripsi ? rules.value._deskripsi = false : rules.value._deskripsi = true
}
const isKategori = () => {
    formAdd.kategori ? rules.value._kategori = false : rules.value._kategori = true
    selectedCategory.value ? (formAdd.kategori = selectedCategory.value.value) : (formAdd.kategori = '')
}
const isPetugas = () => {
    formAdd.petugas ? rules.value._petugas = false : rules.value._petugas = true
}
const isTglSelesai = () => {
    formConfirm.selesai ? rules.value.tglSelesai = false : rules.value.tglSelesai = true
}
const isJamSelesai = () => {
    formConfirm.jam ? rules.value.jamSelesai = false : rules.value.jamSelesai = true
}
const isSolusi = () => {
    formConfirm.solusi ? rules.value._solusi = false : rules.value._solusi = true
}

const checkValidationNew = () => {
    isTglMulai()
    isJamMulai()
    isLokasi()
    isDeskripsi()
    isKategori()
    isPetugas()
}

const checkValidationConfirm = () => {
    isTglSelesai()
    isJamSelesai()
    isSolusi()
}

const showModal = () => {
    formAdd.reset();
    src.value = null
    clearing.value = false
    resetValidation()

    headerNew.value = 'Input Trouble/Masalah Baru'
    statusForm.value = 'new'
    addNew.value = true
}

const onFormSubmit = () => {
    checkValidationNew()
    if (formAdd.mulai && formAdd.jam && formAdd.lokasi && formAdd.deskripsi && formAdd.kategori && formAdd.petugas) {
        // console.log(formAdd)
        const _time = moment(formAdd.jam).format('HH:mm')
        const _date = moment(formAdd.mulai).format('YYYY-MM-DD')
        formAdd.jam = _time
        formAdd.mulai = _date

        submitted.value = true
        if (statusForm.value === 'new') {
            formAdd.post('/trouble/save', {
                resetOnSuccess: true,
                onSuccess: (res) => {
                    const messages = res.props.flash.message
                    alert_response(messages)
                    submitted.value = false
                    addNew.value = false

                    initData()
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
                    submitted.value = false
                }
            })
        } else if (statusForm.value === 'edit') {
            formAdd.post('/trouble/update', {
                resetOnSuccess: true,
                onSuccess: (res) => {
                    const messages = res.props.flash.message
                    alert_response(messages)
                    submitted.value = false
                    addNew.value = false

                    initData()
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
                    submitted.value = false
                }
            })
        } else {
            return false
        }
    } else {
        toast.add({ severity: 'error', summary: 'Mohon isi data yang diwajibkan!', life: 3000 });
    }
}

const onFormConfirm = () => {
    checkValidationConfirm()
    if (formConfirm.selesai && formConfirm.jam && formConfirm.solusi) {
        // console.log(formConfirm)
        const _date = moment(formConfirm.selesai).format('YYYY-MM-DD')
        const _time = moment(formConfirm.jam).format('HH:mm')
        formConfirm.selesai = _date
        formConfirm.jam = _time

        submitted.value = true
        formConfirm.post('/trouble/confirm', {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)
                submitted.value = false
                confirmation.value = false

                initData()
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
                submitted.value = false
            }
        })
    } else {
        toast.add({ severity: 'error', summary: 'Mohon isi data yang diwajibkan!', life: 3000 });
    }
}

const editForm = (_edit) => {
    formAdd.reset()

    formAdd.uuid        = _edit.uuid
    formAdd.mulai       = _edit.tgl_trouble
    formAdd.jam         = _edit.jam_trouble
    formAdd.lokasi      = _edit.lokasi
    formAdd.petugas     = _edit.petugas
    formAdd.deskripsi   = _edit.problem
    formAdd.kategori    = _edit.kategori
    
    if (_edit.foto_awal) {
        formAdd.old_foto= _edit.foto_awal
        src.value       = _edit.foto_awal
    }
    categories.value.some((cat) => {
        if (cat.value === _edit.kategori) {
            selectedCategory.value = cat
            return true
        }
    })

    headerNew.value = 'Edit Trouble/Masalah'
    statusForm.value = 'edit'
    addNew.value = true
}

const detailForm = (isDetail) => {
    //
}

const confirmDialog = (isDetail) => {
    if (isDetail) {
        formAdd.mulai       = moment(isDetail.tgl_trouble).format('DD MMMM YYYY')
        formAdd.jam         = isDetail.jam_trouble
        formAdd.kategori    = isDetail.kategori
        formAdd.petugas     = isDetail.petugas
        detailLokasi.value  = isDetail.lokasi
        detailDeskripsi.value = isDetail.problem
        if (isDetail.foto_awal) {
            src.value = isDetail.foto_awal
        } else {
            src.value = null
        }

        formConfirm.reset()
        formConfirm.uuid = isDetail.uuid

        confirmation.value = true
    }
}

const dialConfirm = useConfirm()
const deleteData = (props) => {
    dialConfirm.require({
        message: `Anda yakin ingin menghapus lokasi ${props.lokasi}?`,
        header: 'Danger Zone',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Batal',
        rejectProps: {
            label: 'Batal',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Hapus',
            severity: 'danger'
        },
        accept: () => {
            if (props.uuid) {
                confirmDelete(props.uuid)
            } else {
                toast.add({ severity: 'error', summary: 'Error', detail: 'ID tidak terdaftar', life: 3000 });
            }
        }
    });
}

const confirmDelete = (uid) => {
    if (uid) {
        const form = useForm({
            uuid: uid,
        })
        
        form.post('/trouble/delete', {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)

                initData()
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
            }
        })
    } else {
        return false
    }
}

const _test = (event) => {
    console.log('work!', event.srcElement.name)
}

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
    <Head title="Network Trouble" />

    <Card class="w-full">
        <template #title><i class="pi pi-sitemap"></i> Data Network Trouble</template>
        <template #content>
            <div class="mt-5 mb-5">
                <Button type="button" label="Buat Data Trouble" severity="info" icon="pi pi-plus-circle" raised @click="showModal" />
            </div>
            <div>
                <DataTable :value="dataTrouble" paginator :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem">
                    <Column field="tgl_trouble" header="Waktu Kejadian" style="width: 15%">
                        <template #body="slotProps">
                            <label>{{ moment(slotProps.data.tgl_trouble).format("DD MMM YYYY") +' '+ slotProps.data.jam_trouble }}</label>
                        </template>
                    </Column>
                    <Column field="lokasi" header="Lokasi" style="width: 20%"></Column>
                    <Column field="problem" header="Trouble" style="width: 20%"></Column>
                    <Column field="kategori" header="Kategori" style="width: 15%">
                        <template #body="slotProps">
                            {{ setCategories(slotProps.data.kategori) }}
                        </template>
                    </Column>
                    <Column header="Status" style="width: 10%">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status.toLowerCase()" :severity="getSeverity(slotProps.data.status)" />
                        </template>
                    </Column>
                    <Column header="Opsi" style="width: 20%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <Button type="button" severity="success" icon="pi pi-wrench" variant="outlined" v-tooltip.bottom="'Selesaikan Masalah'" rounded raised v-if="slotProps.data.status === 'progress'" @click="confirmDialog(slotProps.data)" /> &nbsp;
                            <Button type="button" severity="info" icon="pi pi-info-circle" variant="outlined" v-tooltip.bottom="'Detail Trouble'" rounded raised /> &nbsp;
                            <Button type="button" severity="warn" icon="pi pi-pencil" v-tooltip.bottom="'Edit Trouble'" rounded raised @click="editForm(slotProps.data)" /> &nbsp;
                            <Button type="button" severity="danger" icon="pi pi-trash" variant="outlined" v-tooltip.bottom="'Hapus Data'" rounded raised @click="deleteData(slotProps.data)" />
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
            <Form @submit="onFormSubmit" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="tglMulai" class="font-bold block"> Tanggal Kejadian </label>
                        <DatePicker v-model="formAdd.mulai" showIcon fluid iconDisplay="input" name="tglMulai" :maxDate="maxDate" inputId="tglMulai" :invalid="rules.tglMulai" @blur="isTglMulai" @change="isTglMulai" />
                        <Message v-if="rules.tglMulai" severity="error" size="small" variant="simple">Tanggal Kejadian wajib diisi</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="jamMulai" class="font-bold block">Jam Kejadian </label>
                        <DatePicker v-model="formAdd.jam" showIcon fluid iconDisplay="input" timeOnly inputId="jamMulai" name="jamMulai" :invalid="rules.jamMulai" @blur="isJamMulai" @change="isJamMulai">
                            <template #inputicon="slotProps">
                                <i class="pi pi-clock" @click="slotProps.clickCallback" />
                            </template>
                        </DatePicker>
                        <Message v-if="rules.jamMulai" severity="error" size="small" variant="simple">Jam Kejadian wajib diisi</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Lokasi </label>
                        <Textarea v-model="formAdd.lokasi" rows="6" style="resize: none;" class="w-full" name="_lokasi" :invalid="rules._lokasi" @blur="isLokasi" @change="isLokasi" />
                        <Message v-if="rules._lokasi" severity="error" size="small" variant="simple">Lokasi wajib diisi</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="formAdd.deskripsi" rows="6" style="resize: none;" class="w-full" name="_deskripsi" :invalid="rules._deskripsi" @blur="isDeskripsi" @change="isDeskripsi" />
                        <!-- <Message v-if="$form._deskripsi?.invalid" severity="error" size="small" variant="simple">{{ $form._deskripsi.error?.message }}</Message> -->
                         <Message v-if="rules._deskripsi" severity="error" size="small" variant="simple">Deskripsi wajib diisi</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Kategori Permasalahan </label>
                        <Select v-model="selectedCategory" :options="categories" optionLabel="name" name="_kategori" placeholder="Pilih Kategori" class="w-full" :invalid="rules._kategori" @blur="isKategori" @change="isKategori" />
                        <Message v-if="rules._kategori" severity="error" size="small" variant="simple">Kategori wajib dipilih</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="petugas" class="font-bold block"> Petugas </label>
                        <InputText v-model="formAdd.petugas" placeholder="Nama Petugas" class="w-full" name="_petugas" :invalid="rules._petugas" @blur="isPetugas" @change="isPetugas" />
                        <Message v-if="rules._petugas" severity="error" size="small" variant="simple">Nama Petugas wajib diisi</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 mb-8">
                    <div class="flex-auto justify-items-center">
                        <label for="" class="font-bold block"> Foto Permasalahan (opsional) </label>
                        <FileUpload mode="basic" @select="onFileSelect" customUpload auto severity="secondary" class="p-button-outlined mb-3" accept=".png,.jpg,.jpeg" />
                        <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" raised v-if="clearing" @click="onClearFile" :disabled="submitted" />
                        <img v-if="src" :src="src" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto gap-4 text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="col-3 btn-block mr-5" icon="pi pi-times" raised @click="addNew = false" :disabled="submitted" />
                        <Button type="submit" :label="submitted ? 'Menyimpan' : 'Simpan'" severity="success" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>

    <Dialog v-model:visible="confirmation" maximizable modal header="Konfirmasi Perbaikan Masalah/Trouble" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="flex flex-col md:flex-row">
            <div class="w-full md:w-5/12 flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4 w-45">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Waktu Permasalahan </label>
                        <InputText v-model="formAdd.mulai" placeholder="Tanggal Mulai" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="petugas" class="font-bold block"> Petugas </label>
                        <InputText v-model="formAdd.jam" placeholder="Jam Mulai" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Lokasi </label>
                        <Textarea v-model="detailLokasi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="detailDeskripsi" rows="5" style="resize: none;" class="w-full" disabled />
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
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto justify-items-center">
                        <img v-if="src" :src="src" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" />
                    </div>
                </div>
            </div>
            <div class="w-full md:w-2/12">
                <Divider layout="vertical" class="!hidden md:!flex" />
                <Divider layout="horizontal" class="!flex md:!hidden" align="center" />
            </div>
            <Form @submit="onFormConfirm" class="w-full md:w-5/12 flex flex-col gap-4">
                <!-- <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Waktu Permasalahan </label>
                        <InputText v-model="formAdd.mulai" placeholder="Tanggal Mulai" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="petugas" class="font-bold block"> Petugas </label>
                        <InputText v-model="formAdd.jam" placeholder="Jam Mulai" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Site/Lokasi </label>
                        <Textarea v-model="detailLokasi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Deskripsi Permasalahan </label>
                        <Textarea v-model="detailDeskripsi" rows="5" style="resize: none;" class="w-full" disabled />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <Divider />
                </div> -->
                <div class="card flex flex-wrap gap-4   ">
                    <div class="flex-auto">
                        <label for="tglSelesai" class="font-bold block"> Tanggal Selesai </label>
                        <DatePicker v-model="formConfirm.selesai" showIcon fluid iconDisplay="input" inputId="tglSelesai" name="tglSelesai" :maxDate="maxDate" @blur="isTglSelesai" @change="isTglSelesai" />
                        <Message v-if="rules.tglSelesai" severity="error" size="small" variant="simple">Tanggal Selesai wajib diisi</Message>
                    </div>
                    <div class="flex-auto">
                        <label for="jamSelesai" class="font-bold block">Jam Selesai </label>
                        <DatePicker v-model="formConfirm.jam" showIcon fluid iconDisplay="input" timeOnly inputId="jamSelesai" name="jamSelesai" @blur="isJamSelesai" @change="isJamSelesai">
                            <template #inputicon="slotProps">
                                <i class="pi pi-clock" @click="slotProps.clickCallback" />
                            </template>
                        </DatePicker>
                        <Message v-if="rules.jamSelesai" severity="error" size="small" variant="simple">Jam Selesai wajib diisi</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="icondisplay" class="font-bold block"> Action/Solusi </label>
                        <Textarea v-model="formConfirm.solusi" name="_solusi" rows="5" style="resize: none;" class="w-full" @blur="isSolusi" @change="isSolusi" />
                        <Message v-if="rules._solusi" severity="error" size="small" variant="simple">Solusi wajib diisi</Message>
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 mb-8">
                    <div class="flex-auto justify-items-center">
                        <label for="icondisplay" class="font-bold block"> Foto Selesai (opsional) </label>
                        <FileUpload mode="basic" @select="onFileConfirm" customUpload auto severity="secondary" class="p-button-outlined mb-3" accept=".png,.jpg,.jpeg" />
                        <Button type="button" label="Hapus" severity="warn" icon="pi pi-trash" class="mb-5" raised v-if="fClearing" @click="onClearFileConfirm" />
                        <img v-if="srcFinish" :src="srcFinish" alt="Image" class="shadow-md rounded-xl w-full sm:w-64" />
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <Divider />
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto gap-4 text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="col-3 btn-block mr-5" icon="pi pi-times" raised @click="confirmation = false" :disabled="submitted" />
                        <Button type="submit" :label="submitted ? 'Menyimpan' : 'Konfirmasi'" severity="success" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>
</template>