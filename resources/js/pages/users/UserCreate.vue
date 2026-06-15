<template>
    <div class="max-w-lg">
        <!-- Header -->
        <div class="mb-6 flex items-center gap-3">
            <router-link to="/users" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </router-link>
            <h2 class="text-xl font-semibold text-gray-900">New User</h2>
        </div>

        <form @submit.prevent="handleSubmit" class="card space-y-4">
            <div v-if="globalError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ globalError }}
            </div>

            <div>
                <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" required class="form-input" :class="{ 'border-red-500': errors.name }" />
                <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>

            <div>
                <label class="form-label">Email Address <span class="text-red-500">*</span></label>
                <input v-model="form.email" type="email" required class="form-input" :class="{ 'border-red-500': errors.email }" />
                <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
            </div>

            <div>
                <label class="form-label">Role <span class="text-red-500">*</span></label>
                <select v-model="form.role" required class="form-input">
                    <option value="">Select role...</option>
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                </select>
                <p v-if="errors.role" class="mt-1 text-xs text-red-500">{{ errors.role[0] }}</p>
            </div>

            <div>
                <label class="form-label">Password <span class="text-red-500">*</span></label>
                <input v-model="form.password" type="password" required minlength="8" class="form-input" :class="{ 'border-red-500': errors.password }" />
                <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
            </div>

            <div>
                <label class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                <input v-model="form.password_confirmation" type="password" required minlength="8" class="form-input" />
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" :disabled="loading" class="btn-primary">
                    <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ loading ? 'Creating...' : 'Create User' }}
                </button>
                <router-link to="/users" class="btn-secondary">Cancel</router-link>
            </div>
        </form>
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';

export default {
    name: 'UserCreate',
    data() {
        return {
            form: {
                name:                  '',
                email:                 '',
                role:                  '',
                password:              '',
                password_confirmation: '',
            },
            errors:      {},
            globalError: null,
            loading:     false,
        };
    },
    methods: {
        async handleSubmit() {
            this.loading     = true;
            this.errors      = {};
            this.globalError = null;
            try {
                await api.post('/users', this.form);
                this.$router.push('/users');
            } catch (e) {
                if (e.errors) {
                    this.errors = e.errors;
                } else {
                    this.globalError = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
