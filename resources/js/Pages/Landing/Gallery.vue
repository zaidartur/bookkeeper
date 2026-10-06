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
    galleries: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        default: () => ({ total: 0, active: 0, show_title_count: 0 })
    }
});

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const formDialog = ref(false);
const previewDialog = ref(false);
const previewImage = ref(null);
const isEditMode = ref(false);
const submitted = ref(false);
const loading = ref(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const form = useForm({
    id: null,
    title: '',
    image: '',
    show_title: true,
    is_active: true,
});

const sampleImages = [
    { title: 'Ruang Server & Rack Data Center', url: 'https://images.pexels.com/photos/17323801/pexels-photo-17323801.jpeg' },
    { title: 'Kabel Fiber Optik & Patch Panel', url: 'https://images.pexels.com/photos/159304/network-cable-ethernet-computer-159304.jpeg' },
    { title: 'Monitoring NOC Sentral', url: 'https://images.pexels.com/photos/1148820/pexels-photo-1148820.jpeg' },
    { title: 'Infrastruktur Jaringan Intranet', url: 'https://images.pexels.com/photos/13963756/pexels-photo-13963756.jpeg' },
    { title: 'Keamanan Siber & Firewall', url: 'https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg' },
    { title: 'Pemeliharaan Switch Core', url: 'https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg' },
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
    form.image = '';
    form.show_title = true;
    form.is_active = true;
    isEditMode.value = false;
    submitted.value = false;
    formDialog.value = true;
};

const editGallery = (item) => {
    form.reset();
    form.clearErrors();
    form.id = item.id;
    form.title = item.title;
    form.image = item.image;
    form.show_title = Boolean(item.show_title !== false && item.show_title !== 0);
    form.is_active = Boolean(item.is_active);
    isEditMode.value = true;
    submitted.value = false;
    formDialog.value = true;
};

const viewZoom = (item) => {
    previewImage.value = item;
    previewDialog.value = true;
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            form.image = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const saveGallery = () => {
    submitted.value = true;

    if (!form.title || !form.image) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Judul dan Foto wajib diisi!', life: 3000 });
        return;
    }

    loading.value = true;
    if (isEditMode.value) {
        form.post(`/landing/gallery/update/${form.id}`, {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Foto Galeri berhasil diperbarui', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memperbarui foto galeri', life: 3000 });
            }
        });
    } else {
        form.post('/landing/gallery/save', {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Foto Galeri baru berhasil ditambahkan', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menyimpan foto galeri', life: 3000 });
            }
        });
    }
};

const toggleStatus = (item) => {
    router.post(`/landing/gallery/toggle/${item.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'info', summary: 'Status Diperbarui', detail: `Status foto "${item.title}" berhasil diubah`, life: 2500 });
        }
    });
};

const toggleShowTitle = (item) => {
    router.post(`/landing/gallery/toggle-title/${item.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const statusTxt = item.show_title ? 'disembunyikan' : 'ditampilkan';
            toast.add({ severity: 'success', summary: 'Tampilan Judul', detail: `Judul "${item.title}" ${statusTxt} di landing`, life: 2500 });
        }
    });
};

const toggleAllTitles = (show) => {
    router.post('/landing/gallery/toggle-all-titles', { show_title: show }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'info',
                summary: 'Tampilan Judul Massal',
                detail: show ? 'Semua judul dokumentasi ditampilkan' : 'Semua judul dokumentasi disembunyikan',
                life: 3000
            });
        }
    });
};

const deleteGallery = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus foto "${item.title}"?`,
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
            router.post(`/landing/gallery/delete/${item.id}`, {}, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Foto Galeri berhasil dihapus', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menghapus foto galeri', life: 3000 });
                }
            });
        }
    });
};
</script>

<template>
    <app-layout>
        <Head title="Manajemen Galeri Landing Page" />
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- PAGE HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold">
                        <i class="pi pi-image text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0 m-0">
                            Manajemen Galeri & Dokumentasi Landing
                        </h1>
                        <p class="text-surface-500 dark:text-surface-400 text-sm mt-1 mb-0">
                            Kelola arsip foto dokumentasi ruang server, kabel fiber optik, dan visibilitas judul pada carousel landing page.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        label="Tampilkan Semua Judul"
                        icon="pi pi-eye"
                        severity="secondary"
                        size="small"
                        outlined
                        @click="toggleAllTitles(true)"
                        title="Tampilkan teks judul pada seluruh foto di landing carousel"
                    />
                    <Button
                        label="Sembunyikan Semua Judul"
                        icon="pi pi-eye-slash"
                        severity="secondary"
                        size="small"
                        outlined
                        @click="toggleAllTitles(false)"
                        title="Sembunyikan teks judul pada seluruh foto di landing carousel"
                    />
                    <Button label="Tambah Foto" icon="pi pi-plus" severity="primary" @click="openNew" class="shadow-sm" />
                </div>
            </div>

            <!-- KPI STATS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Total Foto Galeri</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                            {{ summary?.total ?? props.galleries.length }}
                        </div>
                        <span class="text-xs text-surface-400 mt-1 inline-block">Item Dokumentasi</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl">
                        <i class="pi pi-images"></i>
                    </div>
                </div>

                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Foto Aktif di Carousel</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1 text-emerald-600 dark:text-emerald-400">
                            {{ summary?.active ?? 0 }}
                        </div>
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 inline-block">Ditampilkan Pengunjung</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                        <i class="pi pi-check-circle"></i>
                    </div>
                </div>

                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Judul Tampil di Landing</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1 text-blue-600 dark:text-blue-400">
                            {{ summary?.show_title_count ?? 0 }}
                        </div>
                        <span class="text-xs text-blue-500 mt-1 inline-block">Dengan Keterangan Judul</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                        <i class="pi pi-tag"></i>
                    </div>
                </div>
            </div>

            <!-- VISUAL PHOTO SHOWCASE GRID -->
            <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-surface-200 dark:border-surface-700">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-th-large text-primary text-lg"></i>
                        <h2 class="text-base font-bold text-surface-900 dark:text-surface-0 m-0">
                            Showcase Pratinjau Galeri & Visibilitas Judul
                        </h2>
                    </div>
                    <span class="text-xs text-surface-400 font-mono">Klik ikon mata untuk beralih tampilan judul</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div
                        v-for="item in props.galleries"
                        :key="item.id"
                        class="group relative rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700 shadow-sm bg-surface-50 dark:bg-surface-900 flex flex-col justify-between transition-transform hover:-translate-y-1 duration-200"
                    >
                        <div class="h-44 w-full relative cursor-pointer overflow-hidden bg-surface-200 dark:bg-surface-800" @click="viewZoom(item)">
                            <img :src="item.image" :alt="item.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/40 opacity-80 group-hover:opacity-90 transition-opacity"></div>
                            
                            <!-- Badges Top Left -->
                            <div class="absolute top-2 left-2 z-10 flex flex-col gap-1 items-start">
                                <Tag :value="item.is_active ? 'AKTIF' : 'NONAKTIF'" :severity="item.is_active ? 'success' : 'secondary'" class="text-[9px]" />
                                <Tag
                                    :value="item.show_title ? 'JUDUL: TAMPIL' : 'JUDUL: SEMBUNYI'"
                                    :severity="item.show_title ? 'info' : 'secondary'"
                                    class="text-[8.5px]"
                                />
                            </div>

                            <!-- Zoom Button Top Right -->
                            <div class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="w-7 h-7 rounded-full bg-white/80 dark:bg-black/60 text-surface-900 dark:text-surface-0 flex items-center justify-center text-xs">
                                    <i class="pi pi-search-plus"></i>
                                </span>
                            </div>

                            <!-- Title Bottom Overlay -->
                            <div class="absolute bottom-2 left-2 right-2 z-10">
                                <div v-if="item.show_title" class="text-white text-xs font-semibold line-clamp-1 drop-shadow-sm flex items-center gap-1.5">
                                    <i class="pi pi-eye text-[11px] text-cyan-300"></i>
                                    <span>{{ item.title }}</span>
                                </div>
                                <div v-else class="text-surface-300 text-[11px] italic flex items-center gap-1.5 opacity-80">
                                    <i class="pi pi-eye-slash text-[11px] text-amber-300"></i>
                                    <span>(Judul Disembunyikan)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer Action -->
                        <div class="p-2.5 flex items-center justify-between bg-surface-0 dark:bg-surface-800 border-t border-surface-200 dark:border-surface-700">
                            <!-- Toggle Tayang Carousel -->
                            <div class="flex items-center gap-1.5">
                                <ToggleSwitch :modelValue="Boolean(item.is_active)" @update:modelValue="toggleStatus(item)" title="Aktif/Nonaktif di Carousel" />
                                <span class="text-[11px] text-surface-500">{{ item.is_active ? 'Aktif' : 'Off' }}</span>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="flex items-center gap-1">
                                <!-- Quick Toggle Title -->
                                <Button
                                    :icon="item.show_title ? 'pi pi-eye' : 'pi pi-eye-slash'"
                                    text
                                    rounded
                                    :severity="item.show_title ? 'info' : 'secondary'"
                                    size="small"
                                    @click="toggleShowTitle(item)"
                                    :title="item.show_title ? 'Sembunyikan teks judul di landing' : 'Tampilkan teks judul di landing'"
                                />
                                <Button icon="pi pi-pencil" text rounded severity="warn" size="small" @click="editGallery(item)" title="Edit" />
                                <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteGallery(item)" title="Hapus" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATATABLE MANAGEMENT -->
            <div class="bg-surface-0 dark:bg-surface-800 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700 overflow-hidden">
                <DataTable
                    v-model:filters="filters"
                    :value="props.galleries"
                    paginator
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                    dataKey="id"
                    :globalFilterFields="['title', 'image']"
                    class="p-datatable-sm"
                    tableStyle="min-width: 55rem"
                >
                    <template #header>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-2">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-semibold text-surface-900 dark:text-surface-0">
                                    Daftar Tabel Galeri & Pengaturan Judul ({{ props.galleries.length }})
                                </span>
                            </div>
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-surface-400" />
                                <InputText v-model="filters.global.value" placeholder="Cari judul dokumentasi..." class="w-64 text-sm" />
                            </IconField>
                        </div>
                    </template>

                    <template #empty>
                        <div class="text-center py-10 text-surface-500">
                            <i class="pi pi-image text-3xl mb-2 block text-surface-400"></i>
                            Belum ada foto dalam galeri.
                        </div>
                    </template>

                    <!-- Thumbnail -->
                    <Column header="Thumbnail" style="width: 90px" class="text-center">
                        <template #body="{ data }">
                            <div class="w-14 h-10 rounded-lg overflow-hidden border border-surface-200 dark:border-surface-700 cursor-pointer mx-auto" @click="viewZoom(data)">
                                <img :src="data.image" :alt="data.title" class="w-full h-full object-cover" />
                            </div>
                        </template>
                    </Column>

                    <!-- Judul Dokumentasi -->
                    <Column field="title" header="Judul Dokumentasi" sortable style="min-width: 220px">
                        <template #body="{ data }">
                            <div>
                                <span class="font-bold text-surface-900 dark:text-surface-0 text-sm block">{{ data.title }}</span>
                                <span class="text-[11px] text-surface-400" v-if="!data.show_title">
                                    <i class="pi pi-info-circle text-[10px] mr-0.5"></i> Judul tidak tampil di landing
                                </span>
                            </div>
                        </template>
                    </Column>

                    <!-- Opsi Tampilkan Judul -->
                    <Column field="show_title" header="Tampilkan Judul" style="width: 170px" class="text-center">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center gap-2">
                                <ToggleSwitch :modelValue="Boolean(data.show_title)" @update:modelValue="toggleShowTitle(data)" />
                                <Tag
                                    :value="data.show_title ? 'Tampil' : 'Sembunyi'"
                                    :severity="data.show_title ? 'info' : 'secondary'"
                                    class="text-[10px] px-1.5"
                                />
                            </div>
                        </template>
                    </Column>

                    <!-- Sumber Gambar -->
                    <Column field="image" header="Tautan Gambar" style="min-width: 220px">
                        <template #body="{ data }">
                            <span class="text-xs font-mono text-surface-500 truncate block max-w-xs">{{ data.image }}</span>
                        </template>
                    </Column>

                    <!-- Status Tayang Carousel -->
                    <Column field="is_active" header="Status Tayang" style="width: 130px" class="text-center">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center gap-2">
                                <ToggleSwitch :modelValue="Boolean(data.is_active)" @update:modelValue="toggleStatus(data)" />
                                <span class="text-xs text-surface-600 dark:text-surface-300 font-medium">{{ data.is_active ? 'Aktif' : 'Off' }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Aksi -->
                    <Column header="Aksi" style="width: 120px" class="text-center">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="warn" size="small" @click="editGallery(data)" title="Edit" />
                                <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteGallery(data)" title="Hapus" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- DIALOG TAMBAH / EDIT GALERI -->
        <Dialog
            v-model:visible="formDialog"
            :header="isEditMode ? 'Edit Foto Galeri' : 'Tambah Foto Galeri Baru'"
            :modal="true"
            class="w-full max-w-lg"
            :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
        >
            <div class="space-y-4 pt-2">
                <!-- JUDUL FOTO -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        Judul Dokumentasi <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="title"
                        v-model="form.title"
                        placeholder="Contoh: Ruang Server & Rack NOC Diskominfo"
                        class="w-full text-sm"
                        :class="{ 'p-invalid': submitted && !form.title }"
                    />
                </div>

                <!-- OPSI TAMPILKAN JUDUL DI LANDING -->
                <div class="p-3 rounded-xl bg-surface-50 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-semibold text-surface-800 dark:text-surface-100 block">
                            Tampilkan Judul Dokumentasi di Landing
                        </span>
                        <span class="text-xs text-surface-500 dark:text-surface-400">
                            Bila dinonaktifkan, foto akan tampil bersih di carousel landing tanpa teks judul.
                        </span>
                    </div>
                    <ToggleSwitch v-model="form.show_title" class="ml-4 flex-shrink-0" />
                </div>

                <!-- URL FOTO / BASE64 -->
                <div>
                    <label for="image" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        URL Gambar / Upload <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="image"
                        v-model="form.image"
                        placeholder="https://images.pexels.com/... atau pilih file di bawah"
                        class="w-full text-sm font-mono"
                        :class="{ 'p-invalid': submitted && !form.image }"
                    />
                    
                    <!-- File upload trigger -->
                    <div class="mt-2 flex items-center gap-2">
                        <label class="px-3 py-1.5 rounded-lg border border-surface-300 dark:border-surface-600 bg-surface-100 dark:bg-surface-700 text-surface-700 dark:text-surface-200 text-xs font-semibold cursor-pointer hover:bg-surface-200 transition-colors inline-flex items-center gap-1.5">
                            <i class="pi pi-upload"></i> Unggah Gambar Lokal
                            <input type="file" accept="image/*" class="hidden" @change="handleFileUpload" />
                        </label>
                    </div>
                </div>

                <!-- PRESET SAMPLES -->
                <div>
                    <div class="text-xs text-surface-500 mb-1">Contoh Foto Infrastruktur Cepat:</div>
                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto p-1 bg-surface-50 dark:bg-surface-900 rounded-lg border border-surface-200 dark:border-surface-700">
                        <button
                            v-for="sample in sampleImages"
                            :key="sample.url"
                            type="button"
                            @click="form.title = sample.title; form.image = sample.url"
                            class="px-2 py-1 rounded text-xs bg-surface-0 dark:bg-surface-800 text-surface-700 dark:text-surface-300 hover:bg-surface-200 transition-colors"
                        >
                            {{ sample.title }}
                        </button>
                    </div>
                </div>

                <!-- IMAGE PREVIEW -->
                <div v-if="form.image" class="pt-2">
                    <span class="text-xs font-semibold text-surface-500 block mb-1">Pratinjau Gambar:</span>
                    <div class="h-44 w-full rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700 bg-black/10 relative">
                        <img :src="form.image" alt="Pratinjau" class="w-full h-full object-cover" />
                        <div v-if="form.show_title && form.title" class="absolute bottom-2 left-2 right-2 p-1.5 rounded bg-black/60 text-white text-xs font-semibold backdrop-blur-sm truncate">
                            {{ form.title }}
                        </div>
                    </div>
                </div>

                <!-- STATUS AKTIF TAYANG -->
                <div class="flex items-center justify-between pt-2">
                    <span class="text-sm font-semibold text-surface-800 dark:text-surface-100">Tampilkan di Carousel Landing</span>
                    <ToggleSwitch v-model="form.is_active" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Batal" icon="pi pi-times" severity="secondary" text @click="formDialog = false" />
                    <Button
                        :label="isEditMode ? 'Simpan Perubahan' : 'Tambah Foto'"
                        icon="pi pi-check"
                        severity="primary"
                        :loading="loading"
                        @click="saveGallery"
                    />
                </div>
            </template>
        </Dialog>

        <!-- LIGHTBOX PREVIEW MODAL -->
        <Dialog
            v-model:visible="previewDialog"
            :header="previewImage?.title || 'Pratinjau Foto'"
            :modal="true"
            class="w-full max-w-3xl"
        >
            <div v-if="previewImage" class="p-2">
                <div class="rounded-xl overflow-hidden border border-surface-200 dark:border-surface-700 bg-black">
                    <img :src="previewImage.image" :alt="previewImage.title" class="w-full max-h-[70vh] object-contain mx-auto" />
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-surface-500">
                    <span class="font-bold text-surface-900 dark:text-surface-100">{{ previewImage.title }}</span>
                    <div class="flex items-center gap-2">
                        <Tag
                            :value="previewImage.show_title ? 'Judul Tampil' : 'Judul Sembunyi'"
                            :severity="previewImage.show_title ? 'info' : 'secondary'"
                        />
                        <Tag
                            :value="previewImage.is_active ? 'Tampil di Landing' : 'Nonaktif'"
                            :severity="previewImage.is_active ? 'success' : 'secondary'"
                        />
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end pt-2">
                    <Button label="Tutup" severity="secondary" @click="previewDialog = false" />
                </div>
            </template>
        </Dialog>
    </app-layout>
</template>
