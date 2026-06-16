<template>
    <div class="max-w-2xl space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Find Client</h2>
            <p class="mt-1 text-sm text-gray-500">Take a photo to identify a client and record a payment.</p>
        </div>

        <!-- Capture -->
        <div v-if="!searchResult" class="card space-y-4">
            <PhotoCapture @captured="onPhotoCaptured" auto-detect />

            <div v-if="searchError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ searchError }}
            </div>

            <button
                type="button"
                :disabled="!capturedPhoto || searching"
                @click="handleSearch"
                class="btn-primary w-full justify-center"
            >
                <svg v-if="searching" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ searching ? 'Searching...' : 'Search' }}
            </button>
        </div>

        <!-- No candidates at all -->
        <div v-else-if="candidates.length === 0" class="card text-center space-y-4 py-8">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="font-medium text-gray-900">No matching client found</p>
                <p class="text-sm text-gray-500">No registered face was close enough to identify.</p>
            </div>
            <button type="button" @click="resetSearch" class="btn-secondary">Try Again</button>
        </div>

        <!-- Confident match or manually selected → client detail + payment -->
        <div v-else-if="searchResult.matched || manuallySelected" class="space-y-6">
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        {{ manuallySelected ? 'Manually Selected' : 'Client Found' }}
                    </h3>
                    <span :class="matchBadgeClass(selected.similarity)">
                        {{ (selected.similarity * 100).toFixed(1) }}% match
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <img
                        v-if="selected.client.photo_url"
                        :src="selected.client.photo_url"
                        :alt="selected.client.name"
                        class="h-16 w-16 rounded-full object-cover flex-shrink-0"
                    />
                    <div v-else class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-lg font-medium text-gray-600">
                        {{ selected.client.name[0].toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ selected.client.name }}</p>
                        <p class="text-sm text-gray-500">{{ selected.client.email ?? 'No email' }}</p>
                        <p class="text-sm text-gray-500">{{ selected.client.phone ?? 'No phone' }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-4">
                    <div>
                        <p class="text-gray-400">Date of Birth</p>
                        <p class="text-gray-700">{{ selected.client.date_of_birth ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Address</p>
                        <p class="text-gray-700">{{ selected.client.address ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Picture Taken</p>
                        <p class="text-gray-700">{{ formatDateTime(searchResult.searched_at) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Face Status</p>
                        <span :class="selected.client.face_enrolled ? 'badge-enrolled' : 'badge-pending'">
                            {{ selected.client.face_enrolled ? 'Enrolled' : 'Not Enrolled' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Record payment -->
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

            <!-- Payment history -->
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

            <!-- Other possible matches (confident match only) -->
            <div v-if="searchResult.matched && otherCandidates.length > 0" class="card">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 uppercase tracking-wide">Other Possible Matches</h3>
                <div class="divide-y divide-gray-100">
                    <div v-for="c in otherCandidates" :key="c.client.id" class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="c.client.photo_url"
                                :src="c.client.photo_url"
                                :alt="c.client.name"
                                class="h-9 w-9 rounded-full object-cover flex-shrink-0"
                            />
                            <div v-else class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-medium text-gray-600">
                                {{ c.client.name[0].toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ c.client.name }}</p>
                                <p class="text-xs text-gray-400">{{ (c.similarity * 100).toFixed(1) }}% match</p>
                            </div>
                        </div>
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-700 font-medium" @click="selectCandidate(c.originalIndex)">
                            Select
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" @click="resetSearch" class="btn-secondary w-full justify-center">
                Search Another Client
            </button>
        </div>

        <!-- No confident match → show closest candidates for manual selection -->
        <div v-else class="space-y-6">
            <div class="card text-center space-y-2 py-6">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <p class="font-semibold text-gray-900">No confident match found</p>
                <p class="text-sm text-gray-500">Closest results are shown below — please verify identity before selecting.</p>
            </div>

            <div class="card">
                <h3 class="mb-3 text-sm font-semibold text-gray-700 uppercase tracking-wide">Closest Matches</h3>
                <div class="divide-y divide-gray-100">
                    <div v-for="(c, i) in closestCandidates" :key="c.client.id" class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="c.client.photo_url"
                                :src="c.client.photo_url"
                                :alt="c.client.name"
                                class="h-10 w-10 rounded-full object-cover flex-shrink-0"
                            />
                            <div v-else class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-medium text-gray-600">
                                {{ c.client.name[0].toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ c.client.name }}</p>
                                <p class="text-xs text-gray-400">{{ c.client.email ?? 'No email' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                {{ (c.similarity * 100).toFixed(1) }}%
                            </span>
                            <button type="button" class="text-sm text-blue-600 hover:text-blue-700 font-medium" @click="selectManually(i)">
                                Select
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" @click="resetSearch" class="btn-secondary w-full justify-center">
                Search Another Client
            </button>
        </div>
    </div>
</template>

<script>
import { api } from '@/utils/fetch.js';
import PhotoCapture from '@/components/PhotoCapture.vue';

export default {
    name: 'FaceSearchPage',
    components: { PhotoCapture },
    data() {
        return {
            capturedPhoto:    null,
            searching:        false,
            searchError:      null,
            searchResult:     null,
            selectedIndex:    0,
            manuallySelected: false,
            payments:         [],
            paymentForm:      { amount: '', notes: '' },
            recordingPayment: false,
            paymentError:     null,
            paymentSuccess:   null,
        };
    },
    computed: {
        candidates() {
            return this.searchResult?.candidates ?? [];
        },
        selected() {
            return this.candidates[this.selectedIndex] ?? null;
        },
        closestCandidates() {
            return this.candidates.slice(0, 3);
        },
        otherCandidates() {
            return this.candidates
                .map((c, originalIndex) => ({ ...c, originalIndex }))
                .filter((c) => c.originalIndex !== this.selectedIndex);
        },
    },
    methods: {
        onPhotoCaptured(file) {
            this.capturedPhoto = file;
        },
        async handleSearch() {
            if (!this.capturedPhoto) return;

            this.searching   = true;
            this.searchError = null;

            const fd = new FormData();
            fd.append('photo', this.capturedPhoto);

            try {
                const result       = await api.post('/face-search', fd);
                this.searchResult  = result;
                this.selectedIndex = 0;
                this.manuallySelected = false;
                if (this.searchResult.matched && this.selected) {
                    await this.fetchPayments(this.selected.client.id);
                }
            } catch (e) {
                this.searchError = e.message ?? 'Search failed. Please try again.';
            } finally {
                this.searching = false;
            }
        },
        async selectManually(closestIndex) {
            this.selectedIndex    = closestIndex;
            this.manuallySelected = true;
            this.paymentError     = null;
            this.paymentSuccess   = null;
            await this.fetchPayments(this.candidates[closestIndex].client.id);
        },
        async selectCandidate(index) {
            this.selectedIndex  = index;
            this.paymentError   = null;
            this.paymentSuccess = null;
            if (this.selected) {
                await this.fetchPayments(this.selected.client.id);
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
            if (!this.paymentForm.amount || !this.selected) return;

            this.recordingPayment = true;
            this.paymentError     = null;
            this.paymentSuccess   = null;

            try {
                const payment = await api.post(`/clients/${this.selected.client.id}/payments`, {
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
        resetSearch() {
            this.capturedPhoto    = null;
            this.searchResult     = null;
            this.selectedIndex    = 0;
            this.manuallySelected = false;
            this.searchError      = null;
            this.payments         = [];
            this.paymentForm      = { amount: '', notes: '' };
            this.paymentError     = null;
            this.paymentSuccess   = null;
        },
        matchBadgeClass(similarity) {
            const isConfident = similarity >= (this.searchResult?.threshold ?? 0.5);
            return isConfident
                ? 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800'
                : 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800';
        },
        formatDateTime(value) {
            return new Date(value.replace(' ', 'T')).toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
            });
        },
    },
};
</script>
