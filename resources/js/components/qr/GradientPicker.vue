<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import ColorPicker from '@/components/qr/ColorPicker.vue'
import type { GradientType } from '@/types/qr-visual'

export interface GradientConfig {
    enabled: boolean
    type: GradientType
    colorStart: string
    colorEnd: string
    rotation: number
}

const props = defineProps<{ modelValue: GradientConfig }>()
const emit = defineEmits<{ 'update:modelValue': [value: GradientConfig] }>()

const { t } = useI18n()

function patch(changes: Partial<GradientConfig>) {
    emit('update:modelValue', { ...props.modelValue, ...changes })
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium leading-none">{{ t('qr.gradient.label') }}</p>
            <!-- Toggle switch -->
            <button
                type="button"
                role="switch"
                :aria-checked="modelValue.enabled"
                :class="[
                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
                    modelValue.enabled ? 'bg-primary' : 'bg-input',
                ]"
                @click="patch({ enabled: !modelValue.enabled })"
            >
                <span
                    :class="[
                        'pointer-events-none block h-4 w-4 rounded-full bg-background shadow-lg transition-transform',
                        modelValue.enabled ? 'translate-x-4' : 'translate-x-0',
                    ]"
                />
            </button>
        </div>

        <template v-if="modelValue.enabled">
            <!-- Type selector -->
            <div class="flex gap-2">
                <button
                    v-for="type in (['linear', 'radial'] as GradientType[])"
                    :key="type"
                    type="button"
                    :class="[
                        'flex-1 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors',
                        modelValue.type === type
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border hover:border-ring text-muted-foreground',
                    ]"
                    @click="patch({ type })"
                >
                    {{ t(`qr.gradient.${type}`) }}
                </button>
            </div>

            <!-- Color pickers -->
            <div class="flex flex-wrap gap-4">
                <ColorPicker
                    :model-value="modelValue.colorStart"
                    :label="t('qr.gradient.colorStart')"
                    @update:model-value="patch({ colorStart: $event })"
                />
                <ColorPicker
                    :model-value="modelValue.colorEnd"
                    :label="t('qr.gradient.colorEnd')"
                    @update:model-value="patch({ colorEnd: $event })"
                />
            </div>

            <!-- Rotation slider (linear only) -->
            <div v-if="modelValue.type === 'linear'" class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium leading-none" for="gradient-rotation">
                        {{ t('qr.gradient.rotation', { deg: modelValue.rotation }) }}
                    </label>
                </div>
                <input
                    id="gradient-rotation"
                    type="range"
                    min="0"
                    max="360"
                    step="5"
                    :value="modelValue.rotation"
                    class="w-full accent-primary cursor-pointer"
                    @input="patch({ rotation: Number(($event.target as HTMLInputElement).value) })"
                >
            </div>
        </template>
    </div>
</template>
