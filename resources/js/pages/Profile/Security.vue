<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { ShieldCheck, ShieldOff, Smartphone } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { PageProps } from '@/types'

defineOptions({ layout: AppLayout })

const { t } = useI18n()
const page = usePage<PageProps>()
const user = computed(() => page.props.auth.user)

const props = defineProps<{
    twoFactorQrCode?: string
    twoFactorSetupKey?: string
    twoFactorRecoveryCodes?: string[]
}>()

const showingConfirmation = ref(false)
const showingRecoveryCodes = ref(!!props.twoFactorRecoveryCodes?.length && !props.twoFactorQrCode)

const confirmForm = useForm({ code: '' })

function enableTwoFactor() {
    router.post('/user/two-factor-authentication', {}, {
        preserveScroll: true,
        onSuccess: () => { showingConfirmation.value = true },
    })
}

function confirmTwoFactor() {
    confirmForm.post('/user/confirmed-two-factor-authentication', {
        preserveScroll: true,
        onSuccess: () => {
            showingConfirmation.value = false
            showingRecoveryCodes.value = true
            confirmForm.reset()
        },
    })
}

function disableTwoFactor() {
    router.delete('/user/two-factor-authentication', { preserveScroll: true })
}

function regenerateRecoveryCodes() {
    router.post('/user/two-factor-recovery-codes', {}, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('profile.security.headTitle')" />

    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent">
                {{ t('profile.security.title') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('profile.security.subtitle') }}</p>
        </div>

        <!-- 2FA disabled — setup -->
        <div
            v-if="!user?.two_factor_enabled && !twoFactorQrCode"
            class="relative overflow-hidden rounded-xl border border-border bg-card p-6 space-y-4 hover:border-primary/30 transition-colors duration-200"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-muted ring-1 ring-border">
                    <ShieldOff class="size-4 text-muted-foreground" />
                </div>
                <div>
                    <h2 class="font-semibold text-base">{{ t('profile.security.twoFactor.title') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ t('profile.security.twoFactor.disabledDesc') }}</p>
                </div>
            </div>
            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />
            <Button
                class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                @click="enableTwoFactor"
            >
                {{ t('profile.security.twoFactor.enable') }}
            </Button>
        </div>

        <!-- QR code to scan -->
        <div
            v-if="twoFactorQrCode"
            class="relative overflow-hidden rounded-xl border border-cyan-400/30 bg-card p-6 space-y-6"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                    <Smartphone class="size-4 text-cyan-400" />
                </div>
                <div>
                    <h2 class="font-semibold text-base">{{ t('profile.security.twoFactor.scanTitle') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ t('profile.security.twoFactor.scanDesc') }}</p>
                </div>
            </div>

            <!-- eslint-disable-next-line vue/no-v-html -->
            <div
                class="flex justify-center rounded-xl bg-card p-4 ring-1 ring-border"
                v-html="twoFactorQrCode"
            />

            <div
                v-if="twoFactorSetupKey"
                class="rounded-lg bg-muted px-3 py-2 text-xs font-mono text-center ring-1 ring-border"
            >
                {{ twoFactorSetupKey }}
            </div>

            <div
                v-if="showingConfirmation"
                class="space-y-3"
            >
                <label class="text-sm font-medium">{{ t('profile.security.twoFactor.confirmLabel') }}</label>
                <div class="flex gap-2">
                    <Input
                        v-model="confirmForm.code"
                        autocomplete="one-time-code"
                        inputmode="numeric"
                        placeholder="000000"
                        type="text"
                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                        @keyup.enter="confirmTwoFactor"
                    />
                    <Button
                        :disabled="confirmForm.processing"
                        @click="confirmTwoFactor"
                    >
                        {{ t('profile.security.twoFactor.confirm') }}
                    </Button>
                </div>
                <p
                    v-if="confirmForm.errors.code"
                    class="text-xs text-destructive"
                >{{ confirmForm.errors.code }}</p>
            </div>
        </div>

        <!-- Recovery codes -->
        <div
            v-if="showingRecoveryCodes && twoFactorRecoveryCodes?.length"
            class="relative overflow-hidden rounded-xl border border-gold-500/30 bg-card p-6 space-y-4"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/50 to-transparent" />
            <div>
                <h2 class="font-semibold text-base">{{ t('profile.security.twoFactor.recoveryTitle') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ t('profile.security.twoFactor.recoveryDesc') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2 rounded-lg bg-muted p-4 ring-1 ring-border">
                <code
                    v-for="code in twoFactorRecoveryCodes"
                    :key="code"
                    class="text-sm font-mono text-gold-500"
                >{{ code }}</code>
            </div>
            <Button
                variant="outline"
                class="transition-colors duration-150 hover:border-primary/40 hover:text-primary"
                @click="regenerateRecoveryCodes"
            >
                {{ t('profile.security.twoFactor.regenerate') }}
            </Button>
        </div>

        <!-- 2FA enabled -->
        <div
            v-if="user?.two_factor_enabled && !twoFactorQrCode"
            class="relative overflow-hidden rounded-xl border border-green-500/30 bg-green-500/5 p-6 space-y-4"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-green-500/50 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-green-500/10 ring-1 ring-green-500/20">
                    <ShieldCheck class="size-4 text-green-500" />
                </div>
                <h2 class="font-semibold text-base">{{ t('profile.security.twoFactor.enabledTitle') }}</h2>
            </div>
            <div class="flex gap-3">
                <Button
                    variant="outline"
                    class="transition-colors duration-150 hover:border-primary/40 hover:text-primary"
                    @click="showingRecoveryCodes = !showingRecoveryCodes"
                >
                    {{ showingRecoveryCodes ? t('profile.security.twoFactor.hideRecovery') : t('profile.security.twoFactor.showRecovery') }}
                </Button>
                <Button
                    variant="destructive"
                    @click="disableTwoFactor"
                >
                    {{ t('profile.security.twoFactor.disable') }}
                </Button>
            </div>
        </div>
    </div>
</template>
