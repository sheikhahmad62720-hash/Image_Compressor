<script setup>
import { computed } from 'vue'

const props = defineProps({
    progress: {
        type: Number,
        default: 0,
    },
    message: {
        type: String,
        default: 'Compressing your image…',
    },
})

const normalized = computed(() => Math.min(100, Math.max(0, props.progress)))
</script>

<template>
    <div class="w-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center">
                    <svg
                        v-if="progress < 100"
                        class="h-5 w-5 animate-spin text-indigo-600"
                        fill="none" viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <svg v-else class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ message }}</p>
                    <p class="text-xs text-gray-500">Optimizing while preserving quality</p>
                </div>
            </div>
            <span class="text-sm font-bold tabular-nums text-indigo-600">{{ normalized }}%</span>
        </div>

        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-100">
            <div
                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-500 ease-out"
                :style="{ width: normalized + '%' }"
            />
        </div>
    </div>
</template>