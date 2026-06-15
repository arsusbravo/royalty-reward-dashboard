<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Clients</h2>
                <p class="text-sm text-gray-500">{{ pagination.total ?? 0 }} total clients</p>
            </div>
            <router-link to="/clients/create" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Client
            </router-link>
        </div>

        <!-- Search -->
        <div class="relative max-w-sm">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
            </svg>
            <input
                v-model="search"
                @input="onSearch"
                type="search"
                placeholder="Search clients..."
                class="form-input pl-10"
            />
        </div>

        <!-- Table -->
        <div class="card p-0 overflow-hidden">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
            </div>

            <div v-else-if="error" class="py-10 text-center text-sm text-red-500">{{ error }}</div>

            <div v-else-if="clients.length === 0" class="py-12 text-center text-sm text-gray-400">
                No clients found.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added by</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-for="client in clients" :key="client.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="client.photo_url"
                                        :src="client.photo_url"
                                        :alt="client.name"
                                        class="h-10 w-10 rounded-full object-cover flex-shrink-0 border border-gray-200"
                                    />
                                    <div v-else class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-600">
                                        {{ client.name[0].toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ client.name }}</p>
                                        <p v-if="client.date_of_birth" class="text-xs text-gray-400">DOB: {{ client.date_of_birth }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ client.email ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ client.phone ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="client.face_enrolled ? 'badge-enrolled' : 'badge-pending'">
                                    {{ client.face_enrolled ? 'Enrolled' : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ client.creator?.name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(client.created_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <router-link :to="`/clients/${client.id}/edit`" class="btn-secondary text-xs px-3 py-1.5">
                                        {{ isAdmin ? 'Edit' : 'Update Photo' }}
                                    </router-link>
                                    <button
                                        v-if="isAdmin"
                                        @click="confirmDelete(client)"
                                        class="btn-danger text-xs px-3 py-1.5"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <p class="text-sm text-gray-500">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                </p>
                <div class="flex gap-2">
                    <button
                        :disabled="pagination.current_page <= 1"
                        @click="goToPage(pagination.current_page - 1)"
                        class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
                    >
                        Previous
                    </button>
                    <button
                        :disabled="pagination.current_page >= pagination.last_page"
                        @click="goToPage(pagination.current_page + 1)"
                        class="btn-secondary text-xs px-3 py-1.5 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete confirm modal -->
        <ConfirmModal
            v-model="showDeleteModal"
            title="Delete Client"
            :message="`Are you sure you want to delete ${clientToDelete?.name}? This action cannot be undone.`"
            confirm-text="Delete"
            :danger="true"
            @confirm="deleteClient"
        />
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';
import { authState } from '@/utils/auth.js';
import ConfirmModal from '@/components/ConfirmModal.vue';

export default {
    name: 'ClientsIndex',
    components: { ConfirmModal },
    data() {
        return {
            clients:         [],
            pagination:      {},
            search:          '',
            page:            1,
            loading:         false,
            error:           null,
            authState,
            showDeleteModal: false,
            clientToDelete:  null,
            searchTimeout:   null,
        };
    },
    computed: {
        isAdmin() {
            return this.authState.user?.role === 'admin';
        },
    },
    created() {
        this.fetchClients();
    },
    methods: {
        async fetchClients() {
            this.loading = true;
            this.error   = null;
            try {
                const params = new URLSearchParams({ page: this.page });
                if (this.search) params.append('search', this.search);
                const data = await api.get(`/clients?${params}`);
                this.clients    = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page:    data.last_page,
                    total:        data.total,
                };
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        onSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.page = 1;
                this.fetchClients();
            }, 400);
        },

        goToPage(page) {
            this.page = page;
            this.fetchClients();
        },

        confirmDelete(client) {
            this.clientToDelete  = client;
            this.showDeleteModal = true;
        },

        async deleteClient() {
            try {
                await api.delete(`/clients/${this.clientToDelete.id}`);
                await this.fetchClients();
            } catch (e) {
                alert(e.message);
            } finally {
                this.clientToDelete = null;
            }
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
            });
        },
    },
};
</script>
