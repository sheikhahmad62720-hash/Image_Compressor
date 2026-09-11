<script setup>
import { ref } from 'vue'

const emit = defineEmits(['size-selected'])

const sizes = [
    { label: '500 KB', value: 500 * 1024 },
    { label: '1 MB', value: 1 * 1024 * 1024 },
    { label: '2 MB', value: 2 * 1024 * 1024 },
    { label: '5 MB', value: 5 * 1024 * 1024 },
]

const selected = ref(1 * 1024 * 1024)

function select(size) {
    selected.value = size
    emit('size-selected', size)
}
</script>

<template>
    <div class="w-full space-y-3">
        <p class="text-sm font-semibold text-gray-700">Target File Size</p>
        <div class="grid grid-cols-4 gap-3">
            <button
                v-for="size in sizes"
                :key="size.value"
                type="button"
                @click="select(size.value)"
                :class="[
                    'rounded-xl border-2 px-4 py-3 text-sm font-semibold transition-all duration-200',
                    selected === size.value
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 shadow-sm'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50',
                ]"
            >
                {{ size.label }}
            </button>
        </div>
    </div>
</template>
