<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps<{
    siteKey: string
    theme?: 'auto' | 'light' | 'dark'
}>()

const emit = defineEmits<{
    token: [value: string]
    expire: []
    error: []
}>()

const container = ref<HTMLDivElement>()
let widgetId: string | undefined

function loadScript(): Promise<void> {
    return new Promise((resolve) => {
        if (document.getElementById('cf-turnstile-script')) {
            resolve()
            return
        }
        const script = document.createElement('script')
        script.id = 'cf-turnstile-script'
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'
        script.onload = () => resolve()
        document.head.appendChild(script)
    })
}

function tryRender(): void {
    if (window.turnstile && container.value) {
        widgetId = window.turnstile.render(container.value, {
            sitekey: props.siteKey,
            theme: props.theme ?? 'auto',
            callback: (token: string) => emit('token', token),
            'expired-callback': () => emit('expire'),
            'error-callback': () => emit('error'),
        })
    } else {
        setTimeout(tryRender, 100)
    }
}

onMounted(async () => {
    await loadScript()
    tryRender()
})

onUnmounted(() => {
    if (widgetId !== undefined) {
        window.turnstile?.remove(widgetId)
    }
})
</script>

<template>
    <div ref="container" />
</template>
