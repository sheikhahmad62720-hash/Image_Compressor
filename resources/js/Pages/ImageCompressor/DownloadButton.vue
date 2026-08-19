<script setup lang="typescript">
defineProps({
    url: {
        type: String,
        default: '/download',
    },
    label: {
        type: String,
        default: 'Download',
    },
    showNotification: {
        type: Boolean,
        default: true,
    },
})

defineEmits(['clicked'])

const emit = defineEmits(['clicked'])

const handleDownload = async () => {
    try {
        const response = await fetch(this.url, {
            method: 'GET',
            headers: {
                'Accept': 'image/*',
            },
            responseType: 'blob',
        })

        if (response.ok) {
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = this.url.split('?')[1]?.replace('file_name=', '') || 'compressed-image.' + this.url.split('.').pop()
            document.body.appendChild(a)
            a.click()
            setTimeout(() => {
                document.body.removeChild(a)
                window.URL.revokeObjectURL(url)
            }, 100)
            
            // Show success notification
            if (this.showNotification) {
                const notification = document.createElement('div')
                notification.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-2xl shadow-2xl z-50 animate-in fade-in-0up-80'
                notification.textContent = 'Download started!'
                document.body.appendChild(notification)
                setTimeout(() => notification.remove(), 3000)
            }
            
            emit('clicked')
        }
    } catch (error) {
        console.error('Download failed:', error)
    }
}
</script>

<template>
    <button 
        @click="handleDownload"
        class="w-full rounded-2xl border border-indigo-600 bg-indigo-600 px-6 py-3 text-lg font-medium uppercase tracking-widest text-white transition-all duration-200 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
        aria-label="Download compressed image">
        <slot />
    </button>
</template>