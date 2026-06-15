<template>
    <div class="flex min-h-full items-center justify-center bg-[#1e2538] px-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">Face Recognition</h1>
                <p class="mt-1 text-sm text-slate-400">Admin Dashboard</p>
            </div>

            <!-- Card -->
            <div class="card">
                <h2 class="mb-6 text-center text-lg font-semibold text-gray-900">Sign in to your account</h2>

                <div v-if="error" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ error }}
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label for="email" class="form-label">Email address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="form-input"
                            placeholder="admin@example.com"
                        />
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="form-input"
                            placeholder="••••••••"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="remember" class="text-sm text-gray-600">Remember me</label>
                    </div>

                    <button type="submit" :disabled="loading" class="btn-primary w-full justify-center py-2.5">
                        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ loading ? 'Signing in...' : 'Sign in' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { login } from '@/utils/auth.js';

export default {
    name: 'LoginPage',
    data() {
        return {
            form: { email: '', password: '', remember: false },
            error: null,
            loading: false,
        };
    },
    methods: {
        async handleSubmit() {
            this.loading = true;
            this.error   = null;
            try {
                await login(this.form.email, this.form.password, this.form.remember);
                // Full reload so Blade re-renders a fresh CSRF token after session regeneration
                window.location.href = '/';
            } catch (e) {
                this.error = e.message ?? 'Login failed. Please try again.';
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
