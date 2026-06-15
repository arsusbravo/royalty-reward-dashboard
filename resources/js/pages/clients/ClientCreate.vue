<template>
    <div class="max-w-2xl">
        <!-- Header -->
        <div class="mb-6 flex items-center gap-3">
            <router-link to="/clients" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </router-link>
            <h2 class="text-xl font-semibold text-gray-900">New Client</h2>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Personal info -->
            <div class="card space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Personal Information</h3>

                <div v-if="globalError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ globalError }}
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" required class="form-input" :class="{ 'border-red-500': errors.name }" />
                        <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="form-label">Date of Birth</label>
                        <input v-model="form.date_of_birth" type="date" class="form-input" />
                        <p v-if="errors.date_of_birth" class="mt-1 text-xs text-red-500">{{ errors.date_of_birth[0] }}</p>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-input" :class="{ 'border-red-500': errors.email }" />
                        <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input v-model="form.phone" type="tel" class="form-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea v-model="form.address" rows="2" class="form-input resize-none"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="form-input resize-none" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Client app login (optional) -->
            <div class="card space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Client App Login</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Optional — set a password if this client will log in to the client app</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Password</label>
                        <input v-model="form.password" type="password" autocomplete="new-password" class="form-input" :class="{ 'border-red-500': errors.password }" placeholder="Min 8 characters" />
                        <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="form-label">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="form-input" />
                    </div>
                </div>
            </div>

            <!-- Photo -->
            <div class="card space-y-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Face Photo</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Used for face recognition enrollment</p>
                </div>
                <PhotoCapture @captured="onPhotoCaptured" />
                <p v-if="errors.photo" class="text-xs text-red-500">{{ errors.photo[0] }}</p>
            </div>

            <!-- Face enrollment notice -->
            <div v-if="capturedPhoto" class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700 flex gap-2">
                <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Photo will be enrolled in the face recognition system upon saving.
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button type="submit" :disabled="loading" class="btn-primary">
                    <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ loading ? 'Saving...' : 'Save Client' }}
                </button>
                <router-link to="/clients" class="btn-secondary">Cancel</router-link>
            </div>
        </form>
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';
import PhotoCapture from '@/components/PhotoCapture.vue';

export default {
    name: 'ClientCreate',
    components: { PhotoCapture },
    data() {
        return {
            form: {
                name:                 '',
                email:                '',
                phone:                '',
                date_of_birth:        '',
                address:              '',
                notes:                '',
                password:             '',
                password_confirmation: '',
            },
            capturedPhoto: null,
            errors:        {},
            globalError:   null,
            loading:       false,
        };
    },
    methods: {
        onPhotoCaptured(file) {
            this.capturedPhoto = file;
        },
        async handleSubmit() {
            this.loading     = true;
            this.errors      = {};
            this.globalError = null;

            const fd = new FormData();
            Object.entries(this.form).forEach(([k, v]) => {
                if (v !== '' && v !== null) fd.append(k, v);
            });
            if (this.capturedPhoto) fd.append('photo', this.capturedPhoto);

            try {
                await api.post('/clients', fd);
                this.$router.push('/clients');
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
