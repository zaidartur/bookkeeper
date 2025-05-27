import AppLayout from '@/Layouts/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/',
            component: AppLayout,
            children: [
                {
                    path: '/dashboard',
                    component: () => import('@/Pages/Dashboard.vue')
                },
                {
                    path: '/setting',
                    component: () => import('@/Pages/Setting.vue')
                },  
                {
                    path: '/profile',
                    component: () => import('@/Pages/Profile.vue')
                }
            ]
        },
    ]
});

export default router;