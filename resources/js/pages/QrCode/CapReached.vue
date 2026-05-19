<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { FlagOff, QrCode } from 'lucide-vue-next'

const { t } = useI18n()

defineProps<{
    title: string
    limit: number
}>()
</script>

<template>
    <Head :title="t('capReached.pageTitle')" />

    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background p-4">
        <!-- Dot grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Radial glow center — gold tint for limit context -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.78_0.15_85/0.06),transparent)] pointer-events-none" />

        <!-- Logo + branding -->
        <div class="relative mb-8 flex flex-col items-center gap-3">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                <QrCode class="size-6 text-primary" />
            </div>
            <span class="text-sm text-muted-foreground font-medium">QR Master</span>
        </div>

        <!-- Card -->
        <div class="relative w-full max-w-sm rounded-2xl border border-border bg-card p-8 shadow-xl overflow-hidden text-center">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />

            <!-- Icon + heading -->
            <div class="mb-5 flex flex-col items-center gap-3">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-gold-500/10 ring-1 ring-gold-500/20">
                    <FlagOff class="size-7 text-gold-500" />
                </div>
                <h1 class="text-xl font-bold">{{ t('capReached.title') }}</h1>
            </div>

            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent mb-4" />

            <p class="text-sm text-muted-foreground leading-relaxed">
                {{ t('capReached.subtitle', { name: title, limit }) }}
            </p>

            <p class="mt-3 text-xs text-muted-foreground/70">
                {{ t('capReached.hint') }}
            </p>

            <!-- Scan limit badge -->
            <div class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-gold-500/10 px-3 py-1 text-xs font-medium text-gold-500 ring-1 ring-gold-500/20">
                <span class="size-1.5 rounded-full bg-gold-500 inline-block" />
                {{ t('capReached.limit', { limit }) }}
            </div>
        </div>

        <!-- Footer branding -->
        <p class="relative mt-6 text-xs text-muted-foreground/60">
            Powered by <span class="text-muted-foreground">QR Master</span>
        </p>
    </div>
</template>
