<script setup lang="ts">
import { ref } from 'vue'
import LivePreview from '@/components/qr/LivePreview.vue'
import type { LivePreviewProps } from '@/components/qr/LivePreview.vue'
import type { FrameType } from '@/types/qr-visual'

interface Props extends LivePreviewProps {
    frameType?: FrameType
    frameText?: string
    frameColor?: string
}

withDefaults(defineProps<Props>(), {
    frameType: 'none',
    frameText: '',
    frameColor: '#000000',
})

const previewRef = ref<InstanceType<typeof LivePreview>>()

async function download(extension: 'png' | 'svg' | 'jpeg' | 'webp' = 'png', name = 'qrcode') {
    await previewRef.value?.download(extension, name)
}

defineExpose({ download })
</script>

<template>
    <!-- No frame: render LivePreview directly -->
    <template v-if="frameType === 'none'">
        <LivePreview v-bind="$props" ref="previewRef" />
    </template>

    <!-- Simple frame -->
    <div
        v-else-if="frameType === 'simple'"
        class="inline-flex flex-col overflow-hidden"
        :style="{ border: `3px solid ${frameColor}` }"
    >
        <LivePreview v-bind="$props" ref="previewRef" />
        <div
            v-if="frameText"
            class="px-3 py-1.5 text-center text-xs font-bold tracking-wide text-white"
            :style="{ backgroundColor: frameColor }"
        >
            {{ frameText }}
        </div>
    </div>

    <!-- Rounded frame -->
    <div
        v-else-if="frameType === 'rounded'"
        class="inline-flex flex-col overflow-hidden rounded-2xl"
        :style="{ border: `3px solid ${frameColor}` }"
    >
        <LivePreview v-bind="$props" ref="previewRef" />
        <div
            v-if="frameText"
            class="px-3 py-1.5 text-center text-xs font-bold tracking-wide text-white"
            :style="{ backgroundColor: frameColor }"
        >
            {{ frameText }}
        </div>
    </div>

    <!-- Speech bubble frame -->
    <div
        v-else-if="frameType === 'speech-bubble'"
        class="inline-flex flex-col items-center"
    >
        <div
            class="inline-flex flex-col overflow-hidden rounded-2xl"
            :style="{ border: `3px solid ${frameColor}` }"
        >
            <LivePreview v-bind="$props" ref="previewRef" />
            <div
                v-if="frameText"
                class="px-3 py-1.5 text-center text-xs font-bold tracking-wide text-white"
                :style="{ backgroundColor: frameColor }"
            >
                {{ frameText }}
            </div>
        </div>
        <!-- Triangle tail -->
        <div
            class="w-0 h-0"
            :style="{
                borderLeft: '10px solid transparent',
                borderRight: '10px solid transparent',
                borderTop: `10px solid ${frameColor}`,
            }"
        />
    </div>
</template>
