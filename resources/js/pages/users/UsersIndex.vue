<template>
    <div class="space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Users</h2>
                <p class="text-sm text-gray-500">{{ pagination.total ?? 0 }} total users</p>
            </div>
            <router-link to="/users/create" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New User
            </router-link>
        </div>

        <!-- Table -->
        <div class="card p-0 overflow-hidden">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
            </div>

            <div v-else-if="error" class="py-10 text-center text-sm text-red-500">{{ error }}</div>

            <div v-else-if="users.length === 0" class="py-12 text-center text-sm text-gray-400">
                No users found.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#1e2538] text-xs font-semibold text-white">
                                        {{ userInitials(user.name) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 flex items-center gap-1.5">
                                            {{ user.name }}
                                            <span v-if="user.id === authState.user?.id" class="text-xs text-gray-400">(you)</span>
                                        </p>
                                        <p class="text-xs text-gray-400">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="user.role === 'admin' ? 'badge-admin' : 'badge-employee'" class="capitalize">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(user.created_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    v-if="user.id !== authState.user?.id"
                                    @click="confirmDelete(user)"
                                    class="btn-danger text-xs px-3 py-1.5"
                                >
                                    Delete
                                </button>
                                <span v-else class="text-xs text-gray-400">—</span>
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

        <!-- Delete confirm -->
        <ConfirmModal
            v-model="showDeleteModal"
            title="Delete User"
            :message="`Are you sure you want to delete ${userToDelete?.name}? Their clients will remain.`"
            confirm-text="Delete"
            :danger="true"
            @confirm="deleteUser"
        />
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';
import { authState } from '@/utils/auth.js';
import ConfirmModal from '@/components/ConfirmModal.vue';

export default {
    name: 'UsersIndex',
    components: { ConfirmModal },
    data() {
        return {
            users:           [],
            pagination:      {},
            page:            1,
            loading:         false,
            error:           null,
            authState,
            showDeleteModal: false,
            userToDelete:    null,
        };
    },
    created() {
        this.fetchUsers();
    },
    methods: {
        async fetchUsers() {
            this.loading = true;
            this.error   = null;
            try {
                const data = await api.get(`/users?page=${this.page}`);
                this.users      = data.data;
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

        goToPage(page) {
            this.page = page;
            this.fetchUsers();
        },

        confirmDelete(user) {
            this.userToDelete    = user;
            this.showDeleteModal = true;
        },

        async deleteUser() {
            try {
                await api.delete(`/users/${this.userToDelete.id}`);
                await this.fetchUsers();
            } catch (e) {
                alert(e.message);
            } finally {
                this.userToDelete = null;
            }
        },

        userInitials(name) {
            return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        },

        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
            });
        },
    },
};
</script>
