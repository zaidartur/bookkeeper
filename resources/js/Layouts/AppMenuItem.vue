<script setup>
import { useLayout } from '@/Layouts/composables/layout';
import { onBeforeMount, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const { layoutState, setActiveMenuItem, toggleMenu } = useLayout();
const page = usePage();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    index: {
        type: Number,
        default: 0
    },
    root: {
        type: Boolean,
        default: true
    },
    parentItemKey: {
        type: String,
        default: null
    }
});

const isActiveMenu = ref(false);
const itemKey = ref(null);

// Cek apakah item ini atau salah satu anaknya sedang aktif berdasarkan URL saat ini
const checkActiveRoute = (menuItem) => {
    if (!menuItem) return false;
    const currentUrl = page.url ? page.url.split('?')[0] : '';
    
    if (menuItem.to) {
        const targetUrl = menuItem.to.split('?')[0];
        if (targetUrl === '/') {
            return currentUrl === '/';
        }
        return currentUrl === targetUrl || currentUrl.startsWith(targetUrl + '/');
    }
    
    if (menuItem.items && Array.isArray(menuItem.items)) {
        return menuItem.items.some(child => checkActiveRoute(child));
    }
    
    return false;
};

const updateActiveState = () => {
    // 1. Jika rute ini atau rute anaknya sedang aktif di halaman saat ini, otomatis buka accordion submenu
    if (checkActiveRoute(props.item)) {
        isActiveMenu.value = true;
        return;
    }

    // 2. Jika merupakan root section, selalu tampilkan kontennya
    if (props.root) {
        isActiveMenu.value = true;
        return;
    }

    // 3. Ikuti activeMenuItem dari layout state
    const activeItem = layoutState.activeMenuItem;
    if (activeItem && typeof activeItem === 'string' && itemKey.value) {
        isActiveMenu.value = activeItem === itemKey.value || activeItem.startsWith(itemKey.value + '-');
    } else {
        isActiveMenu.value = false;
    }
};

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? `${props.parentItemKey}-${props.index}` : String(props.index);
    updateActiveState();
});

watch(
    () => layoutState.activeMenuItem,
    () => {
        updateActiveState();
    }
);

watch(
    () => page.url,
    () => {
        updateActiveState();
    }
);

function itemClick(event, item) {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    // Jika item adalah navigasi langsung (punya URL atau rute Inertia)
    if ((item.to || item.url) && !item.items) {
        if (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive) {
            toggleMenu();
        }
        setActiveMenuItem(itemKey.value);
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }

    // Jika item memiliki sub-menu (anak), toggle accordion
    if (item.items) {
        event.preventDefault();
        isActiveMenu.value = !isActiveMenu.value;
        const foundItemKey = isActiveMenu.value ? itemKey.value : props.parentItemKey;
        setActiveMenuItem(foundItemKey);
    }
}
</script>

<template>
    <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
        <!-- Label Header untuk Root Menu Item -->
        <div v-if="root && item.visible !== false" class="layout-menuitem-root-text">
            {{ item.label }}
        </div>

        <!-- Tombol Tautan Pembuka Sub-menu (Accordion Toggle) atau Tautan Eksternal -->
        <a
            v-if="(!item.to || item.items) && item.visible !== false"
            :href="item.url || 'javascript:void(0)'"
            @click="itemClick($event, item)"
            :class="[item.class, { 'active-route': checkActiveRoute(item) }]"
            :target="item.target"
            tabindex="0"
        >
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items"></i>
        </a>

        <!-- Tautan Navigasi Halaman Internal Inertia (Single Item / Submenu Child) -->
        <Link
            v-if="item.to && !item.items && item.visible !== false"
            :class="[item.class, { 'active-route': checkActiveRoute(item) }]"
            :href="item.to"
            :method="item.method ?? 'get'"
            @click="itemClick($event, item)"
            tabindex="0"
        >
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
        </Link>

        <!-- Daftar Submenu Bersarang (Transition Accordion) -->
        <Transition v-if="item.items && item.visible !== false" name="layout-submenu">
            <ul v-show="root ? true : isActiveMenu" class="layout-submenu">
                <app-menu-item
                    v-for="(child, i) in item.items"
                    :key="child.label || child.to || i"
                    :index="i"
                    :item="child"
                    :parentItemKey="itemKey"
                    :root="false"
                ></app-menu-item>
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped></style>
