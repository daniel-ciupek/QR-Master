<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import TurnstileWidget from '@/components/TurnstileWidget.vue'

const { t } = useI18n()

const props = defineProps<{
    hash: string
    title: string
    siteKey: string
}>()

const form = useForm({ turnstile_token: '' })

function onToken(token: string) {
    form.turnstile_token = token
    form.post(`/q/${props.hash}/challenge`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('botChallenge.pageTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6 text-center">
            <div class="space-y-1">
                <p class="text-3xl">🛡️</p>
                <h1 class="text-lg font-semibold">{{ t('botChallenge.title') }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('botChallenge.subtitle', { name: props.title }) }}
                </p>
            </div>

            <div class="flex justify-center">
                <TurnstileWidget
                    :site-key="props.siteKey"
                    theme="auto"
                    @token="onToken"
                />
            </div>

            <p v-if="form.errors.turnstile_token" class="text-xs text-destructive">
                {{ form.errors.turnstile_token }}
            </p>

            <p class="text-xs text-muted-foreground">{{ t('botChallenge.hint') }}</p>
        </div>
    </div>
</template>
