<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { CornerDotStyle, CornerSquareStyle, DotStyle, FrameType } from '@/types/qr-visual'
import type { GradientConfig } from '@/components/qr/GradientPicker.vue'

export interface QrTemplate {
    name: string
    dotStyle: DotStyle
    dotColor: string
    bgColor: string
    cornerSquare: CornerSquareStyle
    cornerDot: CornerDotStyle
    gradient: GradientConfig
    frameType: FrameType
    frameText: string
    frameColor: string
}

const emit = defineEmits<{ apply: [template: QrTemplate] }>()

const { t } = useI18n()

const TEMPLATES: QrTemplate[] = [
    {
        name: t('qr.templates.classic'),
        dotStyle: 'square',
        dotColor: '#000000',
        bgColor: '#ffffff',
        cornerSquare: 'square',
        cornerDot: 'square',
        gradient: { enabled: false, type: 'linear', colorStart: '#000000', colorEnd: '#444444', rotation: 0 },
        frameType: 'none',
        frameText: '',
        frameColor: '#000000',
    },
    {
        name: t('qr.templates.modern'),
        dotStyle: 'rounded',
        dotColor: '#1e3a5f',
        bgColor: '#ffffff',
        cornerSquare: 'extra-rounded',
        cornerDot: 'dot',
        gradient: { enabled: false, type: 'linear', colorStart: '#1e3a5f', colorEnd: '#0c4a6e', rotation: 0 },
        frameType: 'simple',
        frameText: t('qr.templates.scanMe'),
        frameColor: '#1e3a5f',
    },
    {
        name: t('qr.templates.vibrant'),
        dotStyle: 'dots',
        dotColor: '#991b1b',
        bgColor: '#fff1f2',
        cornerSquare: 'dot',
        cornerDot: 'dot',
        gradient: { enabled: true, type: 'linear', colorStart: '#991b1b', colorEnd: '#f97316', rotation: 45 },
        frameType: 'rounded',
        frameText: t('qr.templates.scanMe'),
        frameColor: '#991b1b',
    },
    {
        name: t('qr.templates.minimal'),
        dotStyle: 'square',
        dotColor: '#374151',
        bgColor: '#f9fafb',
        cornerSquare: 'square',
        cornerDot: 'square',
        gradient: { enabled: false, type: 'linear', colorStart: '#374151', colorEnd: '#6b7280', rotation: 0 },
        frameType: 'none',
        frameText: '',
        frameColor: '#374151',
    },
    {
        name: t('qr.templates.restaurant'),
        dotStyle: 'rounded',
        dotColor: '#166534',
        bgColor: '#f0fdf4',
        cornerSquare: 'extra-rounded',
        cornerDot: 'dot',
        gradient: { enabled: false, type: 'linear', colorStart: '#166534', colorEnd: '#15803d', rotation: 0 },
        frameType: 'rounded',
        frameText: 'MENU',
        frameColor: '#166534',
    },
]
</script>

<template>
    <div class="space-y-2">
        <p class="text-sm font-medium leading-none">{{ t('qr.templates.label') }}</p>
        <div class="grid grid-cols-5 gap-2">
            <button
                v-for="tpl in TEMPLATES"
                :key="tpl.name"
                type="button"
                class="flex flex-col items-center gap-1.5 rounded-lg border border-border p-2.5 text-xs hover:border-ring transition-colors text-muted-foreground hover:text-foreground"
                @click="emit('apply', tpl)"
            >
                <!-- Color preview -->
                <div class="flex gap-0.5">
                    <span
                        class="size-5 rounded-l-sm border border-black/10"
                        :style="{ backgroundColor: tpl.dotColor }"
                    />
                    <span
                        class="size-5 rounded-r-sm border border-black/10"
                        :style="{ backgroundColor: tpl.bgColor }"
                    />
                </div>
                {{ tpl.name }}
            </button>
        </div>
    </div>
</template>
