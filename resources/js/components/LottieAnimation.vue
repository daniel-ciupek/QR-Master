<script setup lang="ts">
import lottie from 'lottie-web'
import { onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps<{
    animationData: object
    loop?: boolean
    autoplay?: boolean
}>()

const container = ref<HTMLDivElement>()
let animation: ReturnType<typeof lottie.loadAnimation> | null = null

function load() {
    if (!container.value) return
    animation?.destroy()
    animation = lottie.loadAnimation({
        container: container.value,
        renderer: 'svg',
        loop: props.loop ?? true,
        autoplay: props.autoplay ?? true,
        animationData: props.animationData,
    })
}

onMounted(load)
onUnmounted(() => animation?.destroy())
watch(() => props.animationData, load)
</script>

<template>
    <div ref="container" />
</template>
