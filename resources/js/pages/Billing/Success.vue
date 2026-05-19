<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { CheckCircle2, ArrowRight, Star } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const { t } = useI18n()

defineProps<{
    plan: string
    planName: string
}>()
</script>

<template>
    <Head :title="t('billing.success.pageTitle')" />

    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background px-4">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Gold/emerald radial glow for celebratory feel -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.78_0.15_85/0.06),transparent)] pointer-events-none" />

        <div class="relative w-full max-w-md space-y-8 text-center">
            <!-- Icon -->
            <div class="flex flex-col items-center gap-4">
                <div class="relative flex size-20 items-center justify-center rounded-2xl bg-gold-500/10 ring-1 ring-gold-500/30 shadow-[0_0_32px_oklch(0.78_0.15_85/0.2)]">
                    <CheckCircle2 class="size-10 text-gold-500" />
                    <!-- Subtle sparkle -->
                    <div class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-gold-500/20 ring-1 ring-gold-500/40">
                        <Star class="size-3 fill-gold-500 text-gold-500" />
                    </div>
                </div>

                <div class="space-y-2">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-gold-400 via-gold-500 to-yellow-400 bg-clip-text text-transparent">
                        {{ t('billing.success.title') }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t('billing.success.subtitle', { plan: planName }) }}
                    </p>
                </div>
            </div>

            <!-- Info card with gold top-border -->
            <div class="relative rounded-xl border border-border bg-card p-5 text-left overflow-hidden shadow-md">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />
                <div class="flex items-start gap-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20 mt-0.5">
                        <Star class="size-4 text-gold-500" />
                    </div>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        {{ t('billing.success.hint') }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <Button
                    class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                    @click="router.visit('/dashboard')"
                >
                    {{ t('billing.success.toDashboard') }}
                    <ArrowRight class="size-4" />
                </Button>
                <Button
                    variant="outline"
                    class="hover:border-primary/40 transition-colors duration-150"
                    @click="router.visit('/pricing')"
                >
                    {{ t('billing.success.viewPricing') }}
                </Button>
            </div>

            <!-- Plan badge -->
            <div class="flex justify-center">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-500/10 px-3 py-1 text-xs font-medium text-gold-500 ring-1 ring-gold-500/20">
                    <Star class="size-3 fill-gold-500" />
                    {{ planName }} — Active
                </span>
            </div>
        </div>
    </div>
</template>
