<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { QrCode } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()

defineProps<{
    canResetPassword?: boolean
    status?: string
}>()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head :title="t('auth.login.headTitle')" />

    <div class="relative flex min-h-screen items-center justify-center bg-background px-4 overflow-hidden">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none" />
        <!-- Radial glow center -->
        <div class="absolute left-1/2 top-1/3 -translate-x-1/2 -translate-y-1/2 size-[600px] rounded-full bg-primary/5 blur-3xl pointer-events-none" />

        <div class="relative w-full max-w-sm space-y-6">
            <!-- Logo / Branding -->
            <div class="flex flex-col items-center space-y-3">
                <div class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-primary shadow-[0_0_24px_oklch(0.66_0.25_285/0.4)]">
                    <QrCode class="size-6 text-primary-foreground" />
                </div>
                <div class="space-y-1 text-center">
                    <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent">
                        {{ t('app.name') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ t('auth.login.subtitle') }}</p>
                </div>
            </div>

            <!-- Card -->
            <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/80 p-6 shadow-xl backdrop-blur-sm">
                <!-- Gradient top-border -->
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
                            autocomplete="email"
                            autofocus
                            :class="{ 'border-destructive': form.errors.email }"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                            :placeholder="t('auth.emailPlaceholder')"
                            type="email"
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label
                                class="text-sm font-medium"
                                for="password"
                            >{{ t('auth.passwordLabel') }}</label>
                            <a
                                v-if="canResetPassword"
                                class="text-xs text-muted-foreground underline-offset-4 transition-colors duration-150 hover:text-primary hover:underline"
                                href="/forgot-password"
                            >{{ t('auth.login.forgotPassword') }}</a>
                        </div>
                        <Input
                            id="password"
                            v-model="form.password"
                            autocomplete="current-password"
                            :class="{ 'border-destructive': form.errors.password }"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                            type="password"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <Button
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        :disabled="form.processing"
                        type="submit"
                    >
                        {{ form.processing ? t('auth.login.submitting') : t('auth.login.submit') }}
                    </Button>
                </form>
            </div>

            <p class="text-center text-sm text-muted-foreground">
                {{ t('auth.login.noAccount') }}
                <a
                    class="font-medium text-primary underline-offset-4 transition-colors duration-150 hover:underline"
                    href="/register"
                >{{ t('auth.login.register') }}</a>
            </p>
        </div>
    </div>
</template>
