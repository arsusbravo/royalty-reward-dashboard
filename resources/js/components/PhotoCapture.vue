<template>
    <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4">
        <!-- Preview -->
        <div v-if="mode === 'preview'" class="space-y-3">
            <div class="relative">
                <img :src="previewUrl" alt="Captured photo" class="w-full max-h-72 object-contain rounded-lg" />
                <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                    Photo ready
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="retake" class="btn-secondary flex-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Retake
                </button>
            </div>
        </div>

        <!-- Webcam live -->
        <div v-else-if="mode === 'webcam'" class="space-y-3">
            <div class="relative rounded-lg overflow-hidden bg-black">
                <video ref="video" autoplay playsinline class="w-full max-h-72 object-contain"></video>
                <canvas ref="canvas" class="hidden"></canvas>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="captureFrame" class="btn-primary flex-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Capture
                </button>
                <button type="button" @click="cancelWebcam" class="btn-secondary">
                    Cancel
                </button>
            </div>
        </div>

        <!-- Idle: choose method -->
        <div v-else class="space-y-4">
            <div v-if="currentPhotoUrl" class="mb-3">
                <p class="text-xs text-gray-500 mb-2">Current photo:</p>
                <img :src="currentPhotoUrl" alt="Current photo" class="h-24 w-24 object-cover rounded-lg border border-gray-200" />
            </div>
            <p class="text-sm text-gray-500 text-center">{{ currentPhotoUrl ? 'Update photo' : 'Add a photo' }}</p>
            <div class="flex gap-3 justify-center">
                <button type="button" @click="startWebcam" class="btn-secondary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Webcam
                </button>
                <label class="btn-secondary cursor-pointer">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Upload File
                    <input type="file" accept="image/*" class="hidden" @change="onFileSelected" />
                </label>
            </div>

            <!-- Webcam error -->
            <p v-if="webcamError" class="text-xs text-red-500 text-center">{{ webcamError }}</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PhotoCapture',
    emits: ['captured'],
    props: {
        currentPhotoUrl: {
            type: String,
            default: null,
        },
    },
    data() {
        return {
            mode:        'idle',
            stream:      null,
            previewUrl:  null,
            webcamError: null,
        };
    },
    methods: {
        async startWebcam() {
            this.webcamError = null;
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                    audio: false,
                });
                this.mode = 'webcam';
                await this.$nextTick();
                this.$refs.video.srcObject = this.stream;
            } catch (err) {
                this.webcamError = 'Could not access camera. Please allow camera permissions or upload a file instead.';
            }
        },

        captureFrame() {
            const video  = this.$refs.video;
            const canvas = this.$refs.canvas;
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            canvas.toBlob((blob) => {
                const file = new File([blob], 'webcam-capture.jpg', { type: 'image/jpeg' });
                this.previewUrl = URL.createObjectURL(blob);
                this.mode       = 'preview';
                this.$emit('captured', file);
                this.stopStream();
            }, 'image/jpeg', 0.92);
        },

        cancelWebcam() {
            this.stopStream();
            this.mode = 'idle';
        },

        stopStream() {
            this.stream?.getTracks().forEach(t => t.stop());
            this.stream = null;
        },

        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = URL.createObjectURL(file);
            this.mode       = 'preview';
            this.$emit('captured', file);
            event.target.value = '';
        },

        retake() {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
            this.mode       = 'idle';
            this.$emit('captured', null);
        },
    },

    beforeUnmount() {
        this.stopStream();
        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
    },
};
</script>
