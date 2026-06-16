<template>
    <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="toggleSidebar"
                class="-ml-1 flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 md:hidden"
                aria-label="Toggle navigation"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">{{ pageTitle }}</h1>
        </div>

        <div class="flex items-center gap-3">
            <button
                @click="handleLogout"
                :disabled="loggingOut"
                class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </div>
    </header>
</template>

<script>
import { logout } from '@/utils/auth.js';
import { toggleSidebar } from '@/utils/ui.js';

export default {
    name: 'Navbar',
    data() {
        return { loggingOut: false };
    },
    computed: {
        pageTitle() {
            return this.$route.meta?.title ?? 'Face Recognition Admin';
        },
    },
    methods: {
        toggleSidebar,
        async handleLogout() {
            this.loggingOut = true;
            await logout();
            this.$router.push('/login');
        },
    },
};
</script>
