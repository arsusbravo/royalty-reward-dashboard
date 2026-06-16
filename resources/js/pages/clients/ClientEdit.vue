<template>
    <div class="max-w-2xl">
        <!-- Header -->
        <div class="mb-6 flex items-center gap-3">
            <router-link to="/clients" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </router-link>
            <h2 class="text-xl font-semibold text-gray-900">
                {{ isAdmin ? 'Edit Client' : 'Update Client Photo' }}
            </h2>
        </div>

        <!-- Loading -->
        <div v-if="fetchLoading" class="flex justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
        </div>

        <!-- Error loading -->
        <div v-else-if="fetchError" class="card text-center text-red-500">{{ fetchError }}</div>

        <form v-else @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Photo -->
            <div class="card space-y-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Face Photo</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ isAdmin ? 'Update the client photo (will re-enroll face)' : 'Capture a new photo for face recognition' }}
                    </p>
                </div>
                <PhotoCapture :current-photo-url="client?.photo_url" @captured="onPhotoCaptured" show-guide />
                <p v-if="errors.photo" class="text-xs text-red-500">{{ errors.photo[0] }}</p>
            </div>

            <!-- Personal info — admin only -->
            <div v-if="isAdmin" class="card space-y-4">
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
                        <textarea v-model="form.notes" rows="3" class="form-input resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Client app login password — admin only -->
            <div v-if="isAdmin" class="card space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Client App Login</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Leave blank to keep the existing password unchanged</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">New Password</label>
                        <input v-model="form.password" type="password" autocomplete="new-password" class="form-input" :class="{ 'border-red-500': errors.password }" placeholder="Min 8 characters" />
                        <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="form-label">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="form-input" />
                    </div>
                </div>
            </div>

            <!-- Client info summary for employees -->
            <div v-if="!isAdmin && client" class="card">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Client</h3>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-600">
                        {{ client.name[0].toUpperCase() }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ client.name }}</p>
                        <p class="text-sm text-gray-500">{{ client.email ?? 'No email' }}</p>
                    </div>
                    <div class="ml-auto">
                        <span :class="client.face_enrolled ? 'badge-enrolled' : 'badge-pending'">
                            {{ client.face_enrolled ? 'Face Enrolled' : 'Not Enrolled' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Enrollment status notice for admin -->
            <div v-if="isAdmin && client" class="card py-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Face Recognition Status</span>
                    <span :class="client.face_enrolled ? 'badge-enrolled' : 'badge-pending'">
                        {{ client.face_enrolled ? 'Enrolled' : 'Not Enrolled' }}
                    </span>
                </div>
            </div>

            <!-- Employee: must provide photo -->
            <div v-if="!isAdmin && !capturedPhoto" class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700 flex gap-2">
                <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Capture or upload a photo to proceed.
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="loading || (!isAdmin && !capturedPhoto)"
                    class="btn-primary"
                >
                    <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ loading ? 'Saving...' : (isAdmin ? 'Save Changes' : 'Update Photo') }}
                </button>
                <router-link to="/clients" class="btn-secondary">Cancel</router-link>
            </div>
        </form>
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';
import { authState } from '@/utils/auth.js';
import PhotoCapture from '@/components/PhotoCapture.vue';

export default {
    name: 'ClientEdit',
    components: { PhotoCapture },
    data() {
        return {
            client:       null,
            form:         {
                name: '', email: '', phone: '',
                date_of_birth: '', address: '', notes: '',
                password: '', password_confirmation: '',
            },
            capturedPhoto: null,
            errors:        {},
            globalError:   null,
            loading:       false,
            fetchLoading:  true,
            fetchError:    null,
            authState,
        };
    },
    computed: {
        isAdmin() {
            return this.authState.user?.role === 'admin';
        },
    },
    async created() {
        try {
            const data = await api.get(`/clients/${this.$route.params.id}`);
            this.client = data;
            this.form = {
                name:          data.name          ?? '',
                email:         data.email         ?? '',
                phone:         data.phone         ?? '',
                date_of_birth: data.date_of_birth ?? '',
                address:       data.address       ?? '',
                notes:         data.notes         ?? '',
            };
        } catch (e) {
            this.fetchError = e.message;
        } finally {
            this.fetchLoading = false;
        }
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

            try {
                if (this.isAdmin) {
                    Object.entries(this.form).forEach(([k, v]) => {
                        // Skip empty password fields so the backend treats them as unchanged
                        if ((k === 'password' || k === 'password_confirmation') && !v) return;
                        fd.append(k, v ?? '');
                    });
                    if (this.capturedPhoto) fd.append('photo', this.capturedPhoto);
                    await api.put(`/clients/${this.client.id}`, fd);
                } else {
                    fd.append('photo', this.capturedPhoto);
                    await api.patch(`/clients/${this.client.id}/photo`, fd);
                }
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
