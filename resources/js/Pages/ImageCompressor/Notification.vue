<script setup lang="typescript">
defineProps({
    type: {
        type: String,
        default: 'info',
    },
    message: {
        type: String,
        default: 'Action completed',
    },
    duration: {
        type: Number,
        default: 3000,
    },
})

defineEmits(['dismiss'])
</script>

<template>
    <Transition name="fade">
        <div v-if="show" class="fixed top-4 left-1/2 -translate-x-1/2 bg-white rounded-2xl shadow-2xl px-6 py-4 border-l-4 border-indigo-600 transform translate-y--1 opacity-0 transition-all duration-500 ease-out z-50">
            <div class="flex items-center gap-3">
                <svg :class="type === 'success' ? 'w-5 h-5 text-green-500' : 'w-5 h-5 text-indigo-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-gray-800 font-medium">{{ message }}</span>
                <button 
                    @click="dismiss"
                    class="ml-auto text-gray-400 hover:text-gray-500 transition-colors"
                    aria-label="Dismiss">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L186M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'info',
    },
    message: {
        type: String,
        default: 'Action completed',
    },
    duration: {
        type: Number,
        default: 3000,
    },
})

const emit = defineEmits(['dismiss'])
const show = ref(true)

setTimeout(() => {
    show.value = false
    emit('dismiss')
}, props.duration)
</script>