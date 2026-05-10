<script setup lang="ts">
import QRCodeStyling from 'qr-code-styling'
import type { Options, DotType, CornerSquareType, CornerDotType, ErrorCorrectionLevel } from 'qr-code-styling'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

export interface LivePreviewProps {
    data: string
    size?: number
    dotType?: DotType
    dotColor?: string
    backgroundColor?: string
    cornersSquareType?: CornerSquareType
    cornersDotType?: CornerDotType
    errorCorrectionLevel?: ErrorCorrectionLevel
    image?: string
    imageSize?: number
    hideBackgroundDots?: boolean
}

const props = withDefaults(defineProps<LivePreviewProps>(), {
    size: 280,
    dotType: 'square',
    dotColor: '#000000',
    backgroundColor: '#ffffff',
    cornersSquareType: 'square',
    cornersDotType: 'square',
    errorCorrectionLevel: 'M',
    image: undefined,
    imageSize: 0.3,
    hideBackgroundDots: true,
})

const container = ref<HTMLDivElement>()
let qr: QRCodeStyling | null = null

function buildOptions(): Options {
    return {
        type: 'canvas',
        width: props.size,
        height: props.size,
        data: props.data || ' ',
        margin: 8,
        qrOptions: {
            errorCorrectionLevel: props.errorCorrectionLevel,
        },
        dotsOptions: {
            type: props.dotType,
            color: props.dotColor,
        },
        cornersSquareOptions: {
            type: props.cornersSquareType,
        },
        cornersDotOptions: {
            type: props.cornersDotType,
        },
        backgroundOptions: {
            color: props.backgroundColor,
        },
        ...(props.image
            ? {
                  image: props.image,
                  imageOptions: {
                      hideBackgroundDots: props.hideBackgroundDots,
                      imageSize: props.imageSize,
                      margin: 4,
                      crossOrigin: 'anonymous',
                  },
              }
            : {}),
    }
}

const debouncedUpdate = useDebounceFn(() => {
    qr?.update(buildOptions())
}, 150)

onMounted(() => {
    if (!container.value) return
    qr = new QRCodeStyling(buildOptions())
    qr.append(container.value)
})

async function download(extension: 'png' | 'svg' | 'jpeg' | 'webp' = 'png', name = 'qrcode'): Promise<void> {
    await qr?.download({ extension, name })
}

defineExpose({ download })

onUnmounted(() => {
    qr = null
})

watch(
    () => [
        props.data,
        props.size,
        props.dotType,
        props.dotColor,
        props.backgroundColor,
        props.cornersSquareType,
        props.cornersDotType,
        props.errorCorrectionLevel,
        props.image,
        props.imageSize,
        props.hideBackgroundDots,
    ],
    debouncedUpdate,
)
</script>

<template>
    <div ref="container" class="inline-flex items-center justify-center rounded-lg overflow-hidden" />
</template>
