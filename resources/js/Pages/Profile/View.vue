<script setup>
import { ref, onMounted } from 'vue'
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';
import id from 'moment/dist/locale/id';

const Toast = useToast()
const datas = defineProps({
    user: Object,
    activity: Object,
    lists: Object,
})
const userList = ref(new Array())
const loading = ref(true)
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

onMounted(() => {
    initData()
})

const edit_data = () => {
    //
}

const change_pwd = () => {
    //
}

const edit_user = (props) => {
    //
}
</script>

<template>
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
                        <Button label="Ubah Password" icon="pi pi-lock" severity="danger" outlined class="w-full" @click="edit_data" />
                        <Button label="Perbarui Data" icon="pi pi-user-edit" class="w-full" @click="change_pwd" />
                    </div>
                </template>
            </Card>
        </div>
        <div class="basis-2/3">
            <Card class="w-full">
                <template #title><i class="pi pi-book"></i> Laporan Maintenance</template>
                <template #content>
                    <DataTable :value="userList" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" filterDisplay="menu" dataKey="id" :loading="loading">
                        <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                        <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                        <Column field="name" header="Nama User" style="width: 20%"></Column>
                        <Column field="email" header="Email" style="width: 20%"></Column>
                        <Column header="Opsi" style="width: 20%; text-align: center;">
                            <template #body="slotProps">
                                <div class="w-full text-center">
                                    <Button icon="pi pi-pencil" @click="edit_user(slotProps.data)" v-tooltip.bottom="'Edit User'" severity="secondary" rounded />
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
</template>