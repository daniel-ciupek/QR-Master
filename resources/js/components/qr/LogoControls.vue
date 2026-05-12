<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
    logoSize: number   // 0.1 – 0.3 (fraction)
    logoMargin: number // 0 – 20 (px)
}>()

const emit = defineEmits<{
    'update:logoSize': [value: number]
    'update:logoMargin': [value: number]
}>()

const { t } = useI18n()
</script>

<template>
    <div class="space-y-3">
        <!-- Logo size -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium leading-none" for="logo-size">
                    {{ t('qr.logo.size') }}
                </label>
                <span class="text-xs font-mono text-muted-foreground">{{ Math.round(logoSize * 100) }}%</span>
            </div>
            <input
                id="logo-size"
                type="range"
                min="10"
                max="30"
                step="1"
                :value="Math.round(logoSize * 100)"
                class="w-full accent-primary cursor-pointer"
                @input="emit('update:logoSize', Number(($event.target as HTMLInputElement).value) / 100)"
            >
            <div class="flex justify-between text-xs text-muted-foreground">
                <span>10%</span>
                <span>30%</span>
            </div>
        </div>

        <!-- Logo margin -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium leading-none" for="logo-margin">
                    {{ t('qr.logo.margin') }}
                </label>
                <span class="text-xs font-mono text-muted-foreground">{{ logoMargin }}px</span>
            </div>
            <input
                id="logo-margin"
                type="range"
                min="0"
                max="20"
                step="1"
                :value="logoMargin"
                class="w-full accent-primary cursor-pointer"
                @input="emit('update:logoMargin', Number(($event.target as HTMLInputElement).value))"
            >
            <div class="flex justify-between text-xs text-muted-foreground">
                <span>0px</span>
                <span>20px</span>
            </div>
        </div>
    </div>
</template>
