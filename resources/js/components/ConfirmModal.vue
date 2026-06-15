<template>
    <Teleport to="body">
        <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-start gap-4">
                    <div :class="['flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full', danger ? 'bg-red-100' : 'bg-blue-100']">
                        <svg v-if="danger" class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg v-else class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ message }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="close" class="btn-secondary">Cancel</button>
                    <button
                        type="button"
                        @click="confirm"
                        :class="danger ? 'btn-danger' : 'btn-primary'"
                    >
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
export default {
    name: 'ConfirmModal',
    props: {
        modelValue:  { type: Boolean, default: false },
        title:       { type: String, default: 'Confirm' },
        message:     { type: String, default: 'Are you sure?' },
        confirmText: { type: String, default: 'Confirm' },
        danger:      { type: Boolean, default: true },
    },
    emits: ['update:modelValue', 'confirm'],
    methods: {
        close()   { this.$emit('update:modelValue', false); },
        confirm() { this.$emit('confirm'); this.close(); },
    },
};
</script>
