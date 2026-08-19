<script setup lang="typescript">
defineProps({
    originalImage: {
        type: String,
        default: null,
    },
    originalSize: {
        type: Number,
        default: 0,
    },
    originalDimensions: {
        type: String,
        default: '0x0',
    },
    compressedImage: {
        type: String,
        default: null,
    },
    compressedSize: {
        type: Number,
        default: 0,
    },
    compressedDimensions: {
        type: String,
        default: '0x0',
    },
    format: {
        type: String,
        default: 'jpg',
    },
    compressionPercent: {
        type: Number,
        default: 0,
    },
    showComparison: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <Transition name="fade">
        <div v-if="showComparison || originalImage" class="relative">
            <!-- Original Image -->
            <div 
                v-if="originalImage"
                class="rounded-2xl border border-gray-200 overflow-hidden shadow-lg transition-all duration-300 hover:shadow-xl"
                :class="{ 'opacity-50': !showComparison }">
                <img 
                    :src="originalImage"
                    alt="Original image"
                    class="w-full h-auto" />
                <div class="absolute bottom-2 left-2 right-2 bg-black/60 text-white text-xs py-1">
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16l-4-4 4-4"/>
                        </svg>
                        <span>Original: {{ originalSize }} bytes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16l-4-4 4-4"/>
                        </svg>
                        <span>{{ originalDimensions }}</span>
                    </div>
                </div>
            </div>

            <!-- Compressed Image -->
            <div 
                v-if="compressedImage"
                class="rounded-2xl border border-gray-200 overflow-hidden shadow-lg mt-4 transition-all duration-300"
                style="max-height: 400px;">
                <img 
                    :src="compressedImage"
                    alt="Compressed image"
                    class="w-full h-auto" />
                <div class="absolute bottom-2 left-2 right-2 bg-black/80 text-white text-xs py-1">
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16l-4-4 4-4"/>
                        </svg>
                        <span>Compressed: {{ compressedSize }} bytes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16l-4-4 4-4"/>
                        </svg>
                        <span>{{ compressedDimensions }}</span>
                    </div>
                </div>
            </div>

            <!-- Comparison Labels -->
            <div v-if="showComparison" class="grid grid-cols-2 gap-4 px-4 py-2 text-xs font-medium">
                <div class="flex items-center justify-between">
                    <span class="text-gray-300">Original</span>
                    <span class="text-indigo-400 font-medium">8.2 MB</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-300">Compressed</span>
                    <span class="text-green-400 font-medium">824 KB</span>
                </div>
            </div>
        </div>
    </Transition>
</template>