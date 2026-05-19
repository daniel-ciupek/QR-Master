<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { Mail, QrCode } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'

const { t } = useI18n()

defineProps<{ status?: string }>()

const form = useForm({})

function submit() {
    form.post('/email/verification-notification')
}
</script>

<template>
    <Head :title="t('auth.verifyEmail.headTitle')" />

    <div class="relative flex min-h-screen items-center justify-center bg-background px-4 overflow-hidden">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none" />
        <div class="absolute left-1/2 top-1/3 -translate-x-1/2 -translate-y-1/2 size-[600px] rounded-full bg-cyan-400/5 blur-3xl pointer-events-none" />

        <div class="relative w-full max-w-sm space-y-6 text-center">
            <!-- Icon -->
            <div class="flex flex-col items-center space-y-3">
                <div class="relative flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-primary shadow-[0_0_24px_oklch(0.66_0.25_285/0.4)]">
                    <QrCode class="size-6 text-primary-foreground" />
                    <div class="absolute -bottom-1 -right-1 flex size-5 items-center justify-center rounded-full bg-cyan-400/20 ring-2 ring-background">
                        <Mail class="size-3 text-cyan-400" />
                    </div>
                </div>
            </div>

            <!-- Card -->
            <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/80 p-6 shadow-xl backdrop-blur-sm space-y-4">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />

                <div>
                    <h1 class="text-xl font-bold">{{ t('auth.verifyEmail.title') }}</h1>
                    <p class="mt-2 text-sm text-muted-foreground">{{ t('auth.verifyEmail.description') }}</p>
                </div>

                <div
                    v-if="status === 'verification-link-sent'"
                    class="rounded-lg bg-green-500/10 px-3 py-2 text-sm text-green-500 ring-1 ring-green-500/20"
                >
                    {{ t('auth.verifyEmail.linkSent') }}
                </div>

                <form @submit.prevent="submit">
                    <Button
                        :disabled="form.processing"
                        type="submit"
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                    >
                        {{ t('auth.verifyEmail.resend') }}
                    </Button>
                </form>

                <button
                    class="block w-full text-sm text-muted-foreground underline-offset-4 transition-colors duration-150 hover:text-primary hover:underline"
                    type="button"
                    @click="router.post('/logout')"
                >
                    {{ t('auth.verifyEmail.logout') }}
                </button>
            </div>
        </div>
    </div>
</template>
