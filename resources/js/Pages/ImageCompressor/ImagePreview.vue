<script setup>
import { computed } from 'vue'

const props = defineProps({
    image: {
        type: String,
        default: null,
    },
    imageSize: {
        type: Number,
        default: 0,
    },
    dimensions: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    overlayClass: {
        type: String,
        default: 'bg-black/60',
    },
})

const sizeLabel = computed(() => formatBytes(props.imageSize))

function formatBytes(bytes) {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return `${(bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`
}
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 shadow-sm">
        <img
            v-if="image"
            :src="image"
            :alt="label || 'Image preview'"
            class="w-full h-auto max-h-[420px] object-contain"
        />
        <div v-else class="flex h-64 items-center justify-center">
            <div class="flex h-16 w-16 animate-pulse items-center justify-center rounded-xl bg-gray-200">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
        <div :class="['absolute bottom-0 inset-x-0 flex items-center justify-between px-4 py-2 text-xs text-white', overlayClass]">
            <span>{{ label }}</span>
            <span class="font-medium">{{ dimensions ? `${dimensions} · ` : '' }}{{ sizeLabel }}</span>
        </div>
    </div>
</template>