<template>
    <div class="space-y-6">
        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div v-for="stat in stats" :key="stat.label" class="card flex items-center gap-4">
                <div :class="['flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl', stat.bg]">
                    <component :is="stat.icon" class="h-6 w-6" :class="stat.iconColor" />
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ stat.label }}</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <span v-if="loading">—</span>
                        <span v-else>{{ stat.value }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Recent clients -->
        <div class="card">
            <h2 class="mb-4 text-base font-semibold text-gray-900">Recent Clients</h2>

            <div v-if="loading" class="flex justify-center py-8">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
            </div>

            <div v-else-if="error" class="py-4 text-center text-sm text-red-500">{{ error }}</div>

            <div v-else-if="recentClients.length === 0" class="py-8 text-center text-sm text-gray-400">
                No clients yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added by</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="client in recentClients" :key="client.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="client.photo_url"
                                        :src="client.photo_url"
                                        :alt="client.name"
                                        class="h-8 w-8 rounded-full object-cover flex-shrink-0"
                                    />
                                    <div v-else class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-600">
                                        {{ client.name[0].toUpperCase() }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ client.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ client.email ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="client.face_enrolled ? 'badge-enrolled' : 'badge-pending'">
                                    {{ client.face_enrolled ? 'Enrolled' : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ client.creator?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(client.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!loading && recentClients.length > 0" class="mt-4 text-right">
                <router-link to="/clients" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    View all clients →
                </router-link>
            </div>
        </div>

        <!-- Recent Verifications -->
        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Recent Verifications</h2>
                <span class="flex items-center gap-1.5 text-xs text-gray-400">
                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-400"></span>
                    Live · refreshes every 30s
                </span>
            </div>

            <div v-if="verificationsLoading && verifications.length === 0" class="flex justify-center py-8">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
            </div>

            <div v-else-if="verifications.length === 0" class="py-8 text-center text-sm text-gray-400">
                No verifications yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Similarity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="v in verifications" :key="v.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="v.client?.photo_url"
                                        :src="v.client.photo_url"
                                        :alt="v.client.name"
                                        class="h-8 w-8 rounded-full object-cover flex-shrink-0"
                                    />
                                    <div v-else class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-600">
                                        {{ v.client?.name?.[0]?.toUpperCase() ?? '?' }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ v.client?.name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="v.matched
                                    ? 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800'
                                    : 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800'">
                                    {{ v.matched ? '✓ Matched' : '✗ No Match' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ v.similarity != null ? (v.similarity * 100).toFixed(1) + '%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(v.verified_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { h } from 'vue';
import { api } from '@/utils/fetch.js';
import { authState } from '@/utils/auth.js';

const ClientsIcon = {
    inheritAttrs: false,
    render() {
        return h('svg', { ...this.$attrs, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' }),
        ]);
    },
};
const EnrolledIcon = {
    inheritAttrs: false,
    render() {
        return h('svg', { ...this.$attrs, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' }),
        ]);
    },
};
const UsersIcon = {
    inheritAttrs: false,
    render() {
        return h('svg', { ...this.$attrs, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' }),
        ]);
    },
};

export default {
    name: 'DashboardPage',
    data() {
        return {
            totalClients:          0,
            enrolledClients:       0,
            totalEmployees:        0,
            recentClients:         [],
            loading:               true,
            error:                 null,
            verifications:         [],
            verificationsLoading:  false,
            verificationPollTimer: null,
            authState,
        };
    },
    computed: {
        isAdmin() {
            return this.authState.user?.role === 'admin';
        },
        stats() {
            return [
                { label: 'Total Clients',    value: this.totalClients,    icon: ClientsIcon,  bg: 'bg-blue-50',   iconColor: 'text-blue-600' },
                { label: 'Face Enrolled',    value: this.enrolledClients, icon: EnrolledIcon, bg: 'bg-green-50',  iconColor: 'text-green-600' },
                { label: 'Total Employees',  value: this.totalEmployees,  icon: UsersIcon,    bg: 'bg-purple-50', iconColor: 'text-purple-600' },
            ];
        },
    },
    async created() {
        try {
            const data = await api.get('/dashboard');
            this.totalClients    = data.total_clients;
            this.enrolledClients = data.enrolled_clients;
            this.totalEmployees  = data.total_employees;
            this.recentClients   = data.recent_clients;
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },
    mounted() {
        if (this.isAdmin) {
            this.fetchVerifications();
            this.verificationPollTimer = setInterval(this.fetchVerifications, 30000);
        }
    },
    beforeUnmount() {
        clearInterval(this.verificationPollTimer);
    },
    methods: {
        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
            });
        },
        async fetchVerifications() {
            this.verificationsLoading = true;
            try {
                const data = await api.get('/verifications?per_page=5');
                this.verifications = data.data ?? [];
            } catch {
                // silently ignore polling errors
            } finally {
                this.verificationsLoading = false;
            }
        },
    },
};
</script>
