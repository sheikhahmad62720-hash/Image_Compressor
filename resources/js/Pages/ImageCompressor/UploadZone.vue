<script setup>
import { ref } from 'vue'

const ACCEPTED_MIMES = ['image/jpeg', 'image/png', 'image/webp']
const ACCEPTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp']
const MAX_FILE_SIZE = 20 * 1024 * 1024 // 20MB

const emit = defineEmits(['file-selected'])

const inputRef = ref(null)
const isDragging = ref(false)
const error = ref('')

function validate(file) {
    if (!file) return 'No file selected.'
    if (!ACCEPTED_MIMES.includes(file.type) && !ACCEPTED_EXTENSIONS.includes(file.name.split('.').pop().toLowerCase())) {
        return 'Unsupported file type. Please upload JPG, JPEG, PNG, or WebP.'
    }
    if (file.size > MAX_FILE_SIZE) {
        return 'File is too large. Maximum size is 20MB.'
    }
    return ''
}

function handleFile(file) {
    const message = validate(file)
    if (message) {
        error.value = message
        return
    }
    error.value = ''
    emit('file-selected', file)
}

function onDrop(event) {
    event.preventDefault()
    isDragging.value = false
    const file = event.dataTransfer?.files?.[0]
    if (file) handleFile(file)
}

function onDragOver(event) {
    event.preventDefault()
    isDragging.value = true
}

function onDragLeave() {
    isDragging.value = false
}

function onInputChange(event) {
    handleFile(event.target.files[0])
    event.target.value = ''
}
</script>

<template>
    <div class="w-full">
        <div
            role="button"
            tabindex="0"
            @click="inputRef?.click()"
            @keydown.enter="inputRef?.click()"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            :class="[
                'relative flex min-h-[260px] cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 text-center transition-all duration-300',
                isDragging
                    ? 'border-indigo-500 bg-indigo-50 scale-[1.01]'
                    : 'border-gray-300 bg-white hover:border-indigo-400 hover:bg-indigo-50/30',
            ]"
        >
            <input
                ref="inputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                class="hidden"
                @change="onInputChange"
            />

            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 shadow-inner">
                <svg
                    v-if="!isDragging"
                    class="h-8 w-8 transition-transform duration-300"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 13a9 9 0 11-9 9" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="translate-y-2" d="M12 3v9m0-9l-3 3m3-3l3 3" />
                </svg>
                <svg v-else class="h-8 w-8 animate-bounce text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>

            <p class="text-lg font-semibold text-gray-800">
                {{ isDragging ? 'Drop your image here' : 'Drag your image here' }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
                or <span class="font-medium text-indigo-600 underline underline-offset-2">click to browse</span>
            </p>
            <p class="mt-3 text-xs text-gray-400">JPG · JPEG · PNG · WebP — up to 20MB</p>
        </div>

        <p v-if="error" class="mt-3 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600" role="alert">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            {{ error }}
        </p>
    </div>
</template>