<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { KeyRound, QrCode } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import TurnstileWidget from '@/components/TurnstileWidget.vue'

const { t } = useI18n()
const siteKey = import.meta.env.VITE_CLOUDFLARE_TURNSTILE_SITE_KEY as string

defineProps<{ status?: string }>()

const form = useForm({
    email: '',
    turnstile_token: '',
})

function submit() {
    form.post('/forgot-password')
}
</script>

<template>
    <Head :title="t('auth.forgotPassword.headTitle')" />

    <div class="relative flex min-h-screen items-center justify-center bg-background px-4 overflow-hidden">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none" />
        <div class="absolute left-1/2 top-1/3 -translate-x-1/2 -translate-y-1/2 size-[600px] rounded-full bg-primary/5 blur-3xl pointer-events-none" />

        <div class="relative w-full max-w-sm space-y-6">
            <!-- Logo / Branding -->
            <div class="flex flex-col items-center space-y-3">
                <div class="relative flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-primary shadow-[0_0_24px_oklch(0.66_0.25_285/0.4)]">
                    <QrCode class="size-6 text-primary-foreground" />
                    <div class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-card ring-2 ring-background">
                        <KeyRound class="size-3 text-primary" />
                    </div>
                </div>
                <div class="space-y-1 text-center">
                    <h1 class="text-xl font-bold">{{ t('auth.forgotPassword.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('auth.forgotPassword.description') }}</p>
                </div>
            </div>

            <!-- Card -->
            <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/80 p-6 shadow-xl backdrop-blur-sm">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />

                <div
                    v-if="status"
                    class="mb-4 rounded-lg bg-green-500/10 px-3 py-2 text-sm text-green-500 ring-1 ring-green-500/20"
                >
                    {{ status }}
                </div>

                <form
                    class="space-y-4"
                    @submit.prevent="submit"
                >
                    <div class="space-y-2">
                        <label
                            class="text-sm font-medium"
                            for="email"
                        >{{ t('auth.emailLabel') }}</label>
                        <Input
                            id="email"
                            v-model="form.email"
                            autofocus
                            :class="{ 'border-destructive': form.errors.email }"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                            type="email"
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-destructive"
                        >{{ form.errors.email }}</p>
                    </div>

                    <TurnstileWidget
                        :site-key="siteKey"
                        @token="form.turnstile_token = $event"
                        @expire="form.turnstile_token = ''"
                    />
                    <p
                        v-if="form.errors.turnstile_token"
                        class="text-xs text-destructive"
                    >{{ form.errors.turnstile_token }}</p>

                    <Button
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        :disabled="form.processing || !form.turnstile_token"
                        type="submit"
                    >
                        {{ t('auth.forgotPassword.submit') }}
                    </Button>
                </form>
            </div>

            <p class="text-center text-sm">
                <a
                    class="text-muted-foreground underline-offset-4 transition-colors duration-150 hover:text-primary hover:underline"
                    href="/login"
                >{{ t('auth.forgotPassword.backToLogin') }}</a>
            </p>
        </div>
    </div>
</template>
