<template>
    <div class="h-full">
        <!-- Loading screen -->
        <div v-if="authState.loading" class="flex h-full items-center justify-center bg-gray-50">
            <div class="flex flex-col items-center gap-3">
                <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
                <p class="text-sm text-gray-500">Loading...</p>
            </div>
        </div>

        <!-- Authenticated shell -->
        <template v-else-if="authState.user">
            <div class="flex h-full overflow-hidden">
                <Sidebar />
                <div class="flex flex-1 flex-col overflow-hidden">
                    <Navbar />
                    <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                        <router-view />
                    </main>
                </div>
            </div>
        </template>

        <!-- Unauthenticated: just the page (login) -->
        <router-view v-else />
    </div>
</template>

<script>
import { authState } from '@/utils/auth.js';
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';

export default {
    name: 'AppLayout',
    components: { Sidebar, Navbar },
    data() {
        return { authState };
    },
};
</script>
