<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    originalSize: {
        type: Number,
        required: true,
    },
})

const emit = defineEmits(['size-selected'])

const PERCENTS = [25, 50, 75, 100, 125, 150, 200]
const MIN_BYTES = 50 * 1024
const MAX_BYTES = 20 * 1024 * 1024

const selectedPercent = ref(100)

const options = computed(() =>
    PERCENTS.map((percent) => ({
        percent,
        bytes: clamp(Math.round((props.originalSize * percent) / 100)),
    }))
)

function clamp(bytes) {
    return Math.min(MAX_BYTES, Math.max(MIN_BYTES, bytes))
}

function select(option) {
    selectedPercent.value = option.percent
    emit('size-selected', option.bytes)
}

const selectedLabel = computed(() => {
    const option = options.value.find((o) => o.percent === selectedPercent.value)
    return option ? formatBytes(option.bytes) : ''
})

function formatBytes(bytes) {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return `${(bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`
}

onMounted(() => emit('size-selected', clamp(props.originalSize)))
</script>

<template>
    <div class="w-full space-y-3">
        <div class="flex items-baseline justify-between">
            <p class="text-sm font-semibold text-gray-700">Target File Size</p>
            <p class="text-xs text-gray-400">as % of your original file</p>
        </div>
        <div class="flex flex-wrap justify-center gap-2">
            <button
                v-for="option in options"
                :key="option.percent"
                type="button"
                @click="select(option)"
                :class="[
                    'rounded-xl border-2 px-4 py-2.5 text-sm font-semibold transition-all duration-200',
                    selectedPercent === option.percent
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 shadow-sm'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50',
                ]"
            >
                {{ option.percent === 100 ? 'Same size' : `${option.percent}%` }}
            </button>
        </div>
        <p class="text-center text-xs font-medium text-gray-500">
            Resulting file: <span class="font-semibold text-gray-700">≈ {{ selectedLabel }}</span>
        </p>
    </div>
</template>