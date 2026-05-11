<script setup lang="ts">
import { ref, watch } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { BRAND_PALETTES } from '@/types/qr-visual'

const props = defineProps<{
    modelValue: string
    label: string
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const open = ref(false)
const wrapperRef = ref<HTMLElement>()
const hexInput = ref(props.modelValue)

watch(() => props.modelValue, (v) => { hexInput.value = v })

onClickOutside(wrapperRef, () => { open.value = false })

function onColorChange(e: Event) {
    const val = (e.target as HTMLInputElement).value
    hexInput.value = val
    emit('update:modelValue', val)
}

function onHexInput(e: Event) {
    const val = (e.target as HTMLInputElement).value.trim()
    hexInput.value = val
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        emit('update:modelValue', val)
    }
}

function selectPreset(color: string) {
    hexInput.value = color
    emit('update:modelValue', color)
    open.value = false
}

const PRESET_COLORS = [
    '#000000', '#374151', '#6b7280', '#d1d5db',
    '#ffffff', '#fef9c3', '#fee2e2', '#ede9fe',
    '#1e3a5f', '#166534', '#991b1b', '#5b21b6',
    '#0c4a6e', '#7c2d12', '#f59e0b', '#10b981',
]
</script>

<template>
    <div class="space-y-1.5">
        <p class="text-sm font-medium leading-none">{{ label }}</p>
        <div ref="wrapperRef" class="relative inline-block">
            <!-- Swatch trigger -->
            <button
                type="button"
                class="flex items-center gap-2 rounded-md border border-border bg-background px-3 py-2 text-sm hover:bg-muted/50 transition-colors"
                @click="open = !open"
            >
                <span
                    class="size-5 rounded-sm border border-black/10 shrink-0"
                    :style="{ backgroundColor: modelValue }"
                />
                <span class="font-mono text-xs">{{ modelValue }}</span>
            </button>

            <!-- Picker panel -->
            <Transition
                enter-active-class="transition-all duration-150 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-100 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-1"
            >
                <div
                    v-if="open"
                    class="absolute top-full left-0 mt-1.5 z-50 w-56 rounded-xl border border-border bg-popover p-3 shadow-lg space-y-3"
                >
                    <!-- Native color picker + hex input -->
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            :value="modelValue"
                            class="size-9 rounded-md cursor-pointer border border-border p-0.5"
                            @input="onColorChange"
                        >
                        <input
                            type="text"
                            :value="hexInput"
                            maxlength="7"
                            placeholder="#000000"
                            class="flex-1 rounded-md border border-input bg-transparent px-2 py-1.5 text-xs font-mono outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] transition-[color,box-shadow]"
                            @input="onHexInput"
                        >
                    </div>

                    <!-- Preset color grid -->
                    <div class="grid grid-cols-8 gap-1">
                        <button
                            v-for="color in PRESET_COLORS"
                            :key="color"
                            type="button"
                            :title="color"
                            class="size-5 rounded-sm border border-black/10 transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            :class="{ 'ring-2 ring-ring ring-offset-1': modelValue === color }"
                            :style="{ backgroundColor: color }"
                            @click="selectPreset(color)"
                        />
                    </div>

                    <!-- Brand palette presets -->
                    <div class="space-y-1.5">
                        <p class="text-xs text-muted-foreground font-medium">Palety</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="palette in BRAND_PALETTES"
                                :key="palette.name"
                                type="button"
                                :title="palette.name"
                                class="flex items-center gap-1 rounded-full border border-border px-2 py-0.5 text-xs hover:border-ring transition-colors"
                                @click="selectPreset(palette.dotColor)"
                            >
                                <span class="size-3 rounded-full shrink-0 border border-black/10" :style="{ backgroundColor: palette.dotColor }" />
                                {{ palette.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>
