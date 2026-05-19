<script setup lang="ts">
import { QrCode } from 'lucide-vue-next'

withDefaults(defineProps<{
    title?: string
    description?: string
    accentColor?: 'primary' | 'cyan' | 'gold' | 'destructive'
    showLogo?: boolean
}>(), {
    accentColor: 'primary',
    showLogo: true,
})
</script>

<template>
    <!-- Full-screen centered layout z dot-grid + radial glow -->
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background p-4">
        <!-- Dot grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Radial glow — kolor per accent -->
        <div
            class="absolute inset-0 pointer-events-none"
            :class="{
                'bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.66_0.25_285/0.07),transparent)]': accentColor === 'primary',
                'bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.72_0.15_200/0.07),transparent)]': accentColor === 'cyan',
                'bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.78_0.15_85/0.07),transparent)]': accentColor === 'gold',
                'bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.63_0.23_25/0.07),transparent)]': accentColor === 'destructive',
            }"
        />

        <!-- Logo mark -->
        <div v-if="showLogo" class="relative mb-8 flex flex-col items-center gap-2">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 shadow-[0_0_20px_oklch(0.66_0.25_285/0.15)]">
                <QrCode class="size-6 text-primary" />
            </div>
            <span class="text-xs font-medium tracking-wider text-muted-foreground uppercase">QR Master</span>
        </div>

        <!-- Card z gradient top-border -->
        <div class="relative w-full max-w-sm overflow-hidden rounded-2xl border border-border bg-card p-8 shadow-xl">
            <!-- Gradient top-border — kolor per accent -->
            <div
                class="absolute inset-x-0 top-0 h-px"
                :class="{
                    'bg-gradient-to-r from-transparent via-primary/60 to-transparent': accentColor === 'primary',
                    'bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent': accentColor === 'cyan',
                    'bg-gradient-to-r from-transparent via-gold-500/60 to-transparent': accentColor === 'gold',
                    'bg-gradient-to-r from-transparent via-destructive/60 to-transparent': accentColor === 'destructive',
                }"
            />

            <!-- Title + description -->
            <div v-if="title || description" class="mb-6 text-center">
                <h1 v-if="title" class="text-xl font-bold tracking-tight">{{ title }}</h1>
                <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
            </div>

            <!-- Slot na zawartość -->
            <slot />
        </div>

        <!-- Footer slot -->
        <div class="relative mt-6">
            <slot name="footer" />
        </div>
    </div>
</template>
