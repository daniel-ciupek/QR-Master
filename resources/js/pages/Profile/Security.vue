<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { PageProps } from '@/types'

const page = usePage<PageProps>()
const user = computed(() => page.props.auth.user)

const props = defineProps<{
    twoFactorQrCode?: string
    twoFactorSetupKey?: string
    twoFactorRecoveryCodes?: string[]
}>()

// Stany UI
const showingConfirmation = ref(false)
const showingRecoveryCodes = ref(!!props.twoFactorRecoveryCodes?.length && !props.twoFactorQrCode)

// Formularz potwierdzenia kodu 2FA
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
    router.delete('/user/two-factor-authentication', {
        preserveScroll: true,
    })
}

function regenerateRecoveryCodes() {
    router.post('/user/two-factor-recovery-codes', {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Bezpieczeństwo konta" />

    <div class="mx-auto max-w-2xl space-y-8 px-4 py-8">
        <div>
            <h1 class="text-2xl font-bold">Bezpieczeństwo</h1>
            <p class="text-sm text-muted-foreground">Zarządzaj weryfikacją dwuetapową (2FA)</p>
        </div>

        <!-- 2FA wyłączone — setup -->
        <div
            v-if="!user?.two_factor_enabled && !twoFactorQrCode"
            class="rounded-lg border p-6 space-y-4"
        >
            <div>
                <h2 class="font-semibold">Weryfikacja dwuetapowa</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Dodaj dodatkową warstwę ochrony do swojego konta używając aplikacji
                    uwierzytelniającej (Google Authenticator, Authy, itp.)
                </p>
            </div>
            <Button @click="enableTwoFactor">
                Włącz 2FA
            </Button>
        </div>

        <!-- QR code do zeskanowania -->
        <div
            v-if="twoFactorQrCode"
            class="rounded-lg border p-6 space-y-6"
        >
            <div>
                <h2 class="font-semibold">Skanuj kod QR</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Zeskanuj poniższy kod w aplikacji uwierzytelniającej, następnie potwierdź kodem jednorazowym.
                </p>
            </div>

            <!-- v-html: SVG generowany server-side przez Fortify — bezpieczne -->
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

            <!-- Potwierdzenie kodu -->
            <div
                v-if="showingConfirmation"
                class="space-y-3"
            >
                <label class="text-sm font-medium">Potwierdź kod z aplikacji</label>
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
                        Potwierdź
                    </Button>
                </div>
                <p
                    v-if="confirmForm.errors.code"
                    class="text-xs text-destructive"
                >{{ confirmForm.errors.code }}</p>
            </div>
        </div>

        <!-- Kody odzyskiwania -->
        <div
            v-if="showingRecoveryCodes && twoFactorRecoveryCodes?.length"
            class="rounded-lg border p-6 space-y-4"
        >
            <div>
                <h2 class="font-semibold">Kody odzyskiwania</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Zapisz te kody w bezpiecznym miejscu. Służą do odzyskania dostępu jeśli
                    utracisz dostęp do aplikacji uwierzytelniającej.
                </p>
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
                Wygeneruj nowe kody
            </Button>
        </div>

        <!-- 2FA włączone -->
        <div
            v-if="user?.two_factor_enabled && !twoFactorQrCode"
            class="rounded-lg border border-success-200 bg-success-50 p-6 space-y-4 dark:border-success-700/50 dark:bg-success-700/10"
        >
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-success-500" />
                <h2 class="font-semibold">2FA jest włączone</h2>
            </div>
            <div class="flex gap-3">
                <Button
                    variant="outline"
                    @click="showingRecoveryCodes = !showingRecoveryCodes"
                >
                    {{ showingRecoveryCodes ? 'Ukryj kody' : 'Pokaż kody odzyskiwania' }}
                </Button>
                <Button
                    variant="destructive"
                    @click="disableTwoFactor"
                >
                    Wyłącz 2FA
                </Button>
            </div>
        </div>
    </div>
</template>
