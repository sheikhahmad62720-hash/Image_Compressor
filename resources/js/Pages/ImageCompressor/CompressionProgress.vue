<script setup lang="typescript">
defineProps({
    progress: {
        type: Number,
        default: 0,
    },
    message: {
        type: String,
        default: 'Compressing...',
    },
})

defineEmits(['update:visible'])
</script>

<template>
    <Transition name="fade">
        <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl transform hover:scale-95 transition-transform">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 text-sm font-medium">{{ message }}</span>
                    <button 
                        @click="$emit('update:visible', false)"
                        class="text-gray-400 hover:text-gray-500 transition-colors"
                        aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="h-8 rounded-full bg-gray-200 overflow-hidden">
                    <div 
                        class="h-full bg-indigo-600 w-[[ progress ]]% transition-width duration-500 ease-out"
                        :style="{ width: progress + '%' }" 
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Compression progress" />
                </div>

                <p class="mt-3 text-gray-500 text-xs">{{ progress }}%</p>
            </div>
        </div>
    </Transition>
</template>

<script setup>
const props = defineProps({
    progress: {
        type: Number,
        default: 0,
    },
    message: {
        type: String,
        default: 'Compressing...',
    },
})

const emit = defineEmits(['update:visible'])
const visible = ref(true)

watch([props.progress], () => {
    if (props.progress >= 100) {
        setTimeout(() => {
            visible.value = false
            emit('update:visible', false)
        }, 1500)
    }
})
</script>