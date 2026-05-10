<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
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

    <div class="mx-auto max-w-2xl space-y-8">
        <div>
            <h1 class="text-2xl font-bold">{{ t('profile.security.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('profile.security.subtitle') }}</p>
        </div>

        <!-- 2FA disabled — setup -->
        <div
            v-if="!user?.two_factor_enabled && !twoFactorQrCode"
            class="rounded-lg border p-6 space-y-4"
        >
            <div>
                <h2 class="font-semibold">{{ t('profile.security.twoFactor.title') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.security.twoFactor.disabledDesc') }}</p>
            </div>
            <Button @click="enableTwoFactor">
                {{ t('profile.security.twoFactor.enable') }}
            </Button>
        </div>

        <!-- QR code to scan -->
        <div
            v-if="twoFactorQrCode"
            class="rounded-lg border p-6 space-y-6"
        >
            <div>
                <h2 class="font-semibold">{{ t('profile.security.twoFactor.scanTitle') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.security.twoFactor.scanDesc') }}</p>
            </div>

            <!-- eslint-disable-next-line vue/no-v-html -->
            <div
                class="flex justify-center"
                v-html="twoFactorQrCode"
            />

            <div
                v-if="twoFactorSetupKey"
                class="rounded bg-muted px-3 py-2 text-xs font-mono text-center"
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
            class="rounded-lg border p-6 space-y-4"
        >
            <div>
                <h2 class="font-semibold">{{ t('profile.security.twoFactor.recoveryTitle') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.security.twoFactor.recoveryDesc') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-2 rounded bg-muted p-4">
                <code
                    v-for="code in twoFactorRecoveryCodes"
                    :key="code"
                    class="text-sm font-mono"
                >{{ code }}</code>
            </div>
            <Button
                variant="outline"
                @click="regenerateRecoveryCodes"
            >
                {{ t('profile.security.twoFactor.regenerate') }}
            </Button>
        </div>

        <!-- 2FA enabled -->
        <div
            v-if="user?.two_factor_enabled && !twoFactorQrCode"
            class="rounded-lg border border-success-200 bg-success-50 p-6 space-y-4 dark:border-success-700/50 dark:bg-success-700/10"
        >
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-success-500" />
                <h2 class="font-semibold">{{ t('profile.security.twoFactor.enabledTitle') }}</h2>
            </div>
            <div class="flex gap-3">
                <Button
                    variant="outline"
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
