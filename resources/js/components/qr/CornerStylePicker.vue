<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { CornerDotStyle, CornerSquareStyle } from '@/types/qr-visual'
import { CORNER_DOT_STYLES, CORNER_SQUARE_STYLES } from '@/types/qr-visual'

defineProps<{
    cornerSquare: CornerSquareStyle
    cornerDot: CornerDotStyle
}>()

const emit = defineEmits<{
    'update:cornerSquare': [value: CornerSquareStyle]
    'update:cornerDot': [value: CornerDotStyle]
}>()

const { t } = useI18n()

// Corner square SVGs — show the 3 finder-pattern outer squares
const CORNER_SQUARE_SVG: Record<CornerSquareStyle, string> = {
    'square': [
        // outer square
        '<rect x="1" y="1" width="22" height="22" rx="0" fill="none" stroke="currentColor" stroke-width="3"/>',
        // inner dot
        '<rect x="8" y="8" width="8" height="8" rx="0"/>',
    ].join(''),
    'dot': [
        '<circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="3"/>',
        '<circle cx="12" cy="12" r="4"/>',
    ].join(''),
    'extra-rounded': [
        '<rect x="1" y="1" width="22" height="22" rx="8" fill="none" stroke="currentColor" stroke-width="3"/>',
        '<rect x="8" y="8" width="8" height="8" rx="2.5"/>',
    ].join(''),
}

const CORNER_DOT_SVG: Record<CornerDotStyle, string> = {
    'square': '<rect x="7" y="7" width="10" height="10" rx="0"/>',
    'dot': '<circle cx="12" cy="12" r="5"/>',
}
</script>

<template>
    <div class="space-y-4">
        <!-- Corner squares -->
        <div class="space-y-1.5">
            <p class="text-sm font-medium leading-none">{{ t('qr.style.cornerSquare') }}</p>
            <div class="flex gap-2">
                <button
                    v-for="style in CORNER_SQUARE_STYLES"
                    :key="style.value"
                    type="button"
                    :class="[
                        'flex flex-col items-center gap-1.5 rounded-lg border px-3 py-2.5 text-xs flex-1 transition-colors',
                        cornerSquare === style.value
                            ? 'border-primary bg-primary/5 text-primary font-medium'
                            : 'border-border hover:border-ring text-muted-foreground hover:text-foreground',
                    ]"
                    @click="emit('update:cornerSquare', style.value)"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-8"
                        fill="currentColor"
                        aria-hidden="true"
                        v-html="CORNER_SQUARE_SVG[style.value]"
                    />
                    {{ t(`qr.style.cornerSquares.${style.value.replace('-', '_')}`) }}
                </button>
            </div>
        </div>

        <!-- Corner dots -->
        <div class="space-y-1.5">
            <p class="text-sm font-medium leading-none">{{ t('qr.style.cornerDot') }}</p>
            <div class="flex gap-2">
                <button
                    v-for="style in CORNER_DOT_STYLES"
                    :key="style.value"
                    type="button"
                    :class="[
                        'flex flex-col items-center gap-1.5 rounded-lg border px-3 py-2.5 text-xs flex-1 transition-colors',
                        cornerDot === style.value
                            ? 'border-primary bg-primary/5 text-primary font-medium'
                            : 'border-border hover:border-ring text-muted-foreground hover:text-foreground',
                    ]"
                    @click="emit('update:cornerDot', style.value)"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-8"
                        fill="currentColor"
                        aria-hidden="true"
                        v-html="CORNER_DOT_SVG[style.value]"
                    />
                    {{ t(`qr.style.cornerDots.${style.value}`) }}
                </button>
            </div>
        </div>
    </div>
</template>
