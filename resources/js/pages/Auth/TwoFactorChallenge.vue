<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { QrCode, ShieldCheck } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()
const useRecovery = ref(false)

const form = useForm({
    code: '',
    recovery_code: '',
})

function submit() {
    form.post('/two-factor-challenge', {
        onFinish: () => form.reset('code', 'recovery_code'),
    })
}
</script>

<template>
    <Head :title="t('auth.twoFactor.headTitle')" />

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
                        <ShieldCheck class="size-3 text-primary" />
                    </div>
                </div>
                <div class="space-y-1 text-center">
                    <h1 class="text-xl font-bold">{{ t('auth.twoFactor.title') }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ useRecovery ? t('auth.twoFactor.recoveryDescription') : t('auth.twoFactor.codeDescription') }}
                    </p>
                </div>
            </div>

            <!-- Card -->
            <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/80 p-6 shadow-xl backdrop-blur-sm">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />

                <form
                    class="space-y-4"
                    @submit.prevent="submit"
                >
                    <div
                        v-if="!useRecovery"
                        class="space-y-2"
                    >
                        <label
                            class="text-sm font-medium"
                            for="code"
                        >{{ t('auth.twoFactor.codeLabel') }}</label>
                        <Input
                            id="code"
                            v-model="form.code"
                            autocomplete="one-time-code"
                            autofocus
                            inputmode="numeric"
                            type="text"
                            class="text-center text-lg tracking-widest font-mono focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                        />
                        <p
                            v-if="form.errors.code"
                            class="text-xs text-destructive"
                        >{{ form.errors.code }}</p>
                    </div>

                    <div
                        v-else
                        class="space-y-2"
                    >
                        <label
                            class="text-sm font-medium"
                            for="recovery_code"
                        >{{ t('auth.twoFactor.recoveryLabel') }}</label>
                        <Input
                            id="recovery_code"
                            v-model="form.recovery_code"
                            autocomplete="one-time-code"
                            autofocus
                            type="text"
                            class="font-mono focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                        />
                        <p
                            v-if="form.errors.recovery_code"
                            class="text-xs text-destructive"
                        >{{ form.errors.recovery_code }}</p>
                    </div>

                    <Button
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        :disabled="form.processing"
                        type="submit"
                    >
                        {{ t('auth.twoFactor.submit') }}
                    </Button>
                </form>
            </div>

            <p class="text-center text-sm">
                <button
                    class="text-muted-foreground underline-offset-4 transition-colors duration-150 hover:text-primary hover:underline"
                    type="button"
                    @click="useRecovery = !useRecovery"
                >
                    {{ useRecovery ? t('auth.twoFactor.switchToCode') : t('auth.twoFactor.switchToRecovery') }}
                </button>
            </p>
        </div>
    </div>
</template>
