<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from '@primevue/core/api';
import ToggleSwitch from 'primevue/toggleswitch';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({ total: 0, active: 0 })
    }
});

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const formDialog = ref(false);
const isEditMode = ref(false);
const submitted = ref(false);
const loading = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const form = useForm({
    id: null,
    title: '',
    image: '/assets/images/landing/free.svg',
    content: '',
    is_active: true,
});

const illustrationPresets = [
    { label: 'Free / Server Rack', url: '/assets/images/landing/free.svg' },
    { label: 'Startup / Jaringan', url: '/assets/images/landing/startup.svg' },
    { label: 'Enterprise / NOC', url: '/assets/images/landing/enterprise.svg' },
];

onMounted(() => {
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
    form.image = '/assets/images/landing/free.svg';
    form.is_active = true;
    isEditMode.value = false;
    submitted.value = false;
    formDialog.value = true;
};

const editService = (item) => {
    form.reset();
    form.clearErrors();
    form.id = item.id;
    form.title = item.title;
    form.image = item.image || '/assets/images/landing/free.svg';
    form.content = item.content || '';
    form.is_active = Boolean(item.is_active);
    isEditMode.value = true;
    submitted.value = false;
    formDialog.value = true;
};

const getContentList = (content) => {
    if (!content) return [];
    return content.split('\n').map(c => c.trim()).filter(c => c.length > 0);
};

const saveService = () => {
    submitted.value = true;

    if (!form.title || !form.content) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Judul dan Poin Layanan wajib diisi!', life: 3000 });
        return;
    }

    loading.value = true;
    if (isEditMode.value) {
        form.post(`/landing/service/update/${form.id}`, {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Layanan berhasil diperbarui', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memperbarui layanan', life: 3000 });
            }
        });
    } else {
        form.post('/landing/service/save', {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Layanan baru berhasil ditambahkan', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menyimpan layanan baru', life: 3000 });
            }
        });
    }
};

const toggleStatus = (item) => {
    router.post(`/landing/service/toggle/${item.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'info', summary: 'Status Diperbarui', detail: `Status layanan "${item.title}" berhasil diubah`, life: 2500 });
        }
    });
};

const deleteService = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus layanan "${item.title}"?`,
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
            router.post(`/landing/service/delete/${item.id}`, {}, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Layanan berhasil dihapus', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menghapus layanan', life: 3000 });
                }
            });
        }
    });
};
</script>

<template>
    <app-layout>
        <Head title="Manajemen Layanan Landing Page" />
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- PAGE HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold">
                        <i class="pi pi-thumbs-up text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0 m-0">
                            Manajemen Katalog Layanan IT Landing
                        </h1>
                        <p class="text-surface-500 dark:text-surface-400 text-sm mt-1 mb-0">
                            Kelola kartu katalog layanan (Pusat Data, Infrastruktur Jaringan, Helpdesk SLA) yang tampil pada seksi Layanan landing page.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button label="Tambah Layanan" icon="pi pi-plus" severity="primary" @click="openNew" class="shadow-sm" />
                </div>
            </div>

            <!-- KPI STATS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Total Layanan Terdaftar</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                            {{ summary?.total ?? props.services.length }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xl">
                        <i class="pi pi-briefcase"></i>
                    </div>
                </div>

                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Layanan Aktif Ditampilkan</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1 text-emerald-600 dark:text-emerald-400">
                            {{ summary?.active ?? 0 }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                        <i class="pi pi-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- LIVE PREVIEW SECTION (CARDS MATCHING PRICING WIDGET) -->
            <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-surface-200 dark:border-surface-700">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-eye text-primary text-lg"></i>
                        <h2 class="text-base font-bold text-surface-900 dark:text-surface-0 m-0">
                            Pratinjau Langsung Kartu Layanan Landing
                        </h2>
                    </div>
                    <span class="text-xs text-surface-400 font-mono">Format Tampilan Asli Pengunjung</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="service in props.services"
                        :key="service.id"
                        class="p-6 rounded-2xl border flex flex-col justify-between transition-all duration-300"
                        :class="service.is_active ? 'bg-surface-0 dark:bg-surface-900 border-surface-200 dark:border-surface-700 shadow-sm hover:border-primary' : 'opacity-50 border-dashed border-surface-300 dark:border-surface-700 bg-surface-50'"
                    >
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <Tag :value="service.is_active ? 'AKTIF' : 'NONAKTIF'" :severity="service.is_active ? 'success' : 'secondary'" class="text-[10px]" />
                                <div class="flex items-center gap-1">
                                    <Button icon="pi pi-pencil" text rounded severity="warn" size="small" @click="editService(service)" title="Edit" />
                                    <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteService(service)" title="Hapus" />
                                </div>
                            </div>

                            <h3 class="text-center font-bold text-surface-900 dark:text-surface-0 text-xl mb-4">
                                {{ service.title }}
                            </h3>

                            <div class="my-4 flex justify-center h-28">
                                <img :src="service.image" :alt="service.title" class="h-full object-contain max-w-[200px]" />
                            </div>

                            <Divider class="my-4" />

                            <!-- List Points -->
                            <ul class="space-y-2.5 p-0 list-none my-4">
                                <li
                                    v-for="(point, idx) in getContentList(service.content)"
                                    :key="idx"
                                    class="flex items-start text-xs text-surface-700 dark:text-surface-300"
                                >
                                    <i class="pi pi-check-circle text-cyan-500 mr-2 mt-0.5 text-sm flex-shrink-0"></i>
                                    <span>{{ point }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-4 border-t border-surface-100 dark:border-surface-800 flex items-center justify-between text-xs text-surface-500">
                            <span>Status Tayang:</span>
                            <div class="flex items-center gap-1.5">
                                <ToggleSwitch :modelValue="Boolean(service.is_active)" @update:modelValue="toggleStatus(service)" />
                                <span class="font-semibold">{{ service.is_active ? 'Aktif' : 'Mati' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATATABLE MANAGEMENT -->
            <div class="bg-surface-0 dark:bg-surface-800 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700 overflow-hidden">
                <DataTable
                    v-model:filters="filters"
                    :value="props.services"
                    paginator
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                    dataKey="id"
                    :globalFilterFields="['title', 'content']"
                    class="p-datatable-sm"
                    tableStyle="min-width: 50rem"
                >
                    <template #header>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-2">
                            <span class="text-base font-semibold text-surface-900 dark:text-surface-0">
                                Daftar Tabel Layanan ({{ props.services.length }})
                            </span>
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-surface-400" />
                                <InputText v-model="filters.global.value" placeholder="Cari nama atau cakupan layanan..." class="w-64 text-sm" />
                            </IconField>
                        </div>
                    </template>

                    <template #empty>
                        <div class="text-center py-10 text-surface-500">
                            <i class="pi pi-briefcase text-3xl mb-2 block text-surface-400"></i>
                            Belum ada layanan yang tersimpan.
                        </div>
                    </template>

                    <Column header="Ilustrasi" style="width: 90px" class="text-center">
                        <template #body="{ data }">
                            <div class="w-12 h-10 rounded-lg p-1 bg-surface-100 dark:bg-surface-700 mx-auto flex items-center justify-center">
                                <img :src="data.image" :alt="data.title" class="max-h-full max-w-full object-contain" />
                            </div>
                        </template>
                    </Column>

                    <Column field="title" header="Nama Layanan" sortable style="min-width: 220px">
                        <template #body="{ data }">
                            <span class="font-bold text-surface-900 dark:text-surface-0 text-sm">{{ data.title }}</span>
                        </template>
                    </Column>

                    <Column field="content" header="Poin Cakupan Layanan" style="min-width: 320px">
                        <template #body="{ data }">
                            <div class="text-xs text-surface-600 dark:text-surface-300">
                                <span class="font-semibold text-primary mr-1">({{ getContentList(data.content).length }} poin):</span>
                                <span class="line-clamp-1 italic">{{ data.content.replace(/\n/g, ' • ') }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status Aktif" style="width: 130px" class="text-center">
                        <template #body="{ data }">
                            <ToggleSwitch :modelValue="Boolean(data.is_active)" @update:modelValue="toggleStatus(data)" />
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 120px" class="text-center">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="warn" size="small" @click="editService(data)" title="Edit" />
                                <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteService(data)" title="Hapus" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- DIALOG TAMBAH / EDIT LAYANAN -->
        <Dialog
            v-model:visible="formDialog"
            :header="isEditMode ? 'Edit Layanan IT' : 'Tambah Layanan IT Baru'"
            :modal="true"
            class="w-full max-w-lg"
            :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
        >
            <div class="space-y-4 pt-2">
                <!-- JUDUL LAYANAN -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        Nama / Judul Layanan <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="title"
                        v-model="form.title"
                        placeholder="Contoh: Infrastruktur Jaringan Terpadu OPD"
                        class="w-full text-sm"
                        :class="{ 'p-invalid': submitted && !form.title }"
                    />
                </div>

                <!-- ILUSTRASI / GAMBAR SVG -->
                <div>
                    <label for="image" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        URL Ilustrasi SVG / Gambar
                    </label>
                    <InputText
                        id="image"
                        v-model="form.image"
                        placeholder="/assets/images/landing/free.svg"
                        class="w-full text-sm font-mono"
                    />
                    <div class="text-xs text-surface-500 mt-1 mb-1">Pilihan Ilustrasi Landing:</div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="preset in illustrationPresets"
                            :key="preset.url"
                            type="button"
                            @click="form.image = preset.url"
                            class="px-2.5 py-1 rounded text-xs flex items-center gap-1.5 transition-colors border"
                            :class="form.image === preset.url ? 'bg-primary text-white font-semibold border-primary' : 'bg-surface-0 dark:bg-surface-800 text-surface-700 dark:text-surface-300 border-surface-200 hover:bg-surface-200'"
                        >
                            <span>{{ preset.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- KONTEN POIN-POIN LAYANAN -->
                <div>
                    <label for="content" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        Poin Cakupan Layanan <span class="text-red-500">*</span>
                    </label>
                    <Textarea
                        id="content"
                        v-model="form.content"
                        rows="5"
                        placeholder="Tulis setiap poin cakupan dalam baris baru (tekan Enter):
Contoh:
Keamanan Fisik & Logis
Stabilitas Jaringan 24/7
Manajemen Kapasitas Bandwidth"
                        class="w-full text-sm"
                        :class="{ 'p-invalid': submitted && !form.content }"
                    />
                    <small class="text-surface-400 text-xs">Setiap baris baru akan otomatis ditampilkan sebagai checklist poin pada kartu landing.</small>
                </div>

                <!-- STATUS AKTIF -->
                <div class="flex items-center justify-between pt-2">
                    <span class="text-sm font-semibold text-surface-800 dark:text-surface-100">Tampilkan di Halaman Landing</span>
                    <ToggleSwitch v-model="form.is_active" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Batal" icon="pi pi-times" severity="secondary" text @click="formDialog = false" />
                    <Button
                        :label="isEditMode ? 'Simpan Perubahan' : 'Tambah Layanan'"
                        icon="pi pi-check"
                        severity="primary"
                        :loading="loading"
                        @click="saveService"
                    />
                </div>
            </template>
        </Dialog>
    </app-layout>
</template>
