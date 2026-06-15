import { createRouter, createWebHistory } from 'vue-router';
import { authState, fetchUser } from '@/utils/auth.js';

import LoginPage     from '@/pages/LoginPage.vue';
import DashboardPage from '@/pages/DashboardPage.vue';
import ClientsIndex  from '@/pages/clients/ClientsIndex.vue';
import ClientCreate  from '@/pages/clients/ClientCreate.vue';
import ClientEdit    from '@/pages/clients/ClientEdit.vue';
import UsersIndex    from '@/pages/users/UsersIndex.vue';
import UserCreate    from '@/pages/users/UserCreate.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { public: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: DashboardPage,
        meta: { requiresAuth: true, title: 'Dashboard' },
    },
    {
        path: '/clients',
        name: 'clients.index',
        component: ClientsIndex,
        meta: { requiresAuth: true, title: 'Clients' },
    },
    {
        path: '/clients/create',
        name: 'clients.create',
        component: ClientCreate,
        meta: { requiresAuth: true, title: 'New Client' },
    },
    {
        path: '/clients/:id/edit',
        name: 'clients.edit',
        component: ClientEdit,
        meta: { requiresAuth: true, title: 'Edit Client' },
    },
    {
        path: '/users',
        name: 'users.index',
        component: UsersIndex,
        meta: { requiresAuth: true, requiresAdmin: true, title: 'Users' },
    },
    {
        path: '/users/create',
        name: 'users.create',
        component: UserCreate,
        meta: { requiresAuth: true, requiresAdmin: true, title: 'New User' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

let initialAuthChecked = false;

router.beforeEach(async (to) => {
    if (!initialAuthChecked) {
        await fetchUser();
        initialAuthChecked = true;
    }

    const isAuthenticated = !!authState.user;

    if (to.meta.requiresAuth && !isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.public && isAuthenticated) {
        return { name: 'dashboard' };
    }

    if (to.meta.requiresAdmin && authState.user?.role !== 'admin') {
        return { name: 'dashboard' };
    }
});

export default router;
