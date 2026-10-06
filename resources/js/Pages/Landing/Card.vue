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
    cards: {
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
    subtitle: '',
    icon: 'pi pi-lock',
    is_active: true,
});

const iconPresets = [
    { label: 'Gembok (Keandalan)', icon: 'pi pi-lock' },
    { label: 'Perisai (Keamanan)', icon: 'pi pi-shield' },
    { label: 'Jam Pasir (Efisiensi)', icon: 'pi pi-hourglass' },
    { label: 'Mata (Transparansi)', icon: 'pi pi-eye' },
    { label: 'Petir (Inovasi)', icon: 'pi pi-bolt' },
    { label: 'Grup (Kolaborasi)', icon: 'pi pi-users' },
    { label: 'Server (Infrastruktur)', icon: 'pi pi-server' },
    { label: 'WiFi (Jaringan)', icon: 'pi pi-wifi' },
    { label: 'Database (Pusat Data)', icon: 'pi pi-database' },
    { label: 'Grafik (Kinerja)', icon: 'pi pi-chart-line' },
    { label: 'Globe (Internet)', icon: 'pi pi-globe' },
    { label: 'Bintang (Kualitas)', icon: 'pi pi-star' },
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
    form.icon = 'pi pi-lock';
    form.is_active = true;
    isEditMode.value = false;
    submitted.value = false;
    formDialog.value = true;
};

const editCard = (item) => {
    form.reset();
    form.clearErrors();
    form.id = item.id;
    form.title = item.title;
    form.subtitle = item.subtitle;
    form.icon = item.icon || 'pi pi-lock';
    form.is_active = Boolean(item.is_active);
    isEditMode.value = true;
    submitted.value = false;
    formDialog.value = true;
};

const saveCard = () => {
    submitted.value = true;

    if (!form.title || !form.subtitle || !form.icon) {
        toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Mohon isi semua field yang diwajibkan!', life: 3000 });
        return;
    }

    loading.value = true;
    if (isEditMode.value) {
        form.post(`/landing/card/update/${form.id}`, {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Kartu berhasil diperbarui', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memperbarui kartu', life: 3000 });
            }
        });
    } else {
        form.post('/landing/card/save', {
            onSuccess: () => {
                formDialog.value = false;
                loading.value = false;
                toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Kartu baru berhasil ditambahkan', life: 3000 });
            },
            onError: () => {
                loading.value = false;
                toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menyimpan kartu baru', life: 3000 });
            }
        });
    }
};

const toggleStatus = (item) => {
    router.post(`/landing/card/toggle/${item.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'info', summary: 'Status Diperbarui', detail: `Status kartu "${item.title}" berhasil diubah`, life: 2500 });
        }
    });
};

const deleteCard = (item) => {
    confirm.require({
        message: `Apakah Anda yakin ingin menghapus kartu "${item.title}"?`,
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
            router.post(`/landing/card/delete/${item.id}`, {}, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Kartu berhasil dihapus', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menghapus kartu', life: 3000 });
                }
            });
        }
    });
};
</script>

<template>
    <app-layout>
        <Head title="Manajemen Kartu Landing Page" />
        <Toast />
        <ConfirmDialog />

        <div class="space-y-6">
            <!-- PAGE HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold">
                        <i class="pi pi-box text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0 m-0">
                            Manajemen Kartu Komitmen & Nilai Landing
                        </h1>
                        <p class="text-surface-500 dark:text-surface-400 text-sm mt-1 mb-0">
                            Kelola kartu nilai pilar Diskominfo (Keandalan, Keamanan, Efisiensi, dll.) yang tampil di halaman Landing utama.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button label="Tambah Kartu" icon="pi pi-plus" severity="primary" @click="openNew" class="shadow-sm" />
                </div>
            </div>

            <!-- KPI STATS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Total Kartu Terdaftar</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">
                            {{ summary?.total ?? props.cards.length }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                        <i class="pi pi-th-large"></i>
                    </div>
                </div>

                <div class="bg-surface-0 dark:bg-surface-800 p-5 rounded-xl border border-surface-200 dark:border-surface-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-wider uppercase text-surface-500 dark:text-surface-400">Kartu Aktif Ditampilkan</span>
                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1 text-emerald-600 dark:text-emerald-400">
                            {{ summary?.active ?? 0 }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                        <i class="pi pi-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- LIVE PREVIEW SECTION -->
            <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-surface-200 dark:border-surface-700">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-eye text-primary text-lg"></i>
                        <h2 class="text-base font-bold text-surface-900 dark:text-surface-0 m-0">
                            Pratinjau Langsung Tampilan Landing Page
                        </h2>
                    </div>
                    <span class="text-xs text-surface-400 font-mono">Real-time Layout Preview</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="card in props.cards"
                        :key="card.id"
                        class="p-4 rounded-xl border transition-all duration-200 flex flex-col justify-between"
                        :class="card.is_active ? 'bg-surface-50 dark:bg-surface-900/50 border-surface-200 dark:border-surface-700 shadow-sm' : 'opacity-50 border-dashed border-surface-300 dark:border-surface-700'"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-lg font-bold">
                                    <i :class="card.icon"></i>
                                </div>
                                <Tag :value="card.is_active ? 'TAMPIL' : 'TERSEMBUNYI'" :severity="card.is_active ? 'success' : 'secondary'" class="text-[10px]" />
                            </div>
                            <h3 class="font-bold text-surface-900 dark:text-surface-0 text-base mb-1">{{ card.title }}</h3>
                            <p class="text-xs text-surface-600 dark:text-surface-300 line-clamp-3 leading-relaxed mb-0">{{ card.subtitle }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATATABLE MANAGEMENT -->
            <div class="bg-surface-0 dark:bg-surface-800 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-700 overflow-hidden">
                <DataTable
                    v-model:filters="filters"
                    :value="props.cards"
                    paginator
                    :rows="10"
                    :rowsPerPageOptions="[5, 10, 20]"
                    dataKey="id"
                    :globalFilterFields="['title', 'subtitle', 'icon']"
                    class="p-datatable-sm"
                    tableStyle="min-width: 50rem"
                >
                    <template #header>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-2">
                            <span class="text-base font-semibold text-surface-900 dark:text-surface-0">
                                Daftar Tabel Kartu ({{ props.cards.length }})
                            </span>
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-surface-400" />
                                <InputText v-model="filters.global.value" placeholder="Cari judul, konten, ikon..." class="w-60 text-sm" />
                            </IconField>
                        </div>
                    </template>

                    <template #empty>
                        <div class="text-center py-10 text-surface-500">
                            <i class="pi pi-box text-3xl mb-2 block text-surface-400"></i>
                            Belum ada data kartu landing.
                        </div>
                    </template>

                    <Column header="Ikon" style="width: 80px" class="text-center">
                        <template #body="{ data }">
                            <div class="w-9 h-9 rounded-lg bg-surface-100 dark:bg-surface-700 text-primary flex items-center justify-center text-lg mx-auto">
                                <i :class="data.icon"></i>
                            </div>
                        </template>
                    </Column>

                    <Column field="title" header="Judul Kartu" sortable style="min-width: 180px">
                        <template #body="{ data }">
                            <span class="font-bold text-surface-900 dark:text-surface-0 text-sm">{{ data.title }}</span>
                        </template>
                    </Column>

                    <Column field="subtitle" header="Deskripsi & Konten" style="min-width: 320px">
                        <template #body="{ data }">
                            <span class="text-xs text-surface-600 dark:text-surface-300 line-clamp-2">{{ data.subtitle }}</span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status Aktif" style="width: 130px" class="text-center">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center gap-2">
                                <ToggleSwitch :modelValue="Boolean(data.is_active)" @update:modelValue="toggleStatus(data)" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Aksi" style="width: 120px" class="text-center">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="warn" size="small" @click="editCard(data)" title="Edit" />
                                <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteCard(data)" title="Hapus" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- DIALOG TAMBAH / EDIT KARTU -->
        <Dialog
            v-model:visible="formDialog"
            :header="isEditMode ? 'Edit Kartu Landing' : 'Tambah Kartu Baru'"
            :modal="true"
            class="w-full max-w-lg"
            :breakpoints="{ '960px': '75vw', '640px': '95vw' }"
        >
            <div class="space-y-4 pt-2">
                <!-- JUDUL KARTU -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        Judul Kartu <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        id="title"
                        v-model="form.title"
                        placeholder="Contoh: Keandalan Jaringan"
                        class="w-full text-sm"
                        :class="{ 'p-invalid': submitted && !form.title }"
                    />
                </div>

                <!-- DESKRIPSI -->
                <div>
                    <label for="subtitle" class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        Deskripsi / Subtitle <span class="text-red-500">*</span>
                    </label>
                    <Textarea
                        id="subtitle"
                        v-model="form.subtitle"
                        rows="3"
                        placeholder="Tuliskan komitmen atau penjelasan nilai kartu..."
                        class="w-full text-sm"
                        :class="{ 'p-invalid': submitted && !form.subtitle }"
                    />
                </div>

                <!-- PILIH IKON -->
                <div>
                    <label class="block text-sm font-semibold text-surface-800 dark:text-surface-100 mb-1">
                        Ikon PrimeIcons <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-xl flex-shrink-0">
                            <i :class="form.icon"></i>
                        </div>
                        <InputText v-model="form.icon" placeholder="pi pi-lock" class="w-full text-sm font-mono" />
                    </div>
                    <div class="text-xs text-surface-500 mb-1">Pilihan Cepat Ikon:</div>
                    <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto p-1 bg-surface-50 dark:bg-surface-900 rounded-lg border border-surface-200 dark:border-surface-700">
                        <button
                            v-for="preset in iconPresets"
                            :key="preset.icon"
                            type="button"
                            @click="form.icon = preset.icon"
                            class="px-2.5 py-1 rounded text-xs flex items-center gap-1.5 transition-colors"
                            :class="form.icon === preset.icon ? 'bg-primary text-white font-semibold' : 'bg-surface-0 dark:bg-surface-800 text-surface-700 dark:text-surface-300 hover:bg-surface-200'"
                        >
                            <i :class="preset.icon"></i>
                            <span>{{ preset.label.split(' ')[0] }}</span>
                        </button>
                    </div>
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
                        :label="isEditMode ? 'Simpan Perubahan' : 'Tambah Kartu'"
                        icon="pi pi-check"
                        severity="primary"
                        :loading="loading"
                        @click="saveCard"
                    />
                </div>
            </template>
        </Dialog>
    </app-layout>
</template>
