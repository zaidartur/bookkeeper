<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from '@primevue/core/api';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    user: Object,
    petugas: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({
            total: 0,
            jaringan: 0,
            datacenter: 0,
            helpdesk: 0
        })
    }
});

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const dataPetugas = ref([]);
const loading = ref(false);
const formDialog = ref(false);
const detailDialog = ref(false);
const isEditMode = ref(false);
const selectedPetugas = ref(null);
const submitted = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    bidang: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const bidangOptions = [
    { label: 'Semua Bidang', value: null },
    { label: 'Infrastruktur & Jaringan', value: 'Infrastruktur & Jaringan' },
    { label: 'Operasional Data Center', value: 'Operasional Data Center' },
    { label: 'Helpdesk & Tiket Gangguan', value: 'Helpdesk & Tiket Gangguan' },
    { label: 'Aplikasi & SPBE', value: 'Aplikasi & SPBE' },
    { label: 'Tata Kelola IT', value: 'Tata Kelola IT' }
];

const selectedBidangFilter = ref(null);

const form = useForm({
    uuid_petugas: '',
    nama_petugas: '',
    nip: '',
    bidang: 'Infrastruktur & Jaringan',
    phone: '',
    alamat: '',
    foto_profile: '',
});

const initData = () => {
    dataPetugas.value = props.petugas ? [...props.petugas] : [];
};

onMounted(() => {
    initData();
    // Check flash message
    if (page.props.flash?.message) {
        const msg = page.props.flash.message;
        toast.add({
            severity: msg.status === 'success' ? 'success' : 'error',
            summary: msg.status === 'success' ? 'Sukses' : 'Gagal',
            detail: msg.msg,
            life: 3500
        });
    }
});

const openNew = () => {
    form.reset();
    form.clearErrors();
    form.bidang = 'Infrastruktur & Jaringan';
    isEditMode.value = false;
    submitted.value = false;
    formDialog.value = true;
};

const editPetugas = (item) => {
    form.reset();
    form.clearErrors();
    form.uuid_petugas = item.uuid_petugas;
    form.nama_petugas = item.nama_petugas;
    form.nip = item.nip || '';
    form.bidang = item.bidang || 'Infrastruktur & Jaringan';
    form.phone = item.phone || '';
    form.alamat = item.alamat || '';
    form.foto_profile = item.foto_profile || '';
    isEditMode.value = true;
    submitted.value = false;
    formDialog.value = true;
};

const viewDetail = (item) => {
    selectedPetugas.value = item;
    detailDialog.value = true;
};

const savePetugas = () => {
    submitted.value = true;

    if (!form.nama_petugas || form.nama_petugas.trim() === '') {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Nama Petugas wajib diisi!', life: 3000 });
        return;
    }

    loading.value = true;
    if (isEditMode.value) {
        form.post(`/petugas/update/${form.uuid_petugas}`, {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                initData();
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data Petugas berhasil diperbarui', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat memperbarui data', life: 3000 });
            }
        });
    } else {
        form.post('/petugas/save', {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                initData();
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data Petugas baru berhasil ditambahkan', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan saat menyimpan data', life: 3000 });
            }
        });
    }
};

const deletePetugas = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus petugas "${item.nama_petugas}"?`,
        header: 'Konfirmasi Penghapusan',
        icon: 'pi pi-exclamation-triangle text-red-500',
        rejectLabel: 'Batal',
        acceptLabel: 'Hapus',
        rejectProps: {
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            severity: 'danger'
        },
        accept: () => {
            router.post(`/petugas/delete/${item.uuid_petugas}`, {}, {
                onSuccess: () => {
                    initData();
                    toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Data Petugas berhasil dihapus', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menghapus data petugas', life: 3000 });
                }
            });
        }
    });
};

const onBidangFilterChange = (val) => {
    filters.value.bidang.value = val;
};

const getBidangSeverity = (bidang) => {
    switch (bidang) {
        case 'Infrastruktur & Jaringan':
            return 'info';
        case 'Operasional Data Center':
            return 'success';
        case 'Helpdesk & Tiket Gangguan':
            return 'warn';
        case 'Aplikasi & SPBE':
            return 'primary';
        default:
            return 'secondary';
    }
};

const getInitials = (name) => {
    if (!name) return 'PT';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const formatWaLink = (phone) => {
    if (!phone) return '#';
    let clean = phone.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    }
    return `https://wa.me/${clean}?text=Halo%20Bapak%2FIbu%20Petugas%20IT%2C%20terkait%20layanan%20jaringan...`;
};
</script>

<template>
    <Head title="Data Petugas & Personel IT" />
    <Toast />
    <ConfirmDialog />

    <div class="space-y-6">
        <!-- HEADER & PAGE TITLE -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold">
                        <i class="pi pi-users text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0 m-0">
                            Data Petugas & Tim Teknis IT
                        </h1>
                        <p class="text-surface-500 dark:text-surface-400 text-sm mt-1 mb-0">
                            Manajemen personel administrator jaringan, operasional NOC/Data Center, dan teknisi helpdesk.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button label="Tambah Petugas" icon="pi pi-user-plus" severity="primary" @click="openNew" class="shadow-sm" />
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Petugas -->
            <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Total Petugas</span>
                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                        {{ summary?.total ?? dataPetugas.length }}
                    </div>
                    <span class="text-xs text-primary font-medium mt-1 inline-block">Personel Terdaftar</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                    <i class="pi pi-id-card"></i>
                </div>
            </div>

            <!-- Infrastruktur & Jaringan -->
            <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Tim Jaringan</span>
                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                        {{ summary?.jaringan ?? 0 }}
                    </div>
                    <span class="text-xs text-cyan-600 dark:text-cyan-400 font-medium mt-1 inline-block">Kabel FO, Switch & IPAM</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xl">
                    <i class="pi pi-sitemap"></i>
                </div>
            </div>

            <!-- Operasional Data Center -->
            <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Data Center / NOC</span>
                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                        {{ summary?.datacenter ?? 0 }}
                    </div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1 inline-block">Server & Power System</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                    <i class="pi pi-server"></i>
                </div>
            </div>

            <!-- Helpdesk & Tiket Gangguan -->
            <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Helpdesk & SLA</span>
                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                        {{ summary?.helpdesk ?? 0 }}
                    </div>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-1 inline-block">Penanganan Insiden IT</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl">
                    <i class="pi pi-headphones"></i>
                </div>
            </div>
        </div>

        <!-- MAIN DATATABLE CARD -->
        <div class="bg-surface-0 dark:bg-surface-800 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700 overflow-hidden">
            <DataTable
                v-model:filters="filters"
                :value="props.petugas"
                paginator
                :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                filterDisplay="menu"
                dataKey="id"
                :loading="loading"
                :globalFilterFields="['nama_petugas', 'nip', 'bidang', 'phone', 'alamat']"
                class="p-datatable-sm"
                tableStyle="min-width: 60rem"
            >
                <!-- TABLE HEADER -->
                <template #header>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-2">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-semibold text-surface-900 dark:text-surface-0">
                                Daftar Personel Petugas ({{ props.petugas.length }})
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Filter Bidang -->
                            <Select
                                v-model="selectedBidangFilter"
                                :options="bidangOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Semua Bidang"
                                class="w-48 text-sm"
                                @change="onBidangFilterChange(selectedBidangFilter)"
                                showClear
                            />

                            <!-- Search Global -->
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-surface-400" />
                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Cari nama, NIP, kontak..."
                                    class="w-56 text-sm"
                                />
                            </IconField>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="text-center py-12">
                        <i class="pi pi-users text-4xl text-surface-400 mb-3 block"></i>
                        <span class="text-surface-600 dark:text-surface-400 font-medium">Belum ada data petugas yang tersimpan.</span>
                        <div class="mt-3">
                            <Button label="Tambah Petugas Sekarang" icon="pi pi-plus" size="small" @click="openNew" />
                        </div>
                    </div>
                </template>

                <!-- KOLOM NO -->
                <Column header="No." style="width: 50px" class="text-center">
                    <template #body="{ index }">
                        <span class="text-xs text-surface-500 font-mono">{{ index + 1 }}</span>
                    </template>
                </Column>

                <!-- KOLOM NAMA & IDENTITAS -->
                <Column field="nama_petugas" header="Petugas / Identitas" sortable style="min-width: 240px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3 py-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm bg-gradient-to-tr from-primary to-primary-400 text-white shadow-sm flex-shrink-0">
                                <span v-if="!data.foto_profile">{{ getInitials(data.nama_petugas) }}</span>
                                <img v-else :src="data.foto_profile" alt="Avatar" class="w-full h-full rounded-full object-cover" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-surface-900 dark:text-surface-0 truncate hover:text-primary cursor-pointer" @click="viewDetail(data)">
                                    {{ data.nama_petugas }}
                                </div>
                                <div class="text-xs text-surface-500 flex items-center gap-2 mt-0.5">
                                    <span v-if="data.nip" class="font-mono bg-surface-100 dark:bg-surface-700 px-1.5 py-0.5 rounded text-[11px]">
                                        NIP: {{ data.nip }}
                                    </span>
                                    <span v-else class="italic text-[11px] text-surface-400">Non-NIP / Kontrak</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- KOLOM BIDANG / DIVISI -->
                <Column field="bidang" header="Bidang / Penugasan" sortable style="min-width: 180px">
                    <template #body="{ data }">
                        <Tag :value="data.bidang || 'Umum'" :severity="getBidangSeverity(data.bidang)" class="text-xs font-semibold px-2 py-1" />
                    </template>
                </Column>

                <!-- KOLOM TELEPON & WHATSAPP -->
                <Column field="phone" header="Kontak / WhatsApp" style="min-width: 170px">
                    <template #body="{ data }">
                        <div v-if="data.phone" class="flex items-center gap-2">
                            <span class="font-mono text-xs text-surface-700 dark:text-surface-200">{{ data.phone }}</span>
                            <a
                                :href="formatWaLink(data.phone)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-200 transition-colors"
                                title="Chat via WhatsApp"
                            >
                                <i class="pi pi-whatsapp text-sm"></i>
                            </a>
                        </div>
                        <span v-else class="text-xs text-surface-400 italic">- Belum ada -</span>
                    </template>
                </Column>

                <!-- KOLOM LOKASI / ALAMAT -->
                <Column field="alamat" header="Lokasi / Posko" style="min-width: 200px">
                    <template #body="{ data }">
                        <div class="text-xs text-surface-600 dark:text-surface-300 truncate max-w-xs" :title="data.alamat">
                            <i class="pi pi-map-marker text-[11px] text-surface-400 mr-1"></i>
                            {{ data.alamat || 'Kantor Diskominfo' }}
                        </div>
                    </template>
                </Column>

                <!-- KOLOM AKSI -->
                <Column header="Aksi" style="width: 130px" class="text-center">
                    <template #body="{ data }">
                        <div class="flex items-center justify-center gap-1.5">
                            <Button
                                icon="pi pi-eye"
                                text
                                rounded
                                severity="info"
                                size="small"
                                @click="viewDetail(data)"
                                title="Detail Petugas"
                            />
                            <Button
                                icon="pi pi-pencil"
                                text
                                rounded
                                severity="warn"
                                size="small"
                                @click="editPetugas(data)"
                                title="Edit Petugas"
                            />
                            <Button
                                icon="pi pi-trash"
                                text
                                rounded
                                severity="danger"
                                size="small"
                                @click="deletePetugas(data)"
                                title="Hapus Petugas"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>

    <!-- DIALOG TAMBAH / EDIT PETUGAS -->
    <Dialog
        v-model:visible="formDialog"
        :header="isEditMode ? 'Edit Data Petugas' : 'Tambah Petugas Baru'"
        :modal="true"
        class="w-full max-w-lg"
        :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
    >
        <div class="space-y-4 pt-2">
            <!-- NAMA PETUGAS -->
            <div>
                <label for="nama_petugas" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                    Nama Lengkap Petugas <span class="text-red-500">*</span>
                </label>
                <InputText
                    id="nama_petugas"
                    v-model="form.nama_petugas"
                    placeholder="Contoh: Ahmad Rifai, S.Kom"
                    class="w-full text-sm"
                    :class="{ 'p-invalid': submitted && !form.nama_petugas }"
                />
                <small v-if="submitted && !form.nama_petugas" class="text-red-500 text-xs">
                    Nama petugas wajib diisi.
                </small>
            </div>

            <!-- NIP / ID PEGAWAI -->
            <div>
                <label for="nip" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                    NIP / Identitas Pegawai
                </label>
                <InputText
                    id="nip"
                    v-model="form.nip"
                    placeholder="Contoh: 198804152011011002 (Kosongkan bila Non-PNS)"
                    class="w-full text-sm font-mono"
                />
            </div>

            <!-- BIDANG / DIVISI -->
            <div>
                <label for="bidang" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                    Bidang / Penugasan
                </label>
                <Select
                    id="bidang"
                    v-model="form.bidang"
                    :options="bidangOptions.filter(b => b.value !== null)"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Pilih Bidang Penugasan"
                    class="w-full text-sm"
                    editable
                />
                <small class="text-surface-400 text-xs">Anda juga dapat mengetik bidang kustom secara manual.</small>
            </div>

            <!-- NOMOR TELEPON / WHATSAPP -->
            <div>
                <label for="phone" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                    Nomor WhatsApp / Telepon
                </label>
                <InputText
                    id="phone"
                    v-model="form.phone"
                    placeholder="Contoh: 081234567890"
                    class="w-full text-sm font-mono"
                />
            </div>

            <!-- LOKASI / POSKO ALAMAT -->
            <div>
                <label for="alamat" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                    Posko / Lokasi Tugas
                </label>
                <Textarea
                    id="alamat"
                    v-model="form.alamat"
                    rows="2"
                    placeholder="Contoh: Gedung Diskominfo Lt. 2, Ruang NOC Sentral"
                    class="w-full text-sm"
                />
            </div>

            <!-- URL FOTO / AVATAR -->
            <div>
                <label for="foto_profile" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                    URL Foto Profil (Opsional)
                </label>
                <InputText
                    id="foto_profile"
                    v-model="form.foto_profile"
                    placeholder="https://... atau biarkan kosong untuk avatar inisial"
                    class="w-full text-sm"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 pt-2">
                <Button label="Batal" icon="pi pi-times" severity="secondary" text @click="formDialog = false" />
                <Button
                    :label="isEditMode ? 'Simpan Perubahan' : 'Tambah Petugas'"
                    icon="pi pi-check"
                    severity="primary"
                    :loading="loading"
                    @click="savePetugas"
                />
            </div>
        </template>
    </Dialog>

    <!-- DIALOG DETAIL PETUGAS -->
    <Dialog
        v-model:visible="detailDialog"
        header="Kartu Identitas Petugas IT"
        :modal="true"
        class="w-full max-w-md"
    >
        <div v-if="selectedPetugas" class="space-y-5 pt-2">
            <!-- HEADER ID CARD -->
            <div class="p-6 rounded-2xl bg-gradient-to-br from-surface-800 to-surface-900 text-white relative overflow-hidden shadow-lg border border-surface-700">
                <div class="absolute -right-8 -bottom-8 w-36 h-36 rounded-full bg-primary/20 blur-xl"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-primary to-primary-400 flex items-center justify-center font-bold text-xl text-white shadow-inner flex-shrink-0">
                        <span v-if="!selectedPetugas.foto_profile">{{ getInitials(selectedPetugas.nama_petugas) }}</span>
                        <img v-else :src="selectedPetugas.foto_profile" alt="Avatar" class="w-full h-full rounded-full object-cover" />
                    </div>
                    <div>
                        <div class="text-lg font-bold">{{ selectedPetugas.nama_petugas }}</div>
                        <div class="text-xs text-surface-300 font-mono mt-0.5">
                            NIP: {{ selectedPetugas.nip || 'Non-PNS' }}
                        </div>
                        <div class="mt-2">
                            <Tag :value="selectedPetugas.bidang || 'IT Division'" severity="info" class="text-[10px] px-2" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- DETAIL INFO LIST -->
            <div class="space-y-3 bg-surface-50 dark:bg-surface-900/50 p-4 rounded-xl border border-surface-200 dark:border-surface-700 text-sm">
                <div class="flex justify-between items-center py-1 border-b border-surface-200 dark:border-surface-700/50">
                    <span class="text-surface-500 text-xs">Bidang Kerja</span>
                    <span class="font-medium text-surface-900 dark:text-surface-100">{{ selectedPetugas.bidang || '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-surface-200 dark:border-surface-700/50">
                    <span class="text-surface-500 text-xs">WhatsApp / HP</span>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs">{{ selectedPetugas.phone || '-' }}</span>
                        <a
                            v-if="selectedPetugas.phone"
                            :href="formatWaLink(selectedPetugas.phone)"
                            target="_blank"
                            class="text-emerald-500 hover:text-emerald-600 font-bold text-xs flex items-center gap-1"
                        >
                            <i class="pi pi-whatsapp"></i> Chat
                        </a>
                    </div>
                </div>
                <div class="flex justify-between items-start py-1">
                    <span class="text-surface-500 text-xs">Posko Penempatan</span>
                    <span class="font-medium text-surface-900 dark:text-surface-100 text-right max-w-xs">{{ selectedPetugas.alamat || '-' }}</span>
                </div>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end pt-2">
                <Button label="Tutup" severity="secondary" @click="detailDialog = false" />
            </div>
        </template>
    </Dialog>
</template>
