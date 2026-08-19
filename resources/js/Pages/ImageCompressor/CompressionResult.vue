<script setup>
import { computed } from 'vue'
import ImagePreview from './ImagePreview.vue'
import DownloadButton from './DownloadButton.vue'

const props = defineProps({
    result: {
        type: Object,
        default: () => ({
            originalSize: 0,
            compressedSize: 0,
            compressionPercent: 0,
            dimensions: '',
            format: 'jpg',
            originalPreview: '',
            compressedPreview: '',
            dataUrl: '',
            fileName: '',
        }),
    },
})

const emit = defineEmits(['compress-another', 'downloaded'])

const percentage = computed(() => Math.max(0, Math.round(props.result.compressionPercent)))
const originalLabel = computed(() => `${props.result.originalSize} B → ${props.result.compressedSize} B`)
</script>

<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-center gap-3 text-green-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Compression Complete</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <p class="mb-2 text-center text-sm font-semibold uppercase tracking-wider text-gray-500">Original Image</p>
                <ImagePreview
                    :image="result.originalPreview"
                    :image-size="result.originalSize"
                    :dimensions="result.dimensions"
                    label="Original"
                />
            </div>
            <div>
                <p class="mb-2 text-center text-sm font-semibold uppercase tracking-wider text-gray-500">Compressed Image</p>
                <ImagePreview
                    :image="result.compressedPreview"
                    :image-size="result.compressedSize"
                    :dimensions="result.dimensions"
                    label="Compressed"
                    overlay-class="bg-emerald-900/70"
                />
            </div>
        </div>

        <div class="flex flex-col items-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-center text-white shadow-lg shadow-indigo-600/20">
            <p class="text-sm font-medium opacity-90">File size reduced by</p>
            <p class="text-4xl font-extrabold tracking-tight tabular-nums">{{ percentage }}%</p>
            <p class="text-xs opacity-80 tabular-nums">{{ originalLabel }}</p>
        </div>

        <DownloadButton
            :data-url="result.dataUrl"
            :file-name="result.fileName"
            :label="`Download Compressed Image (${result.format.toUpperCase()})`"
            @downloaded="$emit('downloaded')"
        />

        <div class="flex justify-center">
            <button
                type="button"
                @click="$emit('compress-another')"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Compress Another Image
            </button>
        </div>
    </div>
</template>