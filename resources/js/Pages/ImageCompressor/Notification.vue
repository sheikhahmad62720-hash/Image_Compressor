<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'success',
    },
    message: {
        type: String,
        default: 'Done',
    },
    duration: {
        type: Number,
        default: 3000,
    },
})

const emit = defineEmits(['dismiss'])

const visible = ref(false)
let timer = null

onMounted(() => {
    visible.value = true
    timer = setTimeout(hide, props.duration)
})

function hide() {
    visible.value = false
    emit('dismiss')
}

watch(
    () => props.message,
    () => {
        clearTimeout(timer)
        visible.value = true
        timer = setTimeout(hide, props.duration)
    }
)
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-3 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-3 opacity-0"
    >
        <div
            v-if="visible"
            :class="[
                'pointer-events-auto flex items-center gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur w-full max-w-sm',
                type === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800',
            ]"
        >
            <svg
                v-if="type === 'success'"
                class="h-5 w-5 shrink-0 text-green-600"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg
                v-else
                class="h-5 w-5 shrink-0 text-red-600"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-sm font-medium">{{ message }}</p>
            <button
                type="button"
                class="ml-auto rounded-md p-1 opacity-60 transition hover:opacity-100"
                @click="hide"
                aria-label="Dismiss"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Transition>
</template>