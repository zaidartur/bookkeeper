<script setup>
import { ref, defineProps, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3';
import Popover from 'primevue/popover';
import InputMask from 'primevue/inputmask';
import IftaLabel from 'primevue/iftalabel';

const datas = defineProps({
    subnets: Object,
    networks: Object,
    lists: Object,
})

const subnet = ref(Array())
const network = ref(Array())
const lists = ref(Array())

const initData = () => {
    subnet.value = []
    network.value = []
    lists.value = []

    if (datas.subnets.length > 0) {
        datas.subnets.map((sub) => {
            subnet.value.push(sub)
        })
    }

    if (datas.networks.length > 0) {
        datas.networks.map((nt) => {
            network.value.push(nt)
        })
    }

    if (datas.lists.length > 0) {
        datas.lists.map((ls) => {
            lists.value.push(ls)
        })
    }
    
    console.log(subnet.value, network.value)
}

onMounted(() => {
    initData()
})

const popCidr = ref()
const toggle = (event) => {
    popCidr.value.toggle(event);
}

const popIp = ref()
const toggleIp = (event) => {
    popIp.value.toggle(event);
}

const submitted = ref(false)
const dialogSubnet = ref(false)
const dialogNetwork = ref(false)
const dialogHost = ref(false)
const headerNetwork = ref('Add New Network')
const formSubnet = useForm()
const formNetwork = useForm({
    uuid: '',
    network: '',
    subnet: '',
    cidr: null,
    total: 0,
    usable: 0,
    keterangan: '',
})
const formHost = useForm()



const addNetwork = () => {
    formNetwork.reset()
    dialogNetwork.value = true
}

const selectSubnet = (e) => {
    formNetwork.subnet = ''
    formNetwork.total = 0
    if (e && e.value > 0) {
        subnet.value.some((sm) => {
            if (sm.cidr === e.value) {
                formNetwork.subnet = sm.subnet_mask
                formNetwork.total = new Intl.NumberFormat('id-ID').format(sm.usable_host)
                return true
            }
        })
    }
}

const onSubmitNetwork = () => {
    console.log(formNetwork)
}
</script>

<template>
    <Toast />
    <ConfirmDialog></ConfirmDialog>
    <Head title="Network IP" />

    <Card class="w-full">
        <template #title><i class="pi pi-sitemap"></i> Data Network IP</template>
        <template #content>
            <!-- <div class="mt-5 mb-5">
                <Button type="button" label="Buat Data Trouble" severity="info" icon="pi pi-plus-circle" raised @click="showModal" />
            </div> -->
            <div>
                <DataTable :value="lists" paginator :rows="25" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem">
                    <template #header>
                        <div class="flex flex-row justify-between">
                            <div class="flex basis-8/12 gap-5">
                                <Button type="button" icon="pi pi-filter-slash" label="Clear Filter" class="basis-2/12" outlined @click="clearFilter()" />
                                <Button type="button" icon="pi pi-sitemap" severity="info" label="Add IP Host" class="basis-2/12" @click="clearFilter()" />
                                <Button type="button" icon="pi pi-sitemap" severity="info" label="IP Address" class="basis-2/12" outlined @click="toggleIp" />
                                <Button type="button" icon="pi pi-sitemap" severity="help" label="CIDR" class="basis-2/12" outlined @click="toggle" />
                            </div>
                            <div class="flex basis-4/12 gap-5 justify-end">
                                <Button type="button" icon="pi pi-file-import" severity="secondary" label="Import Data" class="w-4/12 float-right" outlined @click="false" />
                            </div>
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column field="assigned_ip" header="IP Host" style="width: 15%">
                        <template #body="slotProps">
                            <label>{{ slotProps.data.assigned_ip }}</label>
                        </template>
                    </Column>
                    <Column field="lokasi" header="Parents" style="width: 20%"></Column>
                    <Column field="problem" header="Device" style="width: 20%"></Column>
                    <Column field="kategori" header="Kategori" style="width: 15%">
                        <template #body="slotProps">
                            {{ setCategories(slotProps.data.kategori) }}
                        </template>
                    </Column>
                    <Column field="keterangan" header="Keterangan" style="width: 10%"></Column>
                    <Column header="Opsi" style="width: 20%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="flex gap-4">
                                <Button type="button" severity="success" icon="pi pi-wrench" variant="outlined" v-tooltip.bottom="'Selesaikan Masalah'" rounded raised @click="confirmDialog(slotProps.data)" />
                                <Button type="button" severity="info" icon="pi pi-info-circle" variant="outlined" v-tooltip.bottom="'Detail Trouble'" rounded raised />
                                <Button type="button" severity="warn" icon="pi pi-pencil" v-tooltip.bottom="'Edit Trouble'" rounded raised @click="editForm(slotProps.data)" />
                                <Button type="button" severity="danger" icon="pi pi-trash" variant="outlined" v-tooltip.bottom="'Hapus Data'" rounded raised @click="deleteData(slotProps.data)" />
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

    <Popover ref="popCidr">
        <div class="flex flex-col gap-4 w-[40rem]">
            <DataTable :value="subnet" paginator selectionMode="single" :rows="10" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="max-width: 40rem">
                <template #header>
                    <div class="flex justify-between">
                        <h4 class="font-bold text-xl text-center w-full">List of CIDR</h4>
                        <!-- <Button type="button" icon="pi pi-plus-circle" label="Tambah Subnet" size="small" outlined @click="clearFilter()" /> -->
                    </div>
                </template>
                <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                <Column field="cidr" header="CIDR" class="text-center" style="width: 20%"></Column>
                <Column field="subnet_mask" header="Subnet Mask" class="text-center" style="width: 20%"></Column>
                <Column field="wildcard_mask" header="Wildcard" class="text-center" style="width: 20%"></Column>
                <Column field="total_ip" header="Usable IP" class="text-center" style="width: 20%">
                    <template #body="slotProps">
                        <label>{{ new Intl.NumberFormat('id-ID').format(slotProps.data.total_ip) }}</label>
                    </template>
                </Column>
                <Column field="ip_class" header="IP Class" class="text-center" style="width: 20%"></Column>
            </DataTable>
        </div>
    </Popover>

    <Popover ref="popIp">
        <div class="flex flex-col gap-4 w-[40rem]">
            <DataTable :value="network" paginator :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="max-width: 40rem">
                <template #header>
                    <div class="flex justify-between">
                        <h4 class="text-xl font-medium">List of IP Address</h4>
                        <Button type="button" icon="pi pi-plus-circle" label="Tambah IP Address" size="small" outlined @click="addNetwork" />
                    </div>
                </template>
                <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                <Column header="IP Address/Subnet" class="text-center" style="width: 30%">
                    <template #body="slotProps">
                        <label>{{ slotProps.data.network_ip }}/{{ slotProps.data.cidr }}</label>
                    </template>
                </Column>
                <!-- <Column field="cidr" header="Subnet Mask" class="text-center" style="width: 20%"></Column> -->
                <Column header="Total Host" class="text-center" style="width: 20%">
                    <template #body="slotProps">
                        <label>{{ new Intl.NumberFormat('id-ID').format(slotProps.data.usable_hosts)  }}</label>
                    </template>
                </Column>
                <Column header="Usable IP" class="text-center" style="width: 20%">
                    <template #body="slotProps">
                        <label>{{ slotProps.data.usable_hosts }}</label>
                    </template>
                </Column>
                <Column field="keterangan" header="Keterangan" class="text-center" style="width: 30%"></Column>
            </DataTable>
        </div>
    </Popover>

    <Dialog v-model:visible="dialogNetwork" maximizable modal :header="headerNetwork" :style="{width: '60rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div>
            <Form @submit.prevent="onSubmitNetwork" class="flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> IP Address </label>
                        <InputText v-model="formNetwork.network" placeholder="192.168.1.0" class="w-full" name="ip_network" autofocus />
                        <!-- <Message v-if="rules._kategori" severity="error" size="small" variant="simple">Kategori wajib dipilih</Message> -->
                    </div>
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Subnet Mask </label>
                        <Select v-model="formNetwork.cidr" :options="subnet" optionLabel="cidr" optionValue="cidr" showClear name="_cidr" placeholder="Pilih Subnet" class="w-full" @change="selectSubnet($event)" />
                        <!-- <Message v-if="rules._petugas" severity="error" size="small" variant="simple">Nama Petugas wajib diisi</Message> -->
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <IftaLabel>
                            <InputText v-model="formNetwork.total" placeholder="0" v-keyfilter.num class="w-full" name="total" id="total" readonly />
                            <label for="total" class="font-bold block"> Total Host </label>
                        </IftaLabel>
                        <!-- <Message v-if="rules._kategori" severity="error" size="small" variant="simple">Kategori wajib dipilih</Message> -->
                    </div>
                    <div class="flex-auto">
                        <IftaLabel>
                            <label for="subnet_sel" class="font-bold block"> Subnet Mask </label>
                            <InputText v-model="formNetwork.subnet" placeholder="0" class="w-full" name="subnet_sel" id="subnet_sel" readonly />
                        </IftaLabel>
                        <!-- <Message v-if="rules._petugas" severity="error" size="small" variant="simple">Nama Petugas wajib diisi</Message> -->
                    </div>
                </div>
                <div class="card flex flex-wrap gap-4 -mt-20">
                    <div class="flex-auto">
                        <label for="" class="font-bold block"> Keterangan </label>
                        <Textarea v-model="formNetwork.keterangan" rows="6" style="resize: none;" class="w-full" name="_deskripsi" />
                    </div>
                </div>

                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto gap-4 text-center">
                        <Button type="button" label="Tutup" severity="secondary" class="col-3 btn-block mr-5" icon="pi pi-times" raised @click="dialogNetwork = false" :disabled="submitted" />
                        <Button type="submit" :label="submitted ? 'Menyimpan' : 'Simpan'" severity="success" :icon="submitted ? 'pi pi-spin pi-spinner' : 'pi pi-save'" raised :disabled="submitted" />
                    </div>
                </div>
            </Form>
        </div>
    </Dialog>

</template>