<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';

const form = useForm({
    nama: '',
    instansi: '',
    tanggal: '',
    masuk: '',
    keluar: '',
    keperluan: '',
});

const isSuccess = ref(false);
const errorMessage = ref('');

onMounted(() => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    
    // Default to current date YYYY-MM-DD
    form.tanggal = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    
    // Default to current time HH:mm
    form.masuk = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
    
    // Default estimated exit +1 hour
    const exitHour = (now.getHours() + 1) % 24;
    form.keluar = `${pad(exitHour)}:${pad(now.getMinutes())}`;
});

const submitCheckin = () => {
    if (!form.nama || !form.instansi || !form.keperluan) {
        errorMessage.value = 'Harap isi Nama, Instansi, dan Keperluan kunjungan Anda.';
        return;
    }

    errorMessage.value = '';
    form.post('/buku-tamu/save-form', {
        preserveScroll: true,
        onSuccess: () => {
            isSuccess.value = true;
        },
        onError: (err) => {
            errorMessage.value = 'Terjadi kesalahan saat menyimpan formulir. Silakan coba lagi.';
        },
    });
};

const resetForm = () => {
    form.reset('nama', 'instansi', 'keperluan');
    isSuccess.value = false;
    errorMessage.value = '';
};
</script>

<template>
    <Head title="Check-in Buku Tamu Mandiri" />

    <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-center items-center p-4">
        <!-- Logo / Title Header -->
        <div class="w-full max-w-md text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600/30 border border-indigo-500/40 text-indigo-400 mb-3 shadow-lg shadow-indigo-500/10">
                <i class="pi pi-qrcode text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Buku Tamu Digital</h1>
            <p class="text-sm text-slate-400 mt-1">Dinas Komunikasi & Informatika</p>
        </div>

        <!-- Success Confirmation View -->
        <div v-if="isSuccess" class="w-full max-w-md bg-slate-800/90 border border-emerald-500/40 rounded-2xl p-6 text-center shadow-2xl backdrop-blur-sm animate-fade-in">
            <div class="w-20 h-20 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4 border border-emerald-500/30">
                <i class="pi pi-check text-4xl"></i>
            </div>
            <h2 class="text-xl font-bold text-white mb-2">Check-in Berhasil!</h2>
            <p class="text-slate-300 text-sm mb-4 leading-relaxed">
                Terima kasih <strong>{{ form.nama }}</strong>, data kunjungan Anda dari <strong>{{ form.instansi }}</strong> telah tersimpan dalam sistem.
            </p>
            <div class="bg-slate-900/60 rounded-xl p-3 mb-6 text-xs text-slate-400 text-left space-y-1 border border-slate-700/50">
                <div>📅 <strong>Tanggal:</strong> {{ form.tanggal }}</div>
                <div>⏱️ <strong>Jam Masuk:</strong> {{ form.masuk }} WIB</div>
                <div>📝 <strong>Keperluan:</strong> {{ form.keperluan }}</div>
            </div>
            <Button label="Isi Formulir Tamu Lainnya" icon="pi pi-plus" class="w-full !bg-indigo-600 !border-indigo-600" @click="resetForm" />
        </div>

        <!-- Form Card -->
        <div v-else class="w-full max-w-md bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-2xl backdrop-blur-md">
            <div class="mb-5 pb-4 border-b border-slate-700/50 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">Formulir Kunjungan</h2>
                    <p class="text-xs text-slate-400">Silakan lengkapi data kedatangan Anda</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Mandiri</span>
            </div>

            <Message v-if="errorMessage" severity="error" class="mb-4 text-xs" :closable="false">
                {{ errorMessage }}
            </Message>

            <form @submit.prevent="submitCheckin" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <InputText v-model="form.nama" placeholder="Masukkan nama lengkap Anda" class="w-full !bg-slate-900/70 !border-slate-700 !text-white text-sm" required />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Instansi / Perusahaan / OPD <span class="text-red-400">*</span></label>
                    <InputText v-model="form.instansi" placeholder="Contoh: Bappeda, PT Telkom, dsb." class="w-full !bg-slate-900/70 !border-slate-700 !text-white text-sm" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jam Masuk</label>
                        <InputText v-model="form.masuk" type="time" class="w-full !bg-slate-900/70 !border-slate-700 !text-white text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jam Selesai (Estimasi)</label>
                        <InputText v-model="form.keluar" type="time" class="w-full !bg-slate-900/70 !border-slate-700 !text-white text-sm" required />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Maksud & Keperluan Kunjungan <span class="text-red-400">*</span></label>
                    <Textarea v-model="form.keperluan" rows="3" placeholder="Jelaskan secara ringkas maksud kunjungan Anda..." class="w-full !bg-slate-900/70 !border-slate-700 !text-white text-sm resize-none" required />
                </div>

                <div class="pt-2">
                    <Button type="submit" label="Check-in Sekarang" icon="pi pi-send" :loading="form.processing" class="w-full !bg-indigo-600 hover:!bg-indigo-500 !border-indigo-600 font-semibold py-2.5 shadow-lg shadow-indigo-600/20" />
                </div>
            </form>
        </div>

        <p class="text-xs text-slate-500 mt-6 text-center">
            Sistem Informasi Manajemen Jaringan & Tamu &bull; Bookkeeper
        </p>
    </div>
</template>
