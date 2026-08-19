<script setup lang="typescript">
defineProps({
    result: {
        type: Object,
        default: () => ({
            compressed_path: '',
            original_size: 0,
            compressed_size: 0,
            compression_percent: 0,
            dimensions: '',
            format: 'jpg',
        }),
    },
    showDownload: {
        type: Boolean,
        default: true,
    },
})

defineEmits(['update:visible', 'download', 'compress-another'])
</script>

<template>
    <Transition name="fade">
        <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 shadow-2xl transform overflow-hidden">
                <div class="flex flex-col items-center space-y-6">

                    <!-- Compression Complete Message -->
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16l-4-4 4-4"/>
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2">Compression Complete</h2>
                        <p class="text-gray-500 mt-1">Your image has been compressed successfully.</p>
                    </div>

                    <!-- Comparison Result -->
                    <ImagePreview
                        v-bind="result"
                        :showComparison="true"
                        />
                    <!-- <hr class="border-t border-gray-200 my-6" /> -->

                    <!-- Download Button -->
                    <div class="w-full">
                        <DownloadButton
                            :url="'/download?file_name=compressed-image.' + result.format"
                            :label="'Download Compressed Image (' + result.format.toUpperCase() + ')'"
                            />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 w-full">
                        <SecondaryButton @click="$emit('update:visible', false)">Close</SecondaryButton>
                        <SecondaryButton @click="$emit('compress-another')">Compress Another Image</SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>