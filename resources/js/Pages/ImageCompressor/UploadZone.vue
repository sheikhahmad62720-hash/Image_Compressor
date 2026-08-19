<script setup>
import { ref, computed, watch } from 'vue'
import { useInertia } from '@inertiajs/vue3'

const props = defineProps({
    accepted: {
        type: Array,
        default: () => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
    },
})

const { updateProgress } = useInertia()

const dropZone = ref(null)
const isDragging = ref(false)
const selectedFile = ref(null)
const originalImage = ref(null)
const compressionProgress = ref(0)
const isCompressing = ref(false)

const MAX_FILE_SIZE = 20 * 1024 * 1024 // 20MB

const handleDragOver = (e) => {
    e.preventDefault()
    e.dataTransfer.effectAllowed = 'copy'
    isDragging.value = true
}

const handleDragLeave = (e) => {
    isDragging.value = false
}

const handleDrop = (e) => {
    e.preventDefault()
    isDragging.value = false

    const files = e.dataTransfer.files
    if (files && files.length > 0) {
        handleFileSelect(files[0])
    }
}

const handleFileSelect = (file) => {
    if (!file) return

    // Validate file type
    const accepted = props.accepted
    const isValidType = accepted.some((mime) => file.type === mime || file.name.match(new RegExp(mime.split('/')[1] + '$')))

    if (!isValidType) {
        alert('Unsupported file type. Please upload JPG, JPEG, PNG, or WebP.')
        return
    }

    // Validate file size
    if (file.size > MAX_FILE_SIZE) {
        alert('File is too large. Maximum size is 20MB.')
        return
    }

    selectedFile.value = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
        originalImage.value = e.target.result
    }
    reader.readAsDataURL(file)

    // Start compression automatically
    startCompression()
}

const startCompression = async () => {
    if (!selectedFile.value || isCompressing.value) return

    isCompressing.value = true
    compressionProgress.value = 0

    try {
        const formData = new FormData()
        formData.append('image', selectedFile.value)

        // Send to Laravel for compression
        const response = await fetch('/compress', {
            method: 'POST',
            body: formData,
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || 'Compression failed')
        }

        // Store compressed result
        compressionProgress.value = 100
        showResult(data)
    } catch (error) {
        console.error('Compression error:', error)
    } finally {
        isCompressing.value = false
    }
}

const showResult = (data) => {
    // Trigger result display - will be handled by parent
    updateProgress?.('show-result', data)
}

const reset = () => {
    selectedFile.value = null
    originalImage.value = null
    compressionProgress.value = 0
    isCompressing.value = false
}
</script>

<template>
    <div class="relative w-full max-w-2xl mx-auto">
        <div 
            ref="dropZone"
            class="border-4 border-dashed border-gray-200 rounded-2xl cursor-pointer transition-all 
                   bg-gray-50 hover:border-indigo-600 hover:bg-gray-100 
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 
                   min-h-[200px] flex items-center justify-center p-8 text-center">
            <div class="relative z-10">
                <p class="text-gray-400 text-sm mb-2 drag-text">
                    Drag your image here
                </p>
                <p class="text-gray-500 text-sm">or click to browse</p>
            </div>
        </div>

        <!-- Upload button -->
        <input 
            type="file" 
            accept="image/jpeg,image/jpg,image/png,image/webp"
            class="hidden"
            @change="handleFileSelect($event.target.files[0])" />

        <!-- Original preview -->
        <template v-if="originalImage">
            <div class="mt-6">
                <img 
                    :src="originalImage"
                    alt="Original image preview"
                    class="rounded-xl border border-gray-200 w-full max-h-64 object-cover" />
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                    <span>Original preview</span>
                    <span class="cursor-pointer text-indigo-600 hover:underline" @click="reset">Remove</span>
                </div>
            </div>
        </template>
    </div>
</template>