<script setup>
import { ref, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import moment from 'moment';
import 'moment/dist/locale/id';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

moment.locale('id');

const props = defineProps({
    networkMonitors: {
        type: Object,
        default: () => ({ total: 0, up: 0, down: 0, items: [] })
    },
    nocStats: {
        type: Object,
        default: () => ({ total_routers: 0, active_routers: 0, total_subnets: 0, total_petugas: 0 })
    }
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'Administrator');

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour >= 4 && hour < 11) return 'Selamat Pagi';
    if (hour >= 11 && hour < 15) return 'Selamat Siang';
    if (hour >= 15 && hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const todayFormatted = computed(() => {
    return moment().format('dddd, DD MMMM YYYY');
});

const showMonitorDialog = ref(false);

const getMonitorSeverity = (status) => {
    return status === 'UP' ? 'success' : 'danger';
};

const formatLatency = (ms) => {
    if (ms === null || ms === undefined) return '-';
    return `${ms} ms`;
};

const formatTime = (time) => {
    if (!time) return '-';
    return moment(time).format('DD MMM YYYY, HH:mm');
};
</script>

<template>
    <!-- Welcome Card: Border Accent, Adaptive to Light & Dark Mode, No solid color fill -->
    <div class="card border border-surface-200 dark:border-surface-700 border-l-4 border-l-primary-500 shadow-sm p-5 md:p-6 mb-0">
        <!-- Header Row: Greeting & System Health -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-primary-500 text-primary">
                        <i class="pi pi-shield text-base"></i>
                    </span>
                    <h2 class="text-xl md:text-2xl font-bold text-surface-900 dark:text-surface-0 m-0">
                        {{ greeting }}, <span class="text-primary">{{ userName }}</span>
                    </h2>
                </div>
                <p class="text-muted-color text-sm m-0 flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5">
                        <i class="pi pi-calendar text-primary"></i> {{ todayFormatted }}
                    </span>
                    <span class="text-surface-300 dark:text-surface-600">•</span>
                    <span>Pusat Operasional Jaringan & Inventaris (NOC)</span>
                </p>
            </div>

            <!-- Health Status & Host Dialog Trigger -->
            <div class="flex items-center gap-2 flex-wrap">
                <div 
                    v-if="(networkMonitors?.down || 0) > 0" 
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-rose-500 text-rose-600 dark:text-rose-400 text-xs font-semibold bg-transparent"
                >
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span>{{ networkMonitors.down }} Host Perlu Penanganan</span>
                </div>
                <div 
                    v-else 
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-emerald-500 text-emerald-600 dark:text-emerald-400 text-xs font-semibold bg-transparent"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Seluruh Host Normal</span>
                </div>

                <Button 
                    v-if="networkMonitors?.items && networkMonitors.items.length > 0"
                    icon="pi pi-server" 
                    label="Host Monitor" 
                    size="small" 
                    severity="secondary" 
                    outlined 
                    class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary"
                    @click="showMonitorDialog = true" 
                />
            </div>
        </div>

        <!-- Metric Highlights Row (Clean Border-Only Design) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 my-4">
            <!-- Box 1: Host Monitor -->
            <div class="border border-surface-200 dark:border-surface-700 rounded-xl p-3.5 hover:border-primary-500 transition-colors">
                <div class="flex items-center justify-between text-xs font-medium text-muted-color mb-1.5">
                    <span>Host Monitoring</span>
                    <i class="pi pi-desktop text-primary"></i>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="px-2 py-0.5 text-xs font-semibold rounded border border-emerald-500 text-emerald-600 dark:text-emerald-400">
                        {{ networkMonitors?.up || 0 }} UP
                    </span>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded border border-rose-500 text-rose-600 dark:text-rose-400">
                        {{ networkMonitors?.down || 0 }} DOWN
                    </span>
                    <span class="px-2 py-0.5 text-xs font-medium rounded border border-surface-300 dark:border-surface-600 text-muted-color">
                        {{ networkMonitors?.total || 0 }} Total
                    </span>
                </div>
            </div>

            <!-- Box 2: Router Gateway -->
            <div class="border border-surface-200 dark:border-surface-700 rounded-xl p-3.5 hover:border-cyan-500 transition-colors">
                <div class="flex items-center justify-between text-xs font-medium text-muted-color mb-1.5">
                    <span>Router Gateway</span>
                    <i class="pi pi-server text-cyan-500"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold text-surface-900 dark:text-surface-0">
                        {{ nocStats?.total_routers || 0 }}
                    </span>
                    <span class="text-xs text-muted-color">Router</span>
                    <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded border border-cyan-500 text-cyan-600 dark:text-cyan-400">
                        {{ nocStats?.active_routers || 0 }} Aktif
                    </span>
                </div>
            </div>

            <!-- Box 3: Subnet IPAM -->
            <div class="border border-surface-200 dark:border-surface-700 rounded-xl p-3.5 hover:border-indigo-500 transition-colors">
                <div class="flex items-center justify-between text-xs font-medium text-muted-color mb-1.5">
                    <span>Subnet & IPAM</span>
                    <i class="pi pi-sitemap text-indigo-500"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold text-surface-900 dark:text-surface-0">
                        {{ nocStats?.total_subnets || 0 }}
                    </span>
                    <span class="text-xs text-muted-color">Subnet Blok</span>
                    <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded border border-indigo-500 text-indigo-600 dark:text-indigo-400">
                        Termonitor
                    </span>
                </div>
            </div>

            <!-- Box 4: Petugas NOC -->
            <div class="border border-surface-200 dark:border-surface-700 rounded-xl p-3.5 hover:border-amber-500 transition-colors">
                <div class="flex items-center justify-between text-xs font-medium text-muted-color mb-1.5">
                    <span>Personil Petugas</span>
                    <i class="pi pi-users text-amber-500"></i>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold text-surface-900 dark:text-surface-0">
                        {{ nocStats?.total_petugas || 0 }}
                    </span>
                    <span class="text-xs text-muted-color">Petugas</span>
                    <span class="ml-auto px-2 py-0.5 text-xs font-semibold rounded border border-amber-500 text-amber-600 dark:text-amber-400">
                        Siap Tugas
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Row -->
        <div class="border-t border-surface-200 dark:border-surface-700 pt-3 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold text-muted-color uppercase tracking-wider flex items-center gap-1.5">
                <i class="pi pi-bolt text-primary"></i> Aksi Cepat:
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('trouble')">
                    <Button 
                        icon="pi pi-wrench" 
                        label="Tiket Gangguan" 
                        severity="secondary" 
                        outlined 
                        size="small" 
                        class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary" 
                    />
                </Link>
                <Link :href="route('ip')">
                    <Button 
                        icon="pi pi-sitemap" 
                        label="Tata Kelola IPAM" 
                        severity="secondary" 
                        outlined 
                        size="small" 
                        class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary" 
                    />
                </Link>
                <Link :href="route('ip.router.list')">
                    <Button 
                        icon="pi pi-server" 
                        label="Daftar Router" 
                        severity="secondary" 
                        outlined 
                        size="small" 
                        class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary" 
                    />
                </Link>
                <Link :href="route('petugas')">
                    <Button 
                        icon="pi pi-users" 
                        label="Data Petugas" 
                        severity="secondary" 
                        outlined 
                        size="small" 
                        class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary" 
                    />
                </Link>
                <Link :href="route('inventory.list')">
                    <Button 
                        icon="pi pi-box" 
                        label="Inventaris" 
                        severity="secondary" 
                        outlined 
                        size="small" 
                        class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary" 
                    />
                </Link>
                <Link :href="route('report.guest')">
                    <Button 
                        icon="pi pi-book" 
                        label="Buku Tamu" 
                        severity="secondary" 
                        outlined 
                        size="small" 
                        class="border-surface-300 dark:border-surface-600 text-xs hover:border-primary" 
                    />
                </Link>
            </div>
        </div>
    </div>

    <!-- Monitored Hosts Modal Dialog -->
    <Dialog 
        v-model:visible="showMonitorDialog" 
        header="Status Host Monitoring NOC" 
        :modal="true" 
        :style="{ width: '680px' }" 
        class="p-fluid"
    >
        <DataTable 
            :value="networkMonitors?.items || []" 
            size="small" 
            stripedRows 
            responsiveLayout="scroll"
        >
            <template #empty>
                <div class="text-center py-4 text-muted-color">Tidak ada host yang dipantau.</div>
            </template>
            <Column field="name" header="Nama Host" style="min-width: 140px">
                <template #body="{ data }">
                    <div class="font-medium text-surface-900 dark:text-surface-0">{{ data.name }}</div>
                    <div class="text-[11px] text-muted-color capitalize">{{ data.type }}</div>
                </template>
            </Column>
            <Column field="ip_address" header="IP Address" style="min-width: 130px">
                <template #body="{ data }">
                    <span class="font-mono text-xs px-2 py-0.5 rounded border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800">
                        {{ data.ip_address }}
                    </span>
                </template>
            </Column>
            <Column field="status" header="Status" style="min-width: 90px" class="text-center">
                <template #body="{ data }">
                    <Tag :value="data.status" :severity="getMonitorSeverity(data.status)" class="text-xs" />
                </template>
            </Column>
            <Column field="response_time_ms" header="Latensi" style="min-width: 90px" class="text-right">
                <template #body="{ data }">
                    <span class="text-xs font-mono" :class="data.response_time_ms ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-muted-color'">
                        {{ formatLatency(data.response_time_ms) }}
                    </span>
                </template>
            </Column>
            <Column field="last_checked_at" header="Pengecekan Terakhir" style="min-width: 150px">
                <template #body="{ data }">
                    <span class="text-xs text-muted-color">{{ formatTime(data.last_checked_at) }}</span>
                </template>
            </Column>
        </DataTable>
        <template #footer>
            <div class="flex justify-between items-center w-full">
                <span class="text-xs text-muted-color">Status diperbarui secara berkala oleh service monitoring.</span>
                <Button label="Tutup" severity="secondary" outlined size="small" @click="showMonitorDialog = false" />
            </div>
        </template>
    </Dialog>
</template>
