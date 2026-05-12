<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import ColorPicker from '@/components/qr/ColorPicker.vue'
import { Input } from '@/components/ui/input'
import type { FrameType } from '@/types/qr-visual'
import { FRAME_TYPES } from '@/types/qr-visual'

defineProps<{
    frameType: FrameType
    frameText: string
    frameColor: string
}>()

const emit = defineEmits<{
    'update:frameType': [value: FrameType]
    'update:frameText': [value: string]
    'update:frameColor': [value: string]
}>()

const { t } = useI18n()

const FRAME_SVG: Record<FrameType, string> = {
    'none': '<rect x="2" y="2" width="20" height="20" rx="1" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 2"/>',
    'simple': [
        '<rect x="2" y="2" width="20" height="15" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        '<rect x="2" y="17" width="20" height="5" fill="currentColor"/>',
    ].join(''),
    'rounded': [
        '<rect x="2" y="2" width="20" height="15" rx="3" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        '<rect x="2" y="17" width="20" height="5" rx="3" fill="currentColor"/>',
    ].join(''),
    'speech-bubble': [
        '<rect x="2" y="2" width="20" height="15" rx="3" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        '<rect x="2" y="17" width="20" height="4" rx="3" fill="currentColor"/>',
        '<polygon points="10,21 14,21 12,24" fill="currentColor"/>',
    ].join(''),
}
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm font-medium leading-none">{{ t('qr.frame.label') }}</p>

        <!-- Frame type grid -->
        <div class="grid grid-cols-4 gap-2">
            <button
                v-for="ft in FRAME_TYPES"
                :key="ft.value"
                type="button"
                :class="[
                    'flex flex-col items-center gap-1 rounded-lg border p-2 text-xs transition-colors',
                    frameType === ft.value
                        ? 'border-primary bg-primary/5 text-primary font-medium'
                        : 'border-border hover:border-ring text-muted-foreground hover:text-foreground',
                ]"
                @click="emit('update:frameType', ft.value)"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="size-7"
                    fill="currentColor"
                    aria-hidden="true"
                    v-html="FRAME_SVG[ft.value]"
                />
                {{ t(`qr.frame.types.${ft.value.replace('-', '_')}`) }}
            </button>
        </div>

        <!-- Frame text + color (only when frame is active) -->
        <template v-if="frameType !== 'none'">
            <div class="space-y-1.5">
                <label class="text-sm font-medium leading-none" for="frame-text">
                    {{ t('qr.frame.text') }}
                </label>
                <Input
                    id="frame-text"
                    :model-value="frameText"
                    :placeholder="t('qr.frame.textPlaceholder')"
                    maxlength="40"
                    @update:model-value="emit('update:frameText', String($event))"
                />
            </div>
            <ColorPicker
                :model-value="frameColor"
                :label="t('qr.frame.color')"
                @update:model-value="emit('update:frameColor', $event)"
            />
        </template>
    </div>
</template>
