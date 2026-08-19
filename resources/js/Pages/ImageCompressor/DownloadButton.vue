<script setup>
import { ref } from 'vue'

const props = defineProps({
    dataUrl: {
        type: String,
        required: true,
    },
    fileName: {
        type: String,
        default: 'compressed-image.webp',
    },
    label: {
        type: String,
        default: 'Download Compressed Image',
    },
})

const emit = defineEmits(['downloaded'])

const downloading = ref(false)

function triggerDownload(dataUrl, fileName) {
    const link = document.createElement('a')
    link.href = dataUrl
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

function handleDownload() {
    if (!props.dataUrl) return
    downloading.value = true
    // Let the UI show the pressed state, then download.
    setTimeout(() => {
        triggerDownload(props.dataUrl, props.fileName)
        downloading.value = false
        emit('downloaded')
    }, 150)
}
</script>

<template>
    <button
        type="button"
        @click="handleDownload"
        :disabled="!dataUrl || downloading"
        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-indigo-600/25 transition-all duration-200 hover:bg-indigo-500 hover:shadow-indigo-500/30 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <svg v-if="!downloading" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <svg v-else class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        {{ label }}
    </button>
</template>