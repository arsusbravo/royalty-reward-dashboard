<template>
    <aside class="flex w-64 flex-shrink-0 flex-col bg-[#1e2538]">
        <!-- Logo -->
        <div class="flex h-16 items-center gap-3 px-6 border-b border-white/10">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <span class="text-sm font-semibold text-white">Face Recog. Admin</span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 px-3 py-4">
            <router-link
                v-for="item in navItems"
                :key="item.to"
                :to="item.to"
                custom
                v-slot="{ isActive, navigate }"
            >
                <button
                    @click="navigate"
                    :class="[
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                        isActive
                            ? 'bg-[#2d3450] text-white border-l-2 border-blue-500 -ml-px pl-[11px]'
                            : 'text-slate-400 hover:bg-[#2d3450] hover:text-white'
                    ]"
                >
                    <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                    {{ item.name }}
                </button>
            </router-link>
        </nav>

        <!-- User info -->
        <div class="border-t border-white/10 px-4 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                    {{ userInitials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ authState.user?.name }}</p>
                    <p class="truncate text-xs text-slate-400 capitalize">{{ authState.user?.role }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>

<script>
import { authState } from '@/utils/auth.js';

// Inline SVG icon components (Options API, no extra deps)
const HomeIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>`,
};

const UsersIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`,
};

const UserGroupIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`,
};

export default {
    name: 'Sidebar',
    data() {
        return { authState };
    },
    computed: {
        isAdmin() {
            return this.authState.user?.role === 'admin';
        },
        navItems() {
            const items = [
                { name: 'Dashboard', to: '/',        icon: HomeIcon },
                { name: 'Clients',   to: '/clients', icon: UsersIcon },
            ];
            if (this.isAdmin) {
                items.push({ name: 'Users', to: '/users', icon: UserGroupIcon });
            }
            return items;
        },
        userInitials() {
            return (this.authState.user?.name ?? 'U')
                .split(' ')
                .map(w => w[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();
        },
    },
};
</script>
