<script setup>
import { ref, computed, onUnmounted } from 'vue'
import UploadZone from './ImageCompressor/UploadZone.vue'
import CompressionProgress from './ImageCompressor/CompressionProgress.vue'
import CompressionResult from './ImageCompressor/CompressionResult.vue'
import Notification from './ImageCompressor/Notification.vue'

const selectedFile = ref(null)
const originalPreview = ref('')
const compressionProgress = ref(0)
const isCompressing = ref(false)
const phase = ref('upload') // 'upload' | 'compressing' | 'result'
const result = ref(null)
const notification = ref(null)

let progressTimer = null

function formatBytes(bytes) {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return `${(bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`
}

function readAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = (e) => resolve(e.target.result)
        reader.onerror = reject
        reader.readAsDataURL(file)
    })
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]')
    return meta ? meta.getAttribute('content') : ''
}

async function handleFileSelect(file) {
    selectedFile.value = file
    originalPreview.value = await readAsDataUrl(file)
    phase.value = 'compressing'
    compressionProgress.value = 0

    // Friendly simulated progress so the UI feels responsive.
    let fake = 5
    progressTimer = setInterval(() => {
        fake = Math.min(90, fake + Math.random() * 9)
        compressionProgress.value = Math.round(fake)
    }, 250)

    compress()
}

async function compress() {
    isCompressing.value = true
    try {
        const formData = new FormData()
        formData.append('image', selectedFile.value)
        formData.append('_token', getCsrfToken())

        const response = await fetch('/compress', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
            },
        })

        const data = await response.json().catch(() => ({}))

        stopProgress(false)

        if (!response.ok) {
            throw new Error(data.error || 'Compression failed. Please try again.')
        }

        clearInterval(progressTimer)
        compressionProgress.value = 100

        const compressedBytes = data.compressed_size
        const compressedDataUrl = `data:image/${data.format};base64,${data.compressed_data}`
        const ext = data.format === 'jpeg' ? 'jpg' : data.format

        result.value = {
            originalSize: selectedFile.value.size,
            compressedSize: compressedBytes,
            compressionPercent: data.compression_percent,
            dimensions: data.dimensions,
            format: ext,
            originalPreview: originalPreview.value,
            compressedPreview: compressedDataUrl,
            dataUrl: compressedDataUrl,
            fileName: `compressed-image.${ext}`,
        }

        setTimeout(() => {
            phase.value = 'result'
            isCompressing.value = false
        }, 500)
    } catch (error) {
        stopProgress(false)
        isCompressing.value = false
        phase.value = 'upload'
        showNotification(error.message || 'Something went wrong.', 'error')
        selectedFile.value = null
        originalPreview.value = ''
    }
}

function stopProgress(finish = false) {
    if (progressTimer) {
        clearInterval(progressTimer)
        progressTimer = null
    }
    if (finish) compressionProgress.value = 100
}

function showNotification(message, type = 'success') {
    notification.value = { message, type }
    setTimeout(() => {
        notification.value = null
    }, 3500)
}

function reset() {
    stopProgress(false)
    selectedFile.value = null
    originalPreview.value = ''
    result.value = null
    isCompressing.value = false
    phase.value = 'upload'
}

onUnmounted(() => stopProgress(false))

const stepDescription = computed(() => {
    if (!selectedFile.value) return ''
    return `${formatBytes(selectedFile.value.size)} · ${selectedFile.value.name}`
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 via-white to-indigo-50/50 font-sans text-gray-900">
        <div class="mx-auto flex max-w-5xl flex-col px-6 py-12 sm:py-16">
            <!-- Hero -->
            <header class="mb-10 text-center">
                <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1 text-xs font-semibold uppercase tracking-wider text-indigo-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500" />
                    Free &amp; Private · Runs in your browser
                </p>
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                    <span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">Image Compressor</span>
                </h1>
                <p class="mt-4 text-xl font-semibold text-gray-800">Compress Images Without Losing Quality</p>
                <p class="mx-auto mt-3 max-w-2xl text-base text-gray-500">
                    Reduce your image size to under 1&nbsp;MB while keeping it sharp, clear, and visually close to the original.
                </p>
            </header>

            <!-- Main card -->
            <main class="mx-auto w-full max-w-3xl rounded-3xl border border-gray-200 bg-white p-6 shadow-xl shadow-gray-200/50 sm:p-10">
                <!-- Upload phase -->
                <section v-if="phase === 'upload'" class="space-y-6">
                    <UploadZone @file-selected="handleFileSelect" />
                    <div class="flex items-center justify-center gap-6 text-xs font-medium text-gray-400">
                        <span>JPG</span>
                        <span class="h-1 w-1 rounded-full bg-gray-300" />
                        <span>JPEG</span>
                        <span class="h-1 w-1 rounded-full bg-gray-300" />
                        <span>PNG</span>
                        <span class="h-1 w-1 rounded-full bg-gray-300" />
                        <span>WebP</span>
                    </div>
                </section>

                <!-- Compressing phase -->
                <section v-else-if="phase === 'compressing'" class="space-y-6">
                    <div class="overflow-hidden rounded-2xl border border-gray-200">
                        <img :src="originalPreview" alt="Your image" class="max-h-72 w-full object-contain" />
                    </div>
                    <p class="text-center text-xs font-medium text-gray-500">{{ stepDescription }}</p>
                    <CompressionProgress :progress="compressionProgress" message="Compressing your image…" />
                </section>

                <!-- Result phase -->
                <section v-else-if="phase === 'result' && result">
                    <CompressionResult
                        :result="result"
                        @compress-another="reset"
                        @downloaded="showNotification('Image downloaded successfully!', 'success')"
                    />
                </section>
            </main>

            <footer class="mt-10 text-center text-xs text-gray-400">
                Your original file is never modified — a new optimized copy is created for download.
            </footer>
        </div>

        <!-- Toast -->
        <div class="pointer-events-none fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
            <template v-if="notification">
                <Notification :message="notification.message" :type="notification.type" />
            </template>
        </div>
    </div>
</template>