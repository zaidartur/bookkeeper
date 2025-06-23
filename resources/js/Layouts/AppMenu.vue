<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'Home',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/dashboard' }]
    },
    {
        label: 'Input',
        items: [
            { label: 'Maintenance/Perawatan', icon: 'pi pi-fw pi-id-card', to: '/maintenance' },
            { label: 'Troubleshoot', icon: 'pi pi-fw pi-id-card', to: '/trouble' },
            { label: 'Network IP', icon: 'pi pi-fw pi-check-square', to: '/ip-address' },
        ]
    },
    {
        label: 'Reports',
        items: [
            { label: 'Laporan Masalah', icon: 'pi pi-fw pi-id-card', to: '/report/trouble' },
            { label: 'Laporan Maintenance', icon: 'pi pi-fw pi-id-card', to: '/report/maintenance' },
            { label: 'Laporan Buku Tamu', icon: 'pi pi-fw pi-check-square', to: '/report/guest' },
        ]
    },
    {
        label: 'Setting',
        icon: 'pi pi-fw pi-briefcase',
        to: '/pages',
        items: [
            {
                label: 'Data Admin',
                icon: 'pi pi-fw pi-pencil',
                to: '/pages/crud'
            },
            {
                label: 'Data Petugas',
                icon: 'pi pi-fw pi-user',
                to: '/pages/crud'
            },
            {
                label: 'Profile',
                icon: 'pi pi-fw pi-exclamation-circle',
                to: '/pages/notfound'
            },
            {
                label: 'Logout',
                icon: 'pi pi-fw pi-circle-off',
                to: '/pages/empty'
            }
        ]
    },
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
