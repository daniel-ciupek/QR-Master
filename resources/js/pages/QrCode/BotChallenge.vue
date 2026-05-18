<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { ShieldCheck, QrCode } from 'lucide-vue-next'
import TurnstileWidget from '@/components/TurnstileWidget.vue'

const { t } = useI18n()

const props = defineProps<{
    hash: string
    title: string
    siteKey: string
}>()

const form = useForm({ turnstile_token: '' })

function onToken(token: string) {
    if (form.processing) return
    form.turnstile_token = token
    form.post(`/q/${props.hash}/challenge`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('botChallenge.pageTitle')" />

    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background p-4">
        <!-- Dot grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Radial glow center — cyan tint for security context -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.72_0.15_200/0.07),transparent)] pointer-events-none" />

        <!-- Logo + branding -->
        <div class="relative mb-8 flex flex-col items-center gap-3">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                <QrCode class="size-6 text-primary" />
            </div>
            <span class="text-sm text-muted-foreground font-medium">QR Master</span>
        </div>

        <!-- Card -->
        <div class="relative w-full max-w-sm rounded-2xl border border-border bg-card p-8 shadow-xl overflow-hidden text-center">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent" />

            <!-- Icon + heading -->
            <div class="mb-6 flex flex-col items-center gap-3">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-cyan-400/10 ring-1 ring-cyan-400/20">
                    <ShieldCheck class="size-7 text-cyan-400" />
                </div>
                <div>
                    <h1 class="text-xl font-bold">{{ t('botChallenge.title') }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('botChallenge.subtitle', { name: props.title }) }}
                    </p>
                </div>
            </div>

            <!-- Turnstile widget -->
            <div class="flex justify-center mb-4">
                <TurnstileWidget
                    :site-key="props.siteKey"
                    theme="dark"
                    @token="onToken"
                />
            </div>

            <p v-if="form.errors.turnstile_token" class="mb-2 text-xs text-destructive">
                {{ form.errors.turnstile_token }}
            </p>

            <p class="text-xs text-muted-foreground">{{ t('botChallenge.hint') }}</p>
        </div>

        <!-- Footer branding -->
        <p class="relative mt-6 text-xs text-muted-foreground/60">
            Powered by <span class="text-muted-foreground">QR Master</span>
        </p>
    </div>
</template>
