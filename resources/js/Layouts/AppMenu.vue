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
            { label: 'Maintenance/Perawatan', icon: 'pi pi-fw pi-sync', to: '/maintenance' },
            { label: 'Troubleshoot', icon: 'pi pi-fw pi-wrench', to: '/trouble' },
            { label: 'Network IP', icon: 'pi pi-fw pi-sitemap', to: '/network' },
        ]
    },
    {
        label: 'Reports',
        items: [
            { label: 'Maintenance', icon: 'pi pi-fw pi-book', to: '/report/maintenance' },
            { label: 'Masalah/Troubleshoot', icon: 'pi pi-fw pi-book', to: '/report/trouble' },
            { label: 'Buku Tamu', icon: 'pi pi-fw pi-book', to: '/report/guest' },
        ]
    },
    {
        label: 'Setting',
        icon: 'pi pi-fw pi-briefcase',
        items: [
            // {
            //     label: 'Data Admin',
            //     icon: 'pi pi-fw pi-verified',
            //     to: '/pages/crud'
            // },
            {
                label: 'Data Petugas',
                icon: 'pi pi-fw pi-users',
                to: '/petugas'
            },
            {
                label: 'Profile',
                icon: 'pi pi-fw pi-exclamation-circle',
                to: '/profile'
            },
            {
                label: 'Logout',
                icon: 'pi pi-fw pi-sign-out',
                to: '/logout',
                method: 'post',
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
