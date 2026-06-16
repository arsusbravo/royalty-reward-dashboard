<template>
    <div class="max-w-2xl space-y-6">
        <div class="flex items-center justify-between">
            <router-link to="/clients" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Clients
            </router-link>
            <router-link v-if="client" :to="`/clients/${client.id}/edit`" class="btn-secondary text-xs px-3 py-1.5">
                {{ isAdmin ? 'Edit' : 'Update Photo' }}
            </router-link>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
        </div>

        <div v-else-if="error" class="card py-10 text-center text-sm text-red-500">{{ error }}</div>

        <template v-else-if="client">
            <!-- Client details -->
            <div class="card">
                <div class="flex items-center gap-4">
                    <img
                        v-if="client.photo_url"
                        :src="client.photo_url"
                        :alt="client.name"
                        class="h-16 w-16 rounded-full object-cover flex-shrink-0"
                    />
                    <div v-else class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-lg font-medium text-gray-600">
                        {{ client.name[0].toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ client.name }}</p>
                        <p class="text-sm text-gray-500">{{ client.email ?? 'No email' }}</p>
                        <p class="text-sm text-gray-500">{{ client.phone ?? 'No phone' }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-4">
                    <div>
                        <p class="text-gray-400">Date of Birth</p>
                        <p class="text-gray-700">{{ client.date_of_birth ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Address</p>
                        <p class="text-gray-700">{{ client.address ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Added by</p>
                        <p class="text-gray-700">{{ client.creator?.name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Face Status</p>
                        <span :class="client.face_enrolled ? 'badge-enrolled' : 'badge-pending'">
                            {{ client.face_enrolled ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                    </div>
                </div>

                <div v-if="client.notes" class="mt-4 border-t border-gray-100 pt-4 text-sm">
                    <p class="text-gray-400">Notes</p>
                    <p class="text-gray-700">{{ client.notes }}</p>
                </div>
            </div>

            <!-- Payment history -->
            <div class="card">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 uppercase tracking-wide">Payment History</h3>

                <div v-if="paymentsLoading" class="flex justify-center py-6">
                    <div class="h-6 w-6 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
                </div>

                <div v-else-if="payments.length === 0" class="py-6 text-center text-sm text-gray-400">
                    No payments recorded yet.
                </div>

                <template v-else>
                    <div class="divide-y divide-gray-100">
                        <div v-for="p in payments" :key="p.id" class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${{ p.amount.toFixed(2) }}</p>
                                <p class="text-xs text-gray-400">{{ p.notes || 'No notes' }} · by {{ p.recorded_by?.name ?? '—' }}</p>
                            </div>
                            <p class="text-xs text-gray-500">{{ formatDateTime(p.created_at) }}</p>
                        </div>
                    </div>

                    <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-gray-100 pt-4 mt-4">
                        <p class="text-sm text-gray-500">
                            Page {{ pagination.current_page }} of {{ pagination.last_page }}
                        </p>
                        <div class="flex gap-2">
                            <button
                                :disabled="pagination.current_page <= 1"
                                @click="goToPaymentsPage(pagination.current_page - 1)"
                                class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
                            >
                                Previous
                            </button>
                            <button
                                :disabled="pagination.current_page >= pagination.last_page"
                                @click="goToPaymentsPage(pagination.current_page + 1)"
                                class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';
import { authState } from '@/utils/auth.js';

export default {
    name: 'ClientShow',
    data() {
        return {
            client:         null,
            loading:        false,
            error:          null,
            payments:       [],
            paymentsLoading: false,
            paymentsPage:   1,
            pagination:     {},
            authState,
        };
    },
    computed: {
        isAdmin() {
            return this.authState.user?.role === 'admin';
        },
    },
    created() {
        this.fetchClient();
        this.fetchPayments();
    },
    methods: {
        async fetchClient() {
            this.loading = true;
            this.error   = null;
            try {
                this.client = await api.get(`/clients/${this.$route.params.id}`);
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
        async fetchPayments() {
            this.paymentsLoading = true;
            try {
                const data = await api.get(`/clients/${this.$route.params.id}/payments?page=${this.paymentsPage}`);
                this.payments   = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page:    data.last_page,
                    total:        data.total,
                };
            } catch {
                this.payments = [];
            } finally {
                this.paymentsLoading = false;
            }
        },
        goToPaymentsPage(page) {
            this.paymentsPage = page;
            this.fetchPayments();
        },
        formatDateTime(value) {
            return new Date(value.replace(' ', 'T')).toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
            });
        },
    },
};
</script>
