<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Fingerprint, Plus } from 'lucide-vue-next'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t, locale } = useI18n()

const webAuthnSupported = computed(() => typeof window !== 'undefined' && !!window.PublicKeyCredential)

interface Credential {
    id: string
    name: string
    transports: string[]
    created_at: string
}

defineProps<{ credentials: Credential[] }>()

async function registerPasskey() {
    const optionsRes = await fetch('/webauthn/register/options', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
        },
    })

    const options = await optionsRes.json()

    options.challenge = base64urlDecode(options.challenge)
    options.user.id = base64urlDecode(options.user.id)
    if (options.excludeCredentials) {
        options.excludeCredentials = options.excludeCredentials.map((c: { id: string }) => ({
            ...c,
            id: base64urlDecode(c.id),
        }))
    }

    const credential = await navigator.credentials.create({ publicKey: options }) as PublicKeyCredential | null

    if (!credential) return

    const attestation = credential.response as AuthenticatorAttestationResponse

    await fetch('/webauthn/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({
            id: credential.id,
            rawId: base64urlEncode(credential.rawId),
            response: {
                clientDataJSON: base64urlEncode(attestation.clientDataJSON),
                attestationObject: base64urlEncode(attestation.attestationObject),
            },
            type: credential.type,
        }),
    })

    router.reload()
}

function revokePasskey(id: string) {
    router.delete(`/webauthn/credentials/${id}`, { preserveScroll: true })
}

function base64urlDecode(str: string): ArrayBuffer {
    const base64 = str.replace(/-/g, '+').replace(/_/g, '/')
    const binary = atob(base64)
    return Uint8Array.from(binary, (c) => c.charCodeAt(0)).buffer
}

function base64urlEncode(buffer: ArrayBuffer): string {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '')
}
</script>

<template>
    <Head :title="t('profile.passkeys.headTitle')" />

    <div class="mx-auto w-full max-w-2xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent sm:text-3xl">
                    {{ t('profile.passkeys.title') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ t('profile.passkeys.subtitle') }}</p>
            </div>
            <Button
                :disabled="!webAuthnSupported"
                class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200 self-start sm:self-auto"
                @click="registerPasskey"
            >
                <Plus class="mr-2 size-4" />
                {{ t('profile.passkeys.add') }}
            </Button>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-border bg-card">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />

            <!-- Empty state -->
            <div
                v-if="credentials.length === 0"
                class="flex flex-col items-center justify-center py-14 text-center"
            >
                <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                    <Fingerprint class="size-7 text-primary" />
                </div>
                <p class="text-sm text-muted-foreground">{{ t('profile.passkeys.empty') }}</p>
            </div>

            <div
                v-for="cred in credentials"
                :key="cred.id"
                class="flex items-center justify-between gap-3 border-b border-border/60 px-4 py-4 last:border-0 transition-colors duration-100 hover:bg-muted/30"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                        <Fingerprint class="size-4 text-primary" />
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <p class="text-sm font-medium truncate">{{ cred.name }}</p>
                        <p class="text-xs text-muted-foreground truncate">
                            {{ t('profile.passkeys.addedOn') }} {{ new Date(cred.created_at).toLocaleDateString(locale === 'pl' ? 'pl-PL' : 'en-GB') }}
                            <span v-if="cred.transports.length"> · {{ cred.transports.join(', ') }}</span>
                        </p>
                    </div>
                </div>
                <Button
                    size="sm"
                    variant="destructive"
                    class="shrink-0"
                    @click="revokePasskey(cred.id)"
                >
                    {{ t('profile.passkeys.revoke') }}
                </Button>
            </div>
        </div>

        <p
            v-if="!webAuthnSupported"
            class="text-xs text-muted-foreground"
        >
            {{ t('profile.passkeys.notSupported') }}
        </p>
    </div>
</template>
