<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import TurnstileWidget from '@/components/TurnstileWidget.vue'

const { t } = useI18n()
const siteKey = import.meta.env.VITE_CLOUDFLARE_TURNSTILE_SITE_KEY as string

defineProps<{ status?: string }>()

const form = useForm({
    email: '',
    turnstile_token: '',
})

function submit() {
    form.post('/forgot-password')
}
</script>

<template>
    <Head :title="t('auth.forgotPassword.headTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">{{ t('auth.forgotPassword.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('auth.forgotPassword.description') }}</p>
            </div>

            <div
                v-if="status"
                class="rounded-md bg-success-50 p-3 text-sm text-success-700"
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
                        autofocus
                        :class="{ 'border-destructive': form.errors.email }"
                        type="email"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-xs text-destructive"
                    >{{ form.errors.email }}</p>
                </div>

                <TurnstileWidget
                    :site-key="siteKey"
                    @token="form.turnstile_token = $event"
                    @expire="form.turnstile_token = ''"
                />
                <p
                    v-if="form.errors.turnstile_token"
                    class="text-xs text-destructive"
                >{{ form.errors.turnstile_token }}</p>

                <Button
                    class="w-full"
                    :disabled="form.processing || !form.turnstile_token"
                    type="submit"
                >
                    {{ t('auth.forgotPassword.submit') }}
                </Button>
            </form>

            <p class="text-center text-sm">
                <a
                    class="text-muted-foreground underline-offset-4 hover:underline"
                    href="/login"
                >{{ t('auth.forgotPassword.backToLogin') }}</a>
            </p>
        </div>
    </div>
</template>
