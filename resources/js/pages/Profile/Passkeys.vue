<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
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

    <div class="mx-auto max-w-2xl space-y-8">
        <div>
            <h1 class="text-2xl font-bold">{{ t('profile.passkeys.title') }}</h1>
            <p class="text-sm text-muted-foreground mt-1">{{ t('profile.passkeys.subtitle') }}</p>
        </div>

        <div class="space-y-3">
            <div
                v-if="credentials.length === 0"
                class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                {{ t('profile.passkeys.empty') }}
            </div>

            <div
                v-for="cred in credentials"
                :key="cred.id"
                class="flex items-center justify-between rounded-lg border p-4"
            >
                <div class="space-y-0.5">
                    <p class="text-sm font-medium">{{ cred.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('profile.passkeys.addedOn') }} {{ new Date(cred.created_at).toLocaleDateString(locale === 'pl' ? 'pl-PL' : 'en-GB') }}
                        <span v-if="cred.transports.length"> · {{ cred.transports.join(', ') }}</span>
                    </p>
                </div>
                <Button
                    size="sm"
                    variant="destructive"
                    @click="revokePasskey(cred.id)"
                >
                    {{ t('profile.passkeys.revoke') }}
                </Button>
            </div>
        </div>

        <Button
            :disabled="!webAuthnSupported"
            @click="registerPasskey"
        >
            {{ t('profile.passkeys.add') }}
        </Button>

        <p
            v-if="!webAuthnSupported"
            class="text-xs text-muted-foreground"
        >
            {{ t('profile.passkeys.notSupported') }}
        </p>
    </div>
</template>
