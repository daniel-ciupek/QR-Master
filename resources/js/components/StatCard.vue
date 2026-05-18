<script setup lang="ts">
import type { Component } from 'vue'

withDefaults(defineProps<{
    label: string
    value: string | number
    icon?: Component
    accent?: 'primary' | 'cyan' | 'gold' | 'emerald'
    trend?: string
    trendUp?: boolean
}>(), {
    accent: 'primary',
})
</script>

<template>
    <div
        class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 transition-all duration-200 hover:border-primary/30 hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.08)]"
    >
        <!-- Gradient top-border per accent -->
        <div
            class="absolute inset-x-0 top-0 h-px"
            :class="{
                'bg-gradient-to-r from-transparent via-primary/60 to-transparent': accent === 'primary',
                'bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent': accent === 'cyan',
                'bg-gradient-to-r from-transparent via-gold-500/60 to-transparent': accent === 'gold',
                'bg-gradient-to-r from-transparent via-emerald-400/50 to-transparent': accent === 'emerald',
            }"
        />

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ label }}</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold tabular-nums tracking-tight">{{ value }}</p>
                <p v-if="trend" class="mt-1 text-xs" :class="trendUp ? 'text-emerald-400' : 'text-muted-foreground'">
                    {{ trend }}
                </p>
            </div>

            <!-- Icon slot lub komponent -->
            <div
                v-if="icon || $slots.icon"
                class="flex shrink-0 size-10 items-center justify-center rounded-full ring-1 transition-colors duration-200"
                :class="{
                    'bg-primary/10 ring-primary/20 text-primary': accent === 'primary',
                    'bg-cyan-400/10 ring-cyan-400/20 text-cyan-400': accent === 'cyan',
                    'bg-gold-500/10 ring-gold-500/20 text-gold-500': accent === 'gold',
                    'bg-emerald-400/10 ring-emerald-400/20 text-emerald-400': accent === 'emerald',
                }"
            >
                <slot name="icon">
                    <component :is="icon" class="size-5" />
                </slot>
            </div>
        </div>
    </div>
</template>
