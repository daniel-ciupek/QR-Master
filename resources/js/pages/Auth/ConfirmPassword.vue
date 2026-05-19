<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Lock, ShieldCheck } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()

const form = useForm({ password: '' })

function submit() {
    form.post('/user/confirm-password', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head :title="t('auth.confirmPassword.headTitle')" />

    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background px-4">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Radial glow -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.66_0.25_285/0.07),transparent)] pointer-events-none" />

        <div class="relative w-full max-w-sm space-y-8">
            <!-- Logo mark -->
            <div class="flex flex-col items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/30 shadow-[0_0_24px_oklch(0.66_0.25_285/0.15)]">
                    <ShieldCheck class="size-7 text-primary" />
                </div>
                <div class="text-center space-y-1">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('auth.confirmPassword.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground max-w-xs">{{ t('auth.confirmPassword.description') }}</p>
                </div>
            </div>

            <!-- Card -->
            <div class="relative rounded-xl border border-border bg-card p-6 shadow-lg overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />

                <form
                    class="space-y-5"
                    @submit.prevent="submit"
                >
                    <div class="space-y-2">
                        <label
                            class="text-sm font-medium flex items-center gap-1.5"
                            for="password"
                        >
                            <Lock class="size-3.5 text-muted-foreground" />
                            {{ t('auth.passwordLabel') }}
                        </label>
                        <Input
                            id="password"
                            v-model="form.password"
                            autocomplete="current-password"
                            autofocus
                            :class="[
                                'focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150',
                                { 'border-destructive focus-visible:ring-destructive/50': form.errors.password }
                            ]"
                            type="password"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-destructive"
                        >{{ form.errors.password }}</p>
                    </div>

                    <Button
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        :disabled="form.processing"
                        type="submit"
                    >
                        {{ t('auth.confirmPassword.submit') }}
                    </Button>
                </form>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-muted-foreground">
                QR Master — secured with end-to-end encryption
            </p>
        </div>
    </div>
</template>
