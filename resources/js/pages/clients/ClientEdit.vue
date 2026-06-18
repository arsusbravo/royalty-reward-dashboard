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

        <!-- Alert shown when auto-saved from Find Client with no match -->
        <div v-if="$route.query.from === 'search'" class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="font-semibold text-amber-800">Client not found — new client created</p>
                    <p class="mt-0.5 text-sm text-amber-700">No matching client was found in the system. A new client has been saved automatically with a default name. You can update their details below, or go back to find another client.</p>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-shrink-0">
                <router-link to="/find-client" class="btn-secondary text-sm text-center whitespace-nowrap">
                    Find Another Client
                </router-link>
                <button
                    v-if="isAdmin && client"
                    type="button"
                    :disabled="deleting"
                    @click="deleteAndFindClient"
                    class="btn-danger text-sm whitespace-nowrap"
                >
                    {{ deleting ? 'Deleting...' : 'Delete & Find Client' }}
                </button>
            </div>
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

        <!-- Record payment -->
        <div v-if="!fetchLoading && !fetchError && client" class="mt-6 space-y-6">
            <div class="card space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Record Payment</h3>

                <div v-if="paymentError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ paymentError }}
                </div>
                <div v-if="paymentSuccess" class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    {{ paymentSuccess }}
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Amount <span class="text-red-500">*</span></label>
                        <input v-model="paymentForm.amount" type="number" min="0.01" step="0.01" class="form-input" placeholder="0.00" />
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <input v-model="paymentForm.notes" type="text" class="form-input" placeholder="Optional" />
                    </div>
                </div>

                <button
                    type="button"
                    :disabled="!paymentForm.amount || recordingPayment"
                    @click="handleRecordPayment"
                    class="btn-primary w-full justify-center"
                >
                    {{ recordingPayment ? 'Recording...' : 'Record Payment' }}
                </button>
            </div>

            <div v-if="payments.length > 0" class="card">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 uppercase tracking-wide">Recent Payments</h3>
                <div class="divide-y divide-gray-100">
                    <div v-for="p in payments" :key="p.id" class="flex items-center justify-between py-2.5">
                        <div>
                            <p class="text-sm font-medium text-gray-900">${{ p.amount.toFixed(2) }}</p>
                            <p class="text-xs text-gray-400">{{ p.notes || 'No notes' }} · by {{ p.recorded_by?.name ?? '—' }}</p>
                        </div>
                        <p class="text-xs text-gray-500">{{ formatDateTime(p.created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>
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
            },
            capturedPhoto: null,
            errors:        {},
            globalError:   null,
            loading:       false,
            fetchLoading:  true,
            fetchError:    null,
            deleting:         false,
            payments:         [],
            paymentForm:      { amount: '', notes: '' },
            recordingPayment: false,
            paymentError:     null,
            paymentSuccess:   null,
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
            await this.fetchPayments(data.id);
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
        async deleteAndFindClient() {
            if (!this.client) return;
            this.deleting = true;
            try {
                await api.delete(`/clients/${this.client.id}`);
                this.$router.push('/find-client');
            } catch (e) {
                this.globalError = e.message ?? 'Could not delete client.';
                this.deleting = false;
            }
        },
        async fetchPayments(clientId) {
            try {
                const data = await api.get(`/clients/${clientId}/payments`);
                this.payments = data.data ?? [];
            } catch {
                this.payments = [];
            }
        },
        async handleRecordPayment() {
            if (!this.paymentForm.amount || !this.client) return;
            this.recordingPayment = true;
            this.paymentError     = null;
            this.paymentSuccess   = null;
            try {
                const payment = await api.post(`/clients/${this.client.id}/payments`, {
                    amount: this.paymentForm.amount,
                    notes:  this.paymentForm.notes || null,
                });
                this.payments.unshift(payment);
                this.paymentForm.amount = '';
                this.paymentForm.notes  = '';
                this.paymentSuccess     = 'Payment recorded successfully.';
            } catch (e) {
                this.paymentError = e.message ?? 'Could not record payment.';
            } finally {
                this.recordingPayment = false;
            }
        },
        formatDateTime(value) {
            return new Date(value.replace(' ', 'T')).toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
            });
        },
        async handleSubmit() {
            this.loading     = true;
            this.errors      = {};
            this.globalError = null;

            const fd = new FormData();

            try {
                if (this.isAdmin) {
                    Object.entries(this.form).forEach(([k, v]) => fd.append(k, v ?? ''));
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
