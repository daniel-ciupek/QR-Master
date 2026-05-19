<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Lock, QrCode, Eye, EyeOff } from 'lucide-vue-next'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()

const props = defineProps<{
    hash: string
    title: string
}>()

const form = useForm({ password: '' })
const showPassword = ref(false)

function submit() {
    form.post(`/q/${props.hash}/unlock`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('qrPassword.pageTitle')" />

    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background p-4">
        <!-- Dot grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Radial glow center -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.66_0.25_285/0.07),transparent)] pointer-events-none" />

        <!-- Logo + branding -->
        <div class="relative mb-8 flex flex-col items-center gap-3">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                <QrCode class="size-6 text-primary" />
            </div>
            <span class="text-sm text-muted-foreground font-medium">QR Master</span>
        </div>

        <!-- Card -->
        <div class="relative w-full max-w-sm rounded-2xl border border-border bg-card p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />

            <!-- Icon + heading -->
            <div class="mb-6 flex flex-col items-center gap-3 text-center">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                    <Lock class="size-7 text-primary" />
                </div>
                <div>
                    <h1 class="text-xl font-bold">{{ t('qrPassword.title') }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('qrPassword.subtitle', { name: props.title }) }}
                    </p>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <label class="text-sm font-medium" for="password">
                        {{ t('qrPassword.passwordLabel') }}
                    </label>
                    <div class="relative">
                        <Input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            :placeholder="t('qrPassword.passwordPlaceholder')"
                            :class="[
                                'pr-10 focus-visible:ring-primary/50 focus-visible:border-primary/50',
                                form.errors.password ? 'border-destructive' : '',
                            ]"
                            autocomplete="current-password"
                            autofocus
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors duration-150"
                            @click="showPassword = !showPassword"
                        >
                            <Eye v-if="!showPassword" class="size-4" />
                            <EyeOff v-else class="size-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-destructive">
                        {{ form.errors.password }}
                    </p>
                </div>

                <Button
                    type="submit"
                    class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                    :disabled="form.processing || !form.password"
                >
                    <Lock v-if="!form.processing" class="size-4 mr-2" />
                    {{ form.processing ? t('qrPassword.verifying') : t('qrPassword.submit') }}
                </Button>
            </form>
        </div>

        <!-- Footer branding -->
        <p class="relative mt-6 text-xs text-muted-foreground/60">
            Powered by <span class="text-muted-foreground">QR Master</span>
        </p>
    </div>
</template>
