<script setup lang="typescript">
import { ref, computed, watch } from 'vue'
import { useInertia } from '@inertiajs/vue3'
import UploadZone from '@/Pages/ImageCompressor/UploadZone.vue'
import ImagePreview from '@/Pages/ImageCompressor/ImagePreview.vue'
import CompressionProgress from '@/Pages/ImageCompressor/CompressionProgress.vue'
import CompressionResult from '@/Pages/ImageCompressor/CompressionResult.vue'
import DownloadButton from '@/Pages/ImageCompressor/DownloadButton.vue'
import Notification from '@/Pages/ImageCompressor/Notification.vue'

const { visit } = useInertia()

const selectedFile = ref(null)
const originalImage = ref(null)
const compressionProgress = ref(0)
const isCompressing = ref(false)
const showResult = ref(false)
const result = ref({
    compressed_path: '',
    original_size: 0,
    compressed_size: 0,
    compression_percent: 0,
    dimensions: '',
    format: 'jpg',
    showComparison: false,
})

const MAX_FILE_SIZE = 20 * 1024 * 1024 // 20MB

// Show result from compression
watch(showResult, (value) => {
    if (value) {
        // Result is passed via Inertia or we set it manually
    }
})

const handleFileSelect = (file: File) => {
    if (!file) return

    // Validate file type
    const acceptedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
    const isValidType = acceptedTypes.includes(file.type)

    if (!isValidType) {
        alert('Unsupported file type. Please upload JPG, JPEG, PNG, or WebP.')
        return
    }

    // Validate file size
    if (file.size > MAX_FILE_SIZE) {
        alert('File is too large. Maximum size is 20MB.')
        return
    }

    selectedFile.value = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e: any) => {
        originalImage.value = e.target.result
    }
    reader.readAsDataURL(file)

    // Start compression automatically
    startCompression()
}

const startCompression = async () => {
    if (!selectedFile.value || isCompressing.value) return

    isCompressing.value = true
    compressionProgress.value = 0

    try {
        const formData = new FormData()
        formData.append('image', selectedFile.value)

        // Send to Laravel for compression
        const response = await fetch('/compress', {
            method: 'POST',
            body: formData,
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || 'Compression failed')
        }

        // Update result state
        result.value = {
            compressed_path: data.compressed_path,
            original_size: data.original_size,
            compressed_size: data.compressed_size,
            compression_percent: data.compression_percent,
            dimensions: data.dimensions,
            format: data.format,
        }

        showResult.value = true
        compressionProgress.value = 100
    } catch (error) {
        console.error('Compression error:', error)
    } finally {
        isCompressing.value = false
    }
}

const handleDownload = () => {
    // Trigger download - the DownloadButton will handle it
    // This is just a placeholder for any client-side download logic
}

const reset = () => {
    selectedFile.value = null
    originalImage.value = null
    showResult.value = false
    result.value = {
        compressed_path: '',
        original_size: 0,
        compressed_size: 0,
        compression_percent: 0,
        dimensions: '',
        format: 'jpg',
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header/Hero Section -->
        <header class="border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-6 py-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-4xl font-extrabold tracking-tight text-gray-900">
                            Image Compressor
                        </h1>
                        <p class="mt-2 text-lg text-gray-600">
                            Compress Images Without Losing Quality
                        </p>
                        <p class="mt-3 text-sm text-gray-500">
                            Reduce your image size to under 1 MB while keeping it sharp, clear, and visually close to the original.
                        </p>
                    </div>

                    <!-- Upload Zone Call to Action -->
                    <div class="flex items-center space-x-3">
                        <UploadZone
                            ref="uploadZone"
                            @file-selected="handleFileSelect"
                            />
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-6 py-8">

            <!-- Upload Area -->
            <template v-if="!selectedFile">
                <div class="border-4 border-dashed border-gray-200 rounded-2xl cursor-pointer transition-all bg-white min-h-[200px] flex items-center justify-center p-8 text-center">
                    <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0-8l-4 4m4 4l-4-4m6 7a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                    <p class="text-gray-500 text-sm mb-2">Drag your image here</p>
                    <p class="text-gray-400 text-sm">or click to browse</p>
                    <input
                        type="file"
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        class="hidden"
                        @change="handleFileSelect($event.target.files[0])" />
                </div>
            </template>

            <!-- Compression Progress -->
            <template v-if="selectedFile && !showResult">
                <CompressionProgress
                    :progress="compressionProgress"
                    :message="'Analyzing and compressing image...'" />
            </template>

            <!-- Result Section -->
            <template v-if="showResult">
                <CompressionResult
                    :result="result"
                    @download="handleDownload"
                    @compress-another="reset"
                    />
            </template>
        </main>
    </div>
</template>