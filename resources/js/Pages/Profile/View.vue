<script setup>
import { ref, onMounted } from 'vue'
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import moment from 'moment';
import id from 'moment/dist/locale/id';
import axios from 'axios';

const toast = useToast()
const confirm = useConfirm()
const datas = defineProps({
    user: Object,
    activity: Object,
    lists: Object,
})
const userList = ref(new Array())
const loading = ref(true)
const submitted = ref(false)
const spinner = ref(false)
const newDialog = ref(false)
const editDialog = ref(false)
const editPassword = ref(false)
const checkPwd = ref(false)
const checkMode = ref(null)
const inputPwd = ref(null)
const inputUid = ref(null)
const initData = () => {
    userList.value = []
    if (datas.lists && datas.lists.length > 0) {
        datas.lists.map((dl) => {
            userList.value.push(dl)
        })
    }
    loading.value = false
}
initData()

const formUser = useForm({
    uuid: null,
    name: null,
    email: null,
    password: null,
})

const formPwd = useForm({
    uuid: null,
    password: null,
    password_confirm: null
})

onMounted(() => {
    initData()
})

const edit_data = () => {
    formUser.reset()
    formUser.uuid = datas.user?.uuid
    formUser.name = datas.user?.name
    formUser.email = datas.user?.email

    editDialog.value = true
}

const checking = () => {
    if (inputPwd.value && inputUid.value) {
        submitted.value = true
        axios.post(route('profile.password.check'), {uuid: inputUid.value, password: btoa(inputPwd.value)}).then((response) => {
            // console.log(response)
            if (response.data.status === 'success') {
                toast.add({ severity: 'success', summary: 'OK', detail: 'Password sesuai', life: 3000 });
                checkPwd.value = false
                if (checkMode.value === 'password') {
                    formPwd.reset()
                    formPwd.uuid = inputUid.value
                    editPassword.value = true
                } else if (checkMode.value === 'user') {
                    confirm_drop(inputUid.value)
                }
            } else {
                toast.add({ severity: 'error', summary: 'Error', detail: response.data.msg, life: 3000 });
            }
            submitted.value = false
        }).catch((err) => {
            console.log(err)
            submitted.value = false
        })
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Password wajib diisi', life: 3000 });
    }
}

const change_pwd = (uid) => {
    inputUid.value = uid
    inputPwd.value = null
    checkMode.value = 'password'
    checkPwd.value = true
}

const update_pwd = () => {
    if (formPwd.password && formPwd.password_confirm) {
        if (formPwd.password === formPwd.password_confirm) {
            submitted.value = true
            formPwd.post(route('profile.password.update'), {
                resetOnSuccess: true,
                onSuccess: (res) => {
                    const messages = res.props.flash.message
                    alert_response(messages)

                    initData()
                    submitted.value = false
                    editPassword.value = false
                    if (formPwd.uuid === inputUid.value) {
                        const logout = useForm()
                        logout.post(route('logout'))
                    }
                },
                onError: () => {
                    submitted.value = false
                    toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
                }
            })
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Password tidak sama', life: 3000 });
        }
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Harap mengisi semua data inputan', life: 3000 });
    }
}

const edit_user = (props) => {
    console.log(props)
    if (props) {
        formUser.reset()
        formUser.uuid = props.uuid
        formUser.name = props.name
        formUser.email = props.email

        editDialog.value = true
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Data tidak ditemukan', life: 3000 });
    }
}

const updateProfile = () => {
    if (formUser.uuid && formUser.name && formUser.email) {
        submitted.value = true
        formUser.post(route('profile.update'), {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)

                initData()
                submitted.value = false
                editDialog.value = false
            },
            onError: () => {
                submitted.value = false
                toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
            }
        })
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Data tidak lengkap', life: 3000 });
    }
}

const new_user = () => {
    formUser.uuid   = null
    formUser.name   = null
    formUser.email  = null
    formUser.password = null

    inputPwd.value = null
    newDialog.value = true
}

const save_user = () => {
    if (formUser.name && formUser.email && formUser.password) {
        if (formUser.password === inputPwd.value) {
            submitted.value = true
            formUser.post(route('profile.user.new'), {
                resetOnSuccess: true,
                onSuccess: (res) => {
                    const messages = res.props.flash.message
                    alert_response(messages)

                    initData()
                    submitted.value = false
                    newDialog.value = false
                },
                onError: () => {
                    submitted.value = false
                    toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
                }
            })
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Password tidak sama', life: 3000 });
        }
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Inputan wajib diisi', life: 3000 });
    }
}

const drop_user = (uid, name) => {
    confirm.require({
        message: `Anda yakin ingin menghapus user ${name} ?`,
        header: 'Danger Zone',
        icon: 'pi pi-info-circle',
        rejectLabel: 'Batal',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Konfirmasi',
            severity: 'danger'
        },
        accept: () => {
            // toast.add({ severity: 'info', summary: 'Confirmed', detail: 'Record deleted', life: 3000 });
            inputUid.value = uid
            inputPwd.value = null
            checkMode.value = 'user'
            checkPwd.value = true
            
        },
        reject: () => {
            // toast.add({ severity: 'error', summary: 'Rejected', detail: 'You have rejected', life: 3000 });
        }
    });
}

const confirm_drop = (uid) => {
    if (uid) {
        const fdrop = useForm({uuid: uid})
        spinner.value = true
        fdrop.post(route('profile.user.drop'), {
            resetOnSuccess: true,
            onSuccess: (res) => {
                const messages = res.props.flash.message
                alert_response(messages)

                initData()
                spinner.value = false
            },
            onError: () => {
                spinner.value = false
                toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
            }
        })
    }
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
    <Head title="Profile" />

    <div class="flex flex-row gap-4">
        <div class="basis-1/3">
            <Card style="width: 100%; overflow: hidden">
                <template #header>
                    <img alt="user header" src="https://primefaces.org/cdn/primevue/images/usercard.png" />
                </template>
                <template #title>{{ datas.user?.name }}</template>
                <template #subtitle>admin</template>
                <template #content>
                    <p class="m-0 mb-5">
                        {{ datas.user?.email }} <br><br>

                        Updated {{ datas.user?.updated_at ? moment(datas.user?.updated_at).format('LLL') : moment(datas.user?.created_at).format('LL') }} <br>
                        Last Activity {{ moment.unix(datas.activity?.last_activity).format('LLL') }}
                    </p>
                    <label>&nbsp;</label>
                </template>
                <template #footer>
                    <div class="flex gap-4 mt-1">
                        <Button label="Ubah Password" icon="pi pi-lock" severity="danger" outlined class="w-full" @click="change_pwd(datas.user?.uuid)" />
                        <Button label="Perbarui Data" icon="pi pi-user-edit" class="w-full" @click="edit_data" />
                    </div>
                </template>
            </Card>
        </div>
        <div class="basis-2/3">
            <Card class="w-full">
                <template #title><i class="pi pi-book"></i> Daftar Akun</template>
                <template #content>
                    <DataTable :value="userList" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading">
                        <template #header>
                            <Button label="Buat Akun Baru" icon="pi pi-user-plus" severity="success" @click="new_user" />
                        </template>
                        <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                        <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                        <Column field="name" header="Nama User" style="width: 20%"></Column>
                        <Column field="email" header="Email" style="width: 20%"></Column>
                        <Column header="Opsi" style="width: 20%; text-align: center;" class="w-full text-center">
                            <template #body="slotProps">
                                <div class="w-full text-center flex gap-2">
                                    <Button icon="pi pi-pencil" @click="edit_user(slotProps.data)" v-tooltip.bottom="'Edit User'" severity="secondary" rounded />
                                    <Button icon="pi pi-key" @click="change_pwd(slotProps.data.uuid)" v-tooltip.bottom="'Ubah Password'" severity="warn" rounded />
                                    <Button icon="pi pi-trash" @click="drop_user(slotProps.data.uuid, slotProps.data.name)" v-tooltip.bottom="'Hapus User ' + slotProps.data.name" severity="danger" rounded v-if="slotProps.data.uuid != datas.user?.uuid" />
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
                </template>
            </Card>
        </div>
    </div>

    <Dialog v-model:visible="editDialog" modal header="Edit Profile" :style="{ width: '25rem' }">
        <span class="text-surface-500 dark:text-surface-400 block mb-8">Update Profile.</span>
        <div class="flex items-center gap-4 mb-4">
            <label for="name" class="font-semibold w-24">Name</label>
            <InputText v-model="formUser.name" id="name" class="flex-auto" maxlength="25" autocomplete="name" autofocus />
        </div>
        <div class="flex items-center gap-4 mb-8">
            <label for="email" class="font-semibold w-24">Email</label>
            <InputText v-model="formUser.email" id="email" class="flex-auto" autocomplete="off" disabled />
        </div>
        <div class="flex justify-end gap-2">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="editDialog = false"></Button>
            <Button type="button" :label="submitted ? 'Memproses...' : 'Simpan'" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" @click="updateProfile"></Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="checkPwd" modal :style="{ width: '25rem' }" :closable="false">
        <template #header>
            <span class="text-2xl block mb-8 text-center w-full">Konfirmasi Password</span>
        </template>
        <div class="flex flex-col items-center gap-4 mb-10">
            <label for="password" class="font-semibold">Masukkan Password Anda</label>
            <Password v-model="inputPwd" id="password" autofocus toggleMask />
            <!-- <InputText id="password" class="flex-auto" autocomplete="off" /> -->
        </div>
        <div class="flex justify-center gap-2">
            <Button type="button" label="Batalkan" icon="pi pi-times" severity="secondary" @click="checkPwd = false"></Button>
            <Button type="button" :label="submitted ? 'Memproses...' : 'Konfirmasi'" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" @click="checking"></Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="editPassword" modal header="Ubah Password" :style="{ width: '25rem' }">
        <span class="text-surface-500 dark:text-surface-400 block mb-8 text-center w-full">Masukkan Password Baru</span>
        <div class="flex flex-col items-center">
            <div class="flex flex-col items-start gap-4 mb-4">
                <label for="password" class="font-semibold">Password Baru</label>
                <Password v-model="formPwd.password" id="password" toggleMask autofocus />
            </div>
            <div class="flex flex-col items-start gap-4 mb-8">
                <label for="password_confirm" class="font-semibold">Konfirmasi Password Baru</label>
                <Password v-model="formPwd.password_confirm" id="password_confirm" toggleMask />
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="editPassword = false"></Button>
            <Button type="button" :label="submitted ? 'Memproses...' : 'Simpan'" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" @click="update_pwd"></Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="newDialog" modal header="Buat Akun" :style="{ width: '35rem' }">
        <span class="text-surface-500 dark:text-surface-400 block mb-8 text-center w-full">Buat Akun Baru</span>
        <div class="flex flex-col items-center">
            <div class="flex flex-col items-start gap-2 mb-4 w-full">
                <label for="name" class="font-semibold">Name</label>
                <InputText v-model="formUser.name" id="name" class="flex-auto w-full" maxlength="25" autocomplete="name" autofocus />
            </div>
            <div class="flex flex-col items-start gap-2 mb-4 w-full">
                <label for="email" class="font-semibold">Email</label>
                <InputText v-model="formUser.email" id="email" class="flex-auto w-full" maxlength="50" autocomplete="email" v-tooltip.focus.top="'Pastikan alamat email berbeda dengan yang sudah terdaftar'" />
            </div>
            <div class="flex flex-col items-start gap-2 mb-4 w-full">
                <label for="password" class="font-semibold">Password</label>
                <Password v-model="formUser.password" id="password" toggleMask />
            </div>
            <div class="flex flex-col items-start gap-2 mb-8 w-full">
                <label for="password_confirm" class="font-semibold">Konfirmasi Password</label>
                <Password v-model="inputPwd" id="password_confirm" toggleMask />
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <Button type="button" label="Batalkan" icon="pi pi-times" severity="secondary" @click="newDialog = false"></Button>
            <Button type="button" :label="submitted ? 'Memproses...' : 'Simpan'" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" @click="save_user"></Button>
        </div>
    </Dialog>

    <Dialog v-model:visible="spinner" modal :style="{ width: '35rem' }" :closable="false" pt:root:class="!border-0 !bg-transparent" pt:mask:class="backdrop-blur-sm">
        <div class="flex flex-col gap-4 justify-center">
            <div class="flex-auto text-center">
                <i class="pi pi-spin pi-spinner-dotted" style="font-size: 5rem"></i>
            </div>
            <div class="flex-auto text-center">
                <label>Memproses...</label>
            </div>
        </div>
    </Dialog>
</template>