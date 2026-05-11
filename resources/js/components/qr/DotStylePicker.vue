<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { DotStyle } from '@/types/qr-visual'
import { DOT_STYLES } from '@/types/qr-visual'

defineProps<{ modelValue: DotStyle }>()
const emit = defineEmits<{ 'update:modelValue': [value: DotStyle] }>()

const { t } = useI18n()

// Pre-built SVG inner content for each dot style (2×2 icon grid, 24×24 viewport)
// Using v-html is safe here — content is 100% static, never from user input
const DOT_SVG: Record<DotStyle, string> = {
    'square': [
        '<rect x="2" y="2" width="9" height="9"/>',
        '<rect x="13" y="2" width="9" height="9"/>',
        '<rect x="2" y="13" width="9" height="9"/>',
        '<rect x="13" y="13" width="9" height="9"/>',
    ].join(''),
    'dots': [
        '<circle cx="6.5" cy="6.5" r="4.5"/>',
        '<circle cx="17.5" cy="6.5" r="4.5"/>',
        '<circle cx="6.5" cy="17.5" r="4.5"/>',
        '<circle cx="17.5" cy="17.5" r="4.5"/>',
    ].join(''),
    'rounded': [
        '<rect x="2" y="2" width="9" height="9" rx="3"/>',
        '<rect x="13" y="2" width="9" height="9" rx="3"/>',
        '<rect x="2" y="13" width="9" height="9" rx="3"/>',
        '<rect x="13" y="13" width="9" height="9" rx="3"/>',
    ].join(''),
    'classy': [
        '<rect x="2" y="2" width="9" height="9"/>',
        '<rect x="13" y="2" width="9" height="9"/>',
        '<rect x="2" y="13" width="9" height="9"/>',
        '<rect x="13" y="13" width="9" height="9"/>',
        '<circle cx="6.5" cy="6.5" r="2.2" fill="var(--background,#fff)"/>',
        '<circle cx="17.5" cy="6.5" r="2.2" fill="var(--background,#fff)"/>',
        '<circle cx="6.5" cy="17.5" r="2.2" fill="var(--background,#fff)"/>',
        '<circle cx="17.5" cy="17.5" r="2.2" fill="var(--background,#fff)"/>',
    ].join(''),
    'classy-rounded': [
        '<rect x="2" y="2" width="9" height="9" rx="2.5"/>',
        '<rect x="13" y="2" width="9" height="9" rx="2.5"/>',
        '<rect x="2" y="13" width="9" height="9" rx="2.5"/>',
        '<rect x="13" y="13" width="9" height="9" rx="2.5"/>',
        '<circle cx="6.5" cy="6.5" r="2.2" fill="var(--background,#fff)"/>',
        '<circle cx="17.5" cy="6.5" r="2.2" fill="var(--background,#fff)"/>',
        '<circle cx="6.5" cy="17.5" r="2.2" fill="var(--background,#fff)"/>',
        '<circle cx="17.5" cy="17.5" r="2.2" fill="var(--background,#fff)"/>',
    ].join(''),
    'extra-rounded': [
        '<rect x="2" y="2" width="9" height="9" rx="4.5"/>',
        '<rect x="13" y="2" width="9" height="9" rx="4.5"/>',
        '<rect x="2" y="13" width="9" height="9" rx="4.5"/>',
        '<rect x="13" y="13" width="9" height="9" rx="4.5"/>',
    ].join(''),
}
</script>

<template>
    <div class="space-y-1.5">
        <p class="text-sm font-medium leading-none">{{ t('qr.style.dotStyle') }}</p>
        <div class="grid grid-cols-3 gap-2">
            <button
                v-for="style in DOT_STYLES"
                :key="style.value"
                type="button"
                :class="[
                    'flex flex-col items-center gap-1.5 rounded-lg border p-2.5 text-xs transition-colors',
                    modelValue === style.value
                        ? 'border-primary bg-primary/5 text-primary font-medium'
                        : 'border-border hover:border-ring text-muted-foreground hover:text-foreground',
                ]"
                @click="emit('update:modelValue', style.value)"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="size-8"
                    fill="currentColor"
                    aria-hidden="true"
                    v-html="DOT_SVG[style.value]"
                />
                {{ t(`qr.style.dots.${style.value.replace('-', '_')}`) }}
            </button>
        </div>
    </div>
</template>
