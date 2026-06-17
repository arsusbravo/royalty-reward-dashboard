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
                <!-- Face-positioning guide overlay -->
                <div v-if="showGuide" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="h-[88%] w-auto aspect-[3/4] rounded-[50%] border-2 border-dashed border-white/80"></div>
                </div>

                <button
                    type="button"
                    @click="switchCamera"
                    class="absolute top-2 right-2 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70"
                    aria-label="Switch camera"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
            <p v-if="autoDetect" class="text-xs text-center text-gray-500">
                {{ faceDetected ? 'Face detected — capturing...' : (frameBlurry ? 'Hold still...' : 'Looking for a face...') }}
            </p>
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
                    Camera
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
import { markRaw } from 'vue';

// MediaPipe's WASM runtime writes its own INFO/WARNING lines straight to the
// console during graph init/inference/teardown. Mute the console for the
// duration of these calls only — our own error logs happen outside this window.
const consoleMethods = ['log', 'info', 'warn', 'error', 'debug'];

const BLUR_THRESHOLD = 60;

function computeBlurScore(video, boundingBox) {
    const W = 160, H = 120;
    const canvas = document.createElement('canvas');
    canvas.width = W; canvas.height = H;
    const ctx = canvas.getContext('2d');
    if (boundingBox) {
        // Crop to the detected face region so background texture doesn't
        // inflate the sharpness score and let blurry face frames slip through.
        ctx.drawImage(
            video,
            boundingBox.originX, boundingBox.originY,
            boundingBox.width,   boundingBox.height,
            0, 0, W, H,
        );
    } else {
        ctx.drawImage(video, 0, 0, W, H);
    }
    const { data } = ctx.getImageData(0, 0, W, H);
    const gray = new Float32Array(W * H);
    for (let i = 0; i < W * H; i++) {
        gray[i] = (data[i * 4] + data[i * 4 + 1] + data[i * 4 + 2]) / 3;
    }
    let sum = 0, sumSq = 0, n = 0;
    for (let y = 1; y < H - 1; y++) {
        for (let x = 1; x < W - 1; x++) {
            const v = Math.abs(
                -4 * gray[y * W + x]
                + gray[(y - 1) * W + x] + gray[(y + 1) * W + x]
                + gray[y * W + (x - 1)] + gray[y * W + (x + 1)]
            );
            sum += v; sumSq += v * v; n++;
        }
    }
    const mean = sum / n;
    return sumSq / n - mean * mean;
}

function withSilencedConsole(fn) {
    const original = {};
    for (const method of consoleMethods) {
        original[method] = console[method];
        console[method] = () => {};
    }
    try {
        return fn();
    } finally {
        for (const method of consoleMethods) {
            console[method] = original[method];
        }
    }
}

async function withSilencedConsoleAsync(fn) {
    const original = {};
    for (const method of consoleMethods) {
        original[method] = console[method];
        console[method] = () => {};
    }
    try {
        return await fn();
    } finally {
        for (const method of consoleMethods) {
            console[method] = original[method];
        }
    }
}

export default {
    name: 'PhotoCapture',
    emits: ['captured'],
    props: {
        currentPhotoUrl: {
            type: String,
            default: null,
        },
        autoDetect: {
            type: Boolean,
            default: false,
        },
        showGuide: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            mode:         'idle',
            stream:       null,
            previewUrl:   null,
            webcamError:  null,
            faceDetected: false,
            frameBlurry:  false,
            detector:     null,
            detectTimer:  null,
            facingMode:   'user',
        };
    },
    methods: {
        async startWebcam() {
            this.webcamError = null;
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: this.facingMode },
                    audio: false,
                });
                this.mode = 'webcam';
                await this.$nextTick();
                this.$refs.video.srcObject = this.stream;

                if (this.autoDetect) {
                    await this.startFaceDetection();
                }
            } catch (err) {
                this.webcamError = 'Could not access camera. Please allow camera permissions or upload a file instead.';
            }
        },

        async startFaceDetection() {
            this.faceDetected = false;
            this.frameBlurry  = false;

            try {
                const { FaceDetector, FilesetResolver } = await import('@mediapipe/tasks-vision');

                // CDN version below must match the installed npm package version
                // (package.json) — mismatched JS/WASM builds break runningMode handling.
                const vision = await FilesetResolver.forVisionTasks(
                    'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.35/wasm'
                );

                // markRaw: the detector is backed by a WASM/embind binding that's
                // sensitive to object identity. Vue's reactive Proxy around it
                // breaks the internal runningMode/state tracking.
                this.detector = markRaw(await withSilencedConsoleAsync(() => FaceDetector.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite',
                        delegate: 'CPU',
                    },
                    runningMode: 'VIDEO',
                })));

                this.detectTimer = setInterval(() => {
                    const video = this.$refs.video;
                    if (!video || video.readyState < 2 || !this.detector) return;

                    try {
                        const result = withSilencedConsole(() => this.detector.detectForVideo(video, performance.now()));
                        if (result.detections.length > 0) {
                            const box = result.detections[0].boundingBox;
                            if (computeBlurScore(video, box) >= BLUR_THRESHOLD) {
                                this.frameBlurry  = false;
                                this.faceDetected = true;
                                this.captureFrame();
                            } else {
                                this.frameBlurry = true;
                            }
                        } else {
                            this.frameBlurry = false;
                        }
                    } catch (err) {
                        console.error('Face detection tick failed:', err);
                        this.stopFaceDetection();
                    }
                }, 100);
            } catch (err) {
                // Face detection failed to load — manual Capture button still works.
                console.error('Face detector setup failed:', err);
                this.stopFaceDetection();
            }
        },

        async switchCamera() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.stopFaceDetection();
            this.stopStream();
            await this.startWebcam();
        },

        stopFaceDetection() {
            clearInterval(this.detectTimer);
            this.detectTimer = null;
            if (this.detector) {
                withSilencedConsole(() => this.detector.close());
            }
            this.detector = null;
        },

        captureFrame() {
            this.stopFaceDetection();

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
            this.stopFaceDetection();
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
        restart() {
            this.stopFaceDetection();
            this.stopStream();
            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
            }
            this.faceDetected = false;
            this.frameBlurry  = false;
            this.$emit('captured', null);
            this.startWebcam();
        },
    },

    beforeUnmount() {
        this.stopFaceDetection();
        this.stopStream();
        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
    },
};
</script>
